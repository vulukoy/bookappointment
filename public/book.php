<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/slots.php';

// AJAX endpoint: return available slots for a given service + date
if (isset($_GET['ajax']) && $_GET['ajax'] === 'slots') {
    header('Content-Type: application/json');
    $username = $_GET['u'] ?? '';
    $stmt = db()->prepare('SELECT id FROM providers WHERE username = ?');
    $stmt->execute([$username]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$provider) { echo json_encode(['error' => 'not found']); exit; }

    $serviceId = (int)($_GET['service_id'] ?? 0);
    $date = $_GET['date'] ?? '';
    $slots = get_available_slots($provider['id'], $serviceId, $date);
    echo json_encode(['slots' => $slots]);
    exit;
}

// Handle booking submission
$bookingResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['u'] ?? '';
    $stmt = db()->prepare('SELECT id FROM providers WHERE username = ?');
    $stmt->execute([$username]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($p) {
        [$ok, $msg, $token] = create_booking(
            $p['id'],
            (int)$_POST['service_id'],
            $_POST['date'],
            $_POST['time'],
            trim($_POST['name']),
            trim($_POST['email']),
            trim($_POST['phone'] ?? '')
        );
        $bookingResult = ['ok' => $ok, 'msg' => $msg, 'token' => $token];
    }
}

$username = $_GET['u'] ?? ($_POST['u'] ?? '');
$stmt = db()->prepare('SELECT * FROM providers WHERE username = ?');
$stmt->execute([$username]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
    http_response_code(404);
    echo "<h1>Page not found</h1><p>No provider with that link.</p>";
    exit;
}

$stmt = db()->prepare('SELECT * FROM services WHERE provider_id = ? AND active = 1 ORDER BY id ASC');
$stmt->execute([$provider['id']]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($provider['business_name']) ?> — Book an appointment</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container">

  <?php if ($bookingResult): ?>
    <div class="card">
      <?php if ($bookingResult['ok']): ?>
        <div class="success-box">
          ✅ <?= htmlspecialchars($bookingResult['msg']) ?><br>
          A confirmation has been sent. You can cancel anytime using
          <a href="/public/cancel.php?token=<?= htmlspecialchars($bookingResult['token']) ?>">this link</a>.
        </div>
        <a href="/public/book.php?u=<?= urlencode($username) ?>" class="btn secondary">Book another appointment</a>
      <?php else: ?>
        <p class="error"><?= htmlspecialchars($bookingResult['msg']) ?></p>
        <a href="/public/book.php?u=<?= urlencode($username) ?>" class="btn secondary">Try again</a>
      <?php endif; ?>
    </div>
  <?php else: ?>

  <div class="card">
    <h1><?= htmlspecialchars($provider['business_name']) ?></h1>
    <?php if ($provider['bio']): ?><p class="muted"><?= htmlspecialchars($provider['bio']) ?></p><?php endif; ?>
  </div>

  <div class="card">
    <h2>1. Choose a service</h2>
    <?php if (!$services): ?>
      <p class="muted">No services available right now.</p>
    <?php else: ?>
      <?php foreach ($services as $s): ?>
        <div class="service-option" data-id="<?= $s['id'] ?>" data-duration="<?= $s['duration_minutes'] ?>" onclick="selectService(this)">
          <div class="name"><?= htmlspecialchars($s['name']) ?></div>
          <div class="meta"><?= $s['duration_minutes'] ?> min <?= $s['price'] > 0 ? '· $' . number_format($s['price'], 2) : '' ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card" id="date-card" style="display:none;">
    <h2>2. Choose a date &amp; time</h2>
    <div class="day-nav">
      <button type="button" class="secondary" onclick="changeDay(-1)">‹ Prev</button>
      <strong id="date-label"></strong>
      <button type="button" class="secondary" onclick="changeDay(1)">Next ›</button>
    </div>
    <div id="slots" class="slots-grid"><p class="muted">Pick a service first.</p></div>
  </div>

  <div class="card" id="details-card" style="display:none;">
    <h2>3. Your details</h2>
    <form method="POST" id="booking-form" action="/public/book.php?u=<?= urlencode($username) ?>">
      <input type="hidden" name="u" value="<?= htmlspecialchars($username) ?>">
      <input type="hidden" name="service_id" id="form-service-id">
      <input type="hidden" name="date" id="form-date">
      <input type="hidden" name="time" id="form-time">
      <label>Name</label>
      <input type="text" name="name" required>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Phone (optional)</label>
      <input type="tel" name="phone">
      <button type="submit">Confirm booking</button>
    </form>
  </div>

  <?php endif; ?>

  <p class="footer-badge">Powered by <a href="/">BookMe</a></p>
</div>

<script>
const username = <?= json_encode($username) ?>;
let selectedServiceId = null, selectedDuration = null, selectedTime = null;
let currentDate = new Date();

function selectService(el) {
  document.querySelectorAll('.service-option').forEach(e => e.classList.remove('selected'));
  el.classList.add('selected');
  selectedServiceId = el.dataset.id;
  selectedDuration = el.dataset.duration;
  document.getElementById('date-card').style.display = 'block';
  document.getElementById('details-card').style.display = 'none';
  loadSlots();
}

function fmtDate(d) {
  return d.toISOString().slice(0, 10);
}

function changeDay(delta) {
  currentDate.setDate(currentDate.getDate() + delta);
  loadSlots();
}

function loadSlots() {
  if (!selectedServiceId) return;
  const dateStr = fmtDate(currentDate);
  document.getElementById('date-label').textContent = currentDate.toDateString();
  const slotsEl = document.getElementById('slots');
  slotsEl.innerHTML = '<p class="muted">Loading...</p>';

  fetch(`/public/book.php?ajax=slots&u=${encodeURIComponent(username)}&service_id=${selectedServiceId}&date=${dateStr}`)
    .then(r => r.json())
    .then(data => {
      const slots = data.slots || [];
      if (slots.length === 0) {
        slotsEl.innerHTML = '<p class="muted">No open slots this day. Try another day.</p>';
        return;
      }
      slotsEl.innerHTML = '';
      slots.forEach(t => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'slot-btn';
        btn.textContent = to12h(t);
        btn.onclick = () => selectSlot(t, btn);
        slotsEl.appendChild(btn);
      });
    });
}

function to12h(t) {
  let [h, m] = t.split(':').map(Number);
  const ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  return `${h}:${m.toString().padStart(2,'0')} ${ampm}`;
}

function selectSlot(time, btn) {
  document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  selectedTime = time;
  document.getElementById('form-service-id').value = selectedServiceId;
  document.getElementById('form-date').value = fmtDate(currentDate);
  document.getElementById('form-time').value = selectedTime;
  document.getElementById('details-card').style.display = 'block';
  document.getElementById('details-card').scrollIntoView({behavior:'smooth'});
}
</script>
</body>
</html>
