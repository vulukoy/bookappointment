<?php
require_once __DIR__ . '/../includes/db.php';

$token = $_GET['token'] ?? '';
$stmt = db()->prepare("
    SELECT b.*, s.name AS service_name, p.business_name, p.username, p.booking_slug
    FROM bookings b
    JOIN services s ON s.id = b.service_id
    JOIN providers p ON p.id = b.provider_id
    WHERE b.cancel_token = ?
");
$stmt->execute([$token]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

$cancelled = false;
if ($booking && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $upd = db()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $upd->execute([$booking['id']]);
    $cancelled = true;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Cancel booking</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container" style="max-width:480px;">
  <div class="card">
    <?php if (!$booking): ?>
      <p class="error">Booking not found or link is invalid.</p>
    <?php elseif ($cancelled || $booking['status'] === 'cancelled'): ?>
      <div class="success-box">Your booking has been cancelled.</div>
      <a href="/book/<?= rawurlencode($booking['booking_slug'] ?: $booking['username']) ?>" class="btn secondary">Book a new appointment</a>
    <?php else: ?>
      <h1>Cancel this booking?</h1>
      <p><strong><?= htmlspecialchars($booking['service_name']) ?></strong> with <?= htmlspecialchars($booking['business_name']) ?></p>
      <p class="muted"><?= date('l, F j, Y \a\t g:i A', strtotime($booking['start_datetime'])) ?></p>
      <form method="POST">
        <button type="submit" class="danger">Yes, cancel my booking</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
