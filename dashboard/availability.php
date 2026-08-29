<?php
require_once __DIR__ . '/../includes/auth.php';
$provider = require_login();

$days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// Save weekly hours (full replace — simple and predictable)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hours'])) {
    $pdo = db();
    $pdo->prepare('DELETE FROM availability WHERE provider_id = ?')->execute([$provider['id']]);
    $insert = $pdo->prepare('INSERT INTO availability (provider_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)');
    foreach ($days as $i => $label) {
        if (!empty($_POST["enabled_$i"])) {
            $start = $_POST["start_$i"] ?? '09:00';
            $end   = $_POST["end_$i"] ?? '17:00';
            if ($start < $end) {
                $insert->execute([$provider['id'], $i, $start, $end]);
            }
        }
    }
    header('Location: /dashboard/availability.php');
    exit;
}

// Add a blocked date (day off / vacation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_blocked'])) {
    $date = $_POST['blocked_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    if ($date) {
        $stmt = db()->prepare('INSERT INTO blocked_dates (provider_id, blocked_date, reason) VALUES (?, ?, ?)');
        $stmt->execute([$provider['id'], $date, $reason]);
    }
    header('Location: /dashboard/availability.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_blocked'])) {
    $stmt = db()->prepare('DELETE FROM blocked_dates WHERE id = ? AND provider_id = ?');
    $stmt->execute([(int)$_POST['delete_blocked'], $provider['id']]);
    header('Location: /dashboard/availability.php');
    exit;
}

$stmt = db()->prepare('SELECT * FROM availability WHERE provider_id = ?');
$stmt->execute([$provider['id']]);
$current = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $current[$row['day_of_week']] = $row;
}

$stmt = db()->prepare('SELECT * FROM blocked_dates WHERE provider_id = ? AND blocked_date >= CURDATE() ORDER BY blocked_date ASC');
$stmt->execute([$provider['id']]);
$blocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Availability — BookMe</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container">
  <div class="card">
    <h2>Weekly hours</h2>
    <form method="POST">
      <?php foreach ($days as $i => $label): $c = $current[$i] ?? null; ?>
        <div style="display:flex; align-items:center; gap:12px; padding:8px 0; border-bottom:1px solid #eee;">
          <label style="width:120px; margin:0;">
            <input type="checkbox" name="enabled_<?= $i ?>" <?= $c ? 'checked' : '' ?>> <?= $label ?>
          </label>
          <input type="time" name="start_<?= $i ?>" value="<?= $c['start_time'] ?? '09:00' ?>" style="width:120px;">
          <span class="muted">to</span>
          <input type="time" name="end_<?= $i ?>" value="<?= $c['end_time'] ?? '17:00' ?>" style="width:120px;">
        </div>
      <?php endforeach; ?>
      <button type="submit" name="save_hours" value="1">Save weekly hours</button>
    </form>
  </div>

  <div class="card">
    <h2>Block off a specific date</h2>
    <p class="muted">Vacation, holiday, or any day you're fully unavailable.</p>
    <form method="POST" style="display:flex; gap:10px; align-items:end;">
      <div style="flex:1;">
        <label>Date</label>
        <input type="date" name="blocked_date" required>
      </div>
      <div style="flex:1;">
        <label>Reason (optional)</label>
        <input type="text" name="reason" placeholder="Vacation">
      </div>
      <button type="submit" name="add_blocked" value="1" style="margin-top:0;">Block date</button>
    </form>

    <?php if ($blocked): ?>
      <table style="margin-top:16px;">
        <tr><th>Date</th><th>Reason</th><th></th></tr>
        <?php foreach ($blocked as $b): ?>
          <tr>
            <td><?= date('D, M j, Y', strtotime($b['blocked_date'])) ?></td>
            <td><?= htmlspecialchars($b['reason']) ?></td>
            <td>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="delete_blocked" value="<?= $b['id'] ?>">
                <button type="submit" class="secondary" style="margin:0;padding:6px 10px;font-size:0.8rem;">Remove</button>
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
