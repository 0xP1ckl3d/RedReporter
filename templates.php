<?php
// File: /var/www/redreporter2/templates.php
require 'config.php';
require 'auth.php';

// Only admins and consultants may access
require_role(['admin','consultant']);

$title    = 'Finding Templates';
$userRole = current_user()['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?> – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- load marked BEFORE your own scripts, without defer -->
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script defer src="assets/js/app.js"></script>
  <script defer src="assets/js/templates.js"></script>
</head>
<!-- expose role to JS via data attribute -->
<body data-user-role="<?= htmlspecialchars($userRole) ?>">
  <?php include 'partials/header.php'; ?>

  <main class="container">
    <div class="header-actions">
      <h1><?= htmlspecialchars($title) ?></h1>
      <button id="create-template" class="btn">Create Template</button>
    </div>

    <section id="template-list" class="list-grid"></section>

    <div id="loading" style="display:none; text-align:center; margin:1rem;">
      Loading more templates...
    </div>
  </main>
</body>
</html>
