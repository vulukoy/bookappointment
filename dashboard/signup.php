<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_provider()) { header('Location: /dashboard/index.php'); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $err] = signup($_POST['username'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($ok) { header('Location: /dashboard/index.php'); exit; }
    $error = $err;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Sign Up — BookMe</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container" style="max-width:420px;">
  <div class="card">
    <h1>Create your booking page</h1>
    <p class="muted">Free to start. Takes 60 seconds.</p>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
      <label>Choose a username (this becomes your link)</label>
      <input type="text" name="username" placeholder="jane-hair" required>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required minlength="6">
      <button type="submit">Create my page</button>
    </form>
    <p class="muted" style="margin-top:16px;">Already have an account? <a href="/dashboard/login.php">Log in</a></p>
  </div>
</div>
</body>
</html>
