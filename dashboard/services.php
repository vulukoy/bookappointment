<?php
require_once __DIR__ . '/../includes/auth.php';
$provider = require_login();

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name'] ?? '');
    $duration = max(5, (int)($_POST['duration'] ?? 30));
    $price = (float)($_POST['price'] ?? 0);
    $buffer = max(0, (int)($_POST['buffer'] ?? 0));
    if ($name !== '') {
        $stmt = db()->prepare('INSERT INTO services (provider_id, name, duration_minutes, price, buffer_minutes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$provider['id'], $name, $duration, $price, $buffer]);
    }
    header('Location: /dashboard/services.php');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = db()->prepare('DELETE FROM services WHERE id = ? AND provider_id = ?');
    $stmt->execute([(int)$_POST['delete_id'], $provider['id']]);
    header('Location: /dashboard/services.php');
    exit;
}

// Handle toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $stmt = db()->prepare('UPDATE services SET active = 1 - active WHERE id = ? AND provider_id = ?');
    $stmt->execute([(int)$_POST['toggle_id'], $provider['id']]);
    header('Location: /dashboard/services.php');
    exit;
}

$stmt = db()->prepare('SELECT * FROM services WHERE provider_id = ? ORDER BY id ASC');
$stmt->execute([$provider['id']]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Services — BookAppointment.me</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container">
  <div class="card">
    <h2>Add a service</h2>
    <form method="POST">
      <label>Service name</label>
      <input type="text" name="name" placeholder="Haircut" required>
      <label>Duration (minutes)</label>
      <input type="number" name="duration" value="30" min="5" step="5" required>
      <label>Buffer time after appointment (minutes, optional)</label>
      <input type="number" name="buffer" value="0" min="0" step="5">
      <label>Price ($, optional — for display only)</label>
      <input type="number" name="price" value="0" min="0" step="0.01">
      <button type="submit" name="add" value="1">Add service</button>
    </form>
  </div>

  <div class="card">
    <h2>Your services</h2>
    <?php if (!$services): ?>
      <p class="muted">No services yet.</p>
    <?php else: ?>
      <table>
        <tr><th>Name</th><th>Duration</th><th>Buffer</th><th>Price</th><th>Status</th><th></th></tr>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= $s['duration_minutes'] ?> min</td>
            <td><?= $s['buffer_minutes'] ?> min</td>
            <td>$<?= number_format($s['price'], 2) ?></td>
            <td><?= $s['active'] ? '<span class="badge">Active</span>' : '<span class="muted">Hidden</span>' ?></td>
            <td style="white-space:nowrap;">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="toggle_id" value="<?= $s['id'] ?>">
                <button type="submit" class="secondary" style="margin:0;padding:6px 10px;font-size:0.8rem;"><?= $s['active'] ? 'Hide' : 'Show' ?></button>
              </form>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this service?');">
                <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                <button type="submit" class="danger" style="margin:0;padding:6px 10px;font-size:0.8rem;">Delete</button>
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
