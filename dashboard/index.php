<?php
require_once __DIR__ . '/../includes/auth.php';
$provider = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $stmt = db()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND provider_id = ?");
    $stmt->execute([(int)$_POST['cancel_id'], $provider['id']]);
    header('Location: /dashboard/index.php');
    exit;
}

$stmt = db()->prepare("
    SELECT b.*, s.name AS service_name, s.duration_minutes
    FROM bookings b
    JOIN services s ON s.id = b.service_id
    WHERE b.provider_id = ? AND b.status = 'confirmed' AND b.start_datetime >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY b.start_datetime ASC
");
$stmt->execute([$provider['id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bookLink = "/public/book.php?u=" . urlencode($provider['username']);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dashboard — BookMe</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container">
  <div class="card">
    <h1>Your booking page</h1>
    <p class="muted">Share this link with clients:</p>
    <input readonly value="<?= htmlspecialchars(($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . $bookLink) ?>" onclick="this.select()">
  </div>

  <div class="card">
    <h2>Upcoming bookings</h2>
    <?php if (!$bookings): ?>
      <p class="muted">No upcoming bookings yet. Once you share your link, bookings will show up here.</p>
    <?php else: ?>
      <table>
        <tr><th>When</th><th>Service</th><th>Client</th><th>Contact</th><th></th></tr>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= date('D, M j · g:i A', strtotime($b['start_datetime'])) ?></td>
            <td><?= htmlspecialchars($b['service_name']) ?> (<?= $b['duration_minutes'] ?>m)</td>
            <td><?= htmlspecialchars($b['client_name']) ?></td>
            <td><?= htmlspecialchars($b['client_email']) ?><?= $b['client_phone'] ? ' · ' . htmlspecialchars($b['client_phone']) : '' ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Cancel this booking?');" style="margin:0;">
                <input type="hidden" name="cancel_id" value="<?= $b['id'] ?>">
                <button type="submit" class="danger" style="margin:0;padding:6px 12px;font-size:0.8rem;">Cancel</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
