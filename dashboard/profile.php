<?php
require_once __DIR__ . '/../includes/auth.php';
$provider = require_login();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessName = trim($_POST['business_name'] ?? '');
    $bookingSlugInput = trim($_POST['booking_slug'] ?? '');
    $bookingSlug = booking_slugify($bookingSlugInput);

    if ($businessName === '') {
        $error = 'Business name is required.';
    } elseif (!preg_match('/^[a-z0-9-]{3,90}$/', $bookingSlugInput)) {
        $error = 'Booking page address must use 3-90 lowercase letters, numbers, or hyphens.';
    } elseif (!booking_slug_available($bookingSlug, (int)$provider['id'])) {
        $error = 'That booking page address is already taken.';
    } else {
        $stmt = db()->prepare('UPDATE providers SET business_name = ?, booking_slug = ? WHERE id = ?');
        $stmt->execute([$businessName, $bookingSlug, $provider['id']]);
        header('Location: /dashboard/profile.php?updated=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Profile — BookAppointment.me</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container" style="max-width:620px;">
  <div class="card">
    <h1>Business profile</h1>
    <?php if (isset($_GET['updated'])): ?><p class="success-box">Profile updated.</p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
      <label>Business name</label>
      <input type="text" name="business_name" value="<?= htmlspecialchars($_POST['business_name'] ?? $provider['business_name']) ?>" required>
      <label>Booking page address</label>
      <input type="text" name="booking_slug" value="<?= htmlspecialchars($_POST['booking_slug'] ?? $provider['booking_slug']) ?>" pattern="[a-z0-9-]{3,90}" required>
      <p class="muted">Use lowercase letters, numbers, and hyphens. Your booking page will be /book/<?= htmlspecialchars($_POST['booking_slug'] ?? $provider['booking_slug']) ?>.</p>
      <button type="submit">Save profile</button>
    </form>
  </div>
</div>
</body>
</html>
