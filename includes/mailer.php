<?php
// includes/mailer.php
//
// Sends transactional emails. Two transports are supported:
//   - 'smtp'     : uses includes/smtp_mailer.php (real SMTP credentials —
//                  use this if PHP's built-in mail() isn't delivering)
//   - 'php_mail' : PHP's built-in mail() function
//
// Set MAIL_TRANSPORT below. Defaults to 'smtp'.

require_once __DIR__ . '/smtp_mailer.php';

//const MAIL_TRANSPORT = 'smtp'; // 'smtp' or 'php_mail'
const MAIL_TRANSPORT = 'php_mail'; // 'smtp' or 'php_mail'


/**
 * Build the site's base URL (scheme + host) from the current request,
 * so links in emails point at wherever this is actually running.
 */
function site_base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

/**
 * Low-level send. Returns true if the message was accepted for delivery
 * (NOT a guarantee of inbox delivery).
 */
function send_email(string $to, string $subject, string $body, string $fromEmail, string $fromName = ''): bool {
    if (MAIL_TRANSPORT === 'smtp') {
        [$ok, $debugLog] = smtp_send_email($to, $subject, $body, $fromEmail, $fromName);
        if (!$ok) {
            // Logged server-side only — never shown to the client booking
            // the appointment. Check your host's PHP error log if emails
            // aren't arriving; this line tells you exactly which SMTP step
            // failed (auth, connection, recipient refused, etc).
            error_log("SMTP send failed to $to: $debugLog");
        }
        return $ok;
    }

    return send_via_php_mail($to, $subject, $body, $fromEmail, $fromName);
}

/**
 * PHP's built-in mail() function. Kept available as a fallback / for hosts
 * where it does work — switch MAIL_TRANSPORT above to use this instead.
 */
function send_via_php_mail(string $to, string $subject, string $body, string $fromEmail, string $fromName = ''): bool {
    $from = $fromName !== '' ? "$fromName <$fromEmail>" : $fromEmail;

    $headers = [
        "From: $from",
        "Reply-To: $fromEmail",
        "Content-Type: text/plain; charset=UTF-8",
        "MIME-Version: 1.0",
    ];

    // @ suppresses PHP's warning if mail() isn't configured on this host —
    // we still return false so the caller can decide whether to surface it,
    // but we don't want a misconfigured mail server to break the booking
    // flow itself (the booking should still succeed even if the email fails).
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Best-effort "no-reply" sender address on the same domain as the request.
 * Many hosts reject or spam-flag mail sent "from" a domain unrelated to the
 * one actually sending it, so this stays consistent with wherever the app
 * is deployed rather than being hardcoded.
 */
function default_from_address(): string {
    $host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    // Strip a port if present (e.g. localhost:8000) since that's not a valid
    // email domain segment.
    $host = preg_replace('/:\d+$/', '', $host);
    return 'no-reply@' . $host;
}

/**
 * Email sent to the CLIENT confirming their booking, with a cancel link.
 */
function send_booking_confirmation_email(array $booking, array $service, array $provider): bool {
    $when = date('l, F j, Y \a\t g:i A', strtotime($booking['start_datetime']));
    $cancelUrl = site_base_url() . '/public/cancel.php?token=' . urlencode($booking['cancel_token']);
    $businessName = $provider['business_name'] !== '' ? $provider['business_name'] : $provider['username'];

    $subject = "Booking confirmed with $businessName";
    $body =
        "Hi {$booking['client_name']},\n\n" .
        "Your booking is confirmed:\n\n" .
        "Service:  {$service['name']}\n" .
        "When:     $when\n" .
        "With:     $businessName\n\n" .
        "Need to cancel? Use this link:\n$cancelUrl\n\n" .
        "See you then!\n";

    return send_email(
        $booking['client_email'],
        $subject,
        $body,
        default_from_address(),
        'BookAppointment'
    );
}

/**
 * Email sent to the PROVIDER notifying them a new booking came in.
 */
function send_provider_notification_email(array $booking, array $service, array $provider): bool {
    $when = date('l, F j, Y \a\t g:i A', strtotime($booking['start_datetime']));

    $subject = "New booking: {$booking['client_name']} — {$service['name']}";
    $body =
        "You have a new booking:\n\n" .
        "Service:  {$service['name']}\n" .
        "When:     $when\n" .
        "Client:   {$booking['client_name']}\n" .
        "Email:    {$booking['client_email']}\n" .
        ($booking['client_phone'] !== '' ? "Phone:    {$booking['client_phone']}\n" : "") .
        "\nView your dashboard: " . site_base_url() . "/dashboard/bookings.php\n";

    return send_email(
        $provider['email'],
        $subject,
        $body,
        default_from_address(),
        'BookAppointment'
    );
}
