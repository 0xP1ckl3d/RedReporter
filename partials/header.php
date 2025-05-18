<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?>
<header>
  <div class="header-container">
    <div class="header-left">
      <?php if ($showMenu ?? true): ?>
        <button id="burger" class="burger" aria-label="Menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <nav id="sidebar" class="sidebar">
          <ul>
            <li><a href="dashboard.php">Home</a></li>
            <?php if (in_array(current_user()['role'], ['admin','consultant'], true)): ?>
              <li><a href="admin.php">Admin Panel</a></li>
              <li><a href="templates.php">Templates</a></li>
              <li><a href="clients.php">Clients</a></li>
            <?php endif; ?>
            <li><a href="projects.php">Projects</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Log out</a></li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
    <h1><?= htmlspecialchars($title ?? 'RedReporter2') ?></h1>
    <div class="header-right">
      <button id="theme-toggle" aria-label="Toggle theme">🌙</button>
    </div>
  </div>
</header>