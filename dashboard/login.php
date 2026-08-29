<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_provider()) { header('Location: /dashboard/index.php'); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /dashboard/index.php'); exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Log In — BookMe</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container" style="max-width:420px;">
  <div class="card">
    <h1>Log in</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit">Log in</button>
    </form>
    <p class="muted" style="margin-top:16px;">No account? <a href="/dashboard/signup.php">Sign up</a></p>
  </div>
</div>
</body>
</html>
