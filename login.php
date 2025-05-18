<?php
require 'config.php';
require 'csrf.php';

// Redirect already-logged-in users
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$title      = 'Login';
$showMenu   = false;                // hide burger/menu on this page
$csrf_token = generateToken();
$error      = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script defer src="assets/js/app.js"></script>
</head>
<body>
  <?php include 'partials/header.php'; ?>

  <main class="container">
    <?php if ($error): ?>
      <p style="color:#e74c3c;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="authenticate.php" class="form-grid login-form">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required maxlength="50">
      </div>

      <div class="form-group">
        <label for="login_password">Password</label>
        <div class="input-with-toggle">
          <input type="password" id="login_password" name="password" required>
          <button type="button" class="toggle-password" data-toggle="login_password">Show</button>
        </div>
      </div>

      <div class="form-group form-actions">
        <button type="submit" class="btn">Log in</button>
      </div>
    </form>
  </main>
</body>
</html>
