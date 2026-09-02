<?php
// includes/slots.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

/**
 * Return an array of available start times ('HH:MM') for a given provider,
 * service, and date.
 */
function get_available_slots(int $providerId, int $serviceId, string $date): array {
    $pdo = db();

    // 1. Load the service (need duration + buffer)
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ? AND provider_id = ? AND active = 1');
    $stmt->execute([$serviceId, $providerId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$service) return [];

    $duration = (int)$service['duration_minutes'];
    $buffer   = (int)$service['buffer_minutes'];
    $slotStep = $duration + $buffer;

    // 2. Check the date isn't fully blocked
    $stmt = $pdo->prepare('SELECT 1 FROM blocked_dates WHERE provider_id = ? AND blocked_date = ?');
    $stmt->execute([$providerId, $date]);
    if ($stmt->fetch()) return [];

    // 3. Don't allow booking in the past
    $today = date('Y-m-d');
    if ($date < $today) return [];

    // 4. Get availability windows for that day of week
    $dow = (int)date('w', strtotime($date)); // 0=Sun..6=Sat
    $stmt = $pdo->prepare('SELECT start_time, end_time FROM availability WHERE provider_id = ? AND day_of_week = ?');
    $stmt->execute([$providerId, $dow]);
    $windows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$windows) return [];

    // 5. Get existing bookings for that date (confirmed only)
    $stmt = $pdo->prepare("
        SELECT start_datetime, end_datetime FROM bookings
        WHERE provider_id = ? AND status = 'confirmed'
        AND date(start_datetime) = ?
    ");
    $stmt->execute([$providerId, $date]);
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert existing bookings to [startTs, endTs] pairs for overlap checks
    $busy = array_map(function ($b) {
        return [strtotime($b['start_datetime']), strtotime($b['end_datetime'])];
    }, $existing);

    $isToday = ($date === $today);
    $nowTs = time();

    $slots = [];

    foreach ($windows as $window) {
        $windowStart = strtotime("$date {$window['start_time']}");
        $windowEnd   = strtotime("$date {$window['end_time']}");

        for ($candidateStart = $windowStart; $candidateStart + ($duration * 60) <= $windowEnd; $candidateStart += $slotStep * 60) {
            $candidateEnd = $candidateStart + ($duration * 60);

            // Skip slots already in the past (if booking for today)
            if ($isToday && $candidateStart <= $nowTs) continue;

            // Check overlap against every existing booking
            $overlaps = false;
            foreach ($busy as [$busyStart, $busyEnd]) {
                if ($candidateStart < $busyEnd && $candidateEnd > $busyStart) {
                    $overlaps = true;
                    break;
                }
            }

            if (!$overlaps) {
                $slots[] = date('H:i', $candidateStart);
            }
        }
    }

    return $slots;
}

/**
 * Attempt to create a booking. Re-validates the slot is still free
 * (protects against two people booking the same slot at once).
 * Returns [success(bool), message(string), cancelToken(?string)]
 */
function create_booking(int $providerId, int $serviceId, string $date, string $time, string $name, string $email, string $phone): array {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ? AND provider_id = ?');
    $stmt->execute([$serviceId, $providerId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$service) return [false, 'Service not found.', null];

    $stmt = $pdo->prepare('SELECT * FROM providers WHERE id = ?');
    $stmt->execute([$providerId]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$provider) return [false, 'Provider not found.', null];

    // Limit: the same email can't hold two different FUTURE confirmed
    // appointments on the same calendar date with this provider. Scoped
    // per-provider (not global) so a client using BookMe with two different
    // businesses isn't affected by one blocking the other. This checks the
    // date the APPOINTMENT is for, not when it was booked — so someone can
    // still book today for an appointment next week, then book again today
    // for a different week, as long as the two appointment dates differ.
    $stmt = $pdo->prepare("
        SELECT 1 FROM bookings
        WHERE provider_id = ? AND client_email = ? AND status = 'confirmed'
        AND DATE(start_datetime) = ?
        LIMIT 1
    ");
    $stmt->execute([$providerId, $email, $date]);
    if ($stmt->fetch()) {
        return [false, "You already have an appointment booked with us on that date. Please choose a different day, or cancel your existing appointment first.", null];
    }

    $availableSlots = get_available_slots($providerId, $serviceId, $date);
    if (!in_array($time, $availableSlots, true)) {
        return [false, 'Sorry, that slot was just taken. Please pick another.', null];
    }

    $startDt = "$date $time:00";
    $endTs = strtotime($startDt) + ((int)$service['duration_minutes'] * 60);
    $endDt = date('Y-m-d H:i:s', $endTs);
    $cancelToken = bin2hex(random_bytes(16));

    $stmt = $pdo->prepare("
        INSERT INTO bookings (provider_id, service_id, client_name, client_email, client_phone, start_datetime, end_datetime, cancel_token)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$providerId, $serviceId, $name, $email, $phone, $startDt, $endDt, $cancelToken]);

    // Build the booking array the mailer functions expect, without a second
    // round-trip to the database.
    $booking = [
        'client_name'     => $name,
        'client_email'    => $email,
        'client_phone'    => $phone,
        'start_datetime'  => $startDt,
        'end_datetime'    => $endDt,
        'cancel_token'    => $cancelToken,
    ];

    // Email failures should never break a booking that already succeeded —
    // the booking is confirmed in the database regardless of whether either
    // email actually sends.
    send_booking_confirmation_email($booking, $service, $provider);
    send_provider_notification_email($booking, $service, $provider);

    return [true, 'Booking confirmed!', $cancelToken];
}
