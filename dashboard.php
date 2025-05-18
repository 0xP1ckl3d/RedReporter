<?php
require 'config.php';
require 'auth.php';
require_role(['admin','consultant','client']);

if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script defer src="assets/js/app.js"></script>
</head>
<body>

  <?php include 'partials/header.php'; ?>

  <main class="container dashboard-container">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
    <p>This is your dashboard.</p>
  </main>
</body>
</html>
