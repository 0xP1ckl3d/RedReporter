<?php
// File: /var/www/redreporter2/projects.php
require 'config.php';
require 'auth.php';
require_role(['admin','consultant','client']);

$title = 'Projects';
$user  = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= $title ?> – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script defer src="assets/js/projects.js"></script>
  <script defer src="assets/js/app.js"></script>
</head>
<body data-user-id="<?= $user['id'] ?>" data-user-role="<?= $user['role'] ?>">
<?php include 'partials/header.php'; ?>
<main class="container">
  <div class="header-actions">
    <h1><?= $title ?></h1>
    <?php if (in_array($user['role'], ['admin','consultant'])): ?>
      <button id="create-project" class="btn">Create Project</button>
    <?php endif; ?>
  </div>

  <section id="project-list" class="list-grid"></section>
  <div id="loading" style="display:none;text-align:center;margin:1rem;">Loading…</div>
</main>
</body>
</html>
