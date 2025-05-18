<?php
// File: /var/www/redreporter2/profile.php
require 'config.php';
require 'csrf.php';
require 'auth.php';

// Only logged-in users
require_role(['admin','consultant','client']);

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    if (!verifyToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request.';
        header('Location: profile.php');
        exit;
    }

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $_SESSION['error'] = 'All password fields are required.';
    } elseif ($new !== $confirm) {
        $_SESSION['error'] = 'New passwords do not match.';
    } else {
        // Fetch current hash
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([current_user()['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $_SESSION['error'] = 'Current password is incorrect.';
        } elseif (password_verify($new, $row['password_hash'])) {
            $_SESSION['error'] = 'New password must differ from your current password.';
        } else {
            // Update password
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([$hash, current_user()['id']]);
            $_SESSION['message'] = 'Password updated successfully.';
        }
    }

    header('Location: profile.php');
    exit;
}

$message  = $_SESSION['message'] ?? '';
$error    = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Fetch assigned projects
$assignStmt = $pdo->prepare(
    'SELECT p.id, p.name
       FROM project_assignments pa
       JOIN projects p ON pa.project_id = p.id
      WHERE pa.user_id = ?'
);
$assignStmt->execute([current_user()['id']]);
$assigned = $assignStmt->fetchAll(PDO::FETCH_ASSOC);

// For admins and consultants, also include projects they own
if (in_array(current_user()['role'], ['admin','consultant'], true)) {
    $ownStmt = $pdo->prepare(
        'SELECT id, name
           FROM projects
          WHERE owner_id = ?'
    );
    $ownStmt->execute([current_user()['id']]);
    $owned = $ownStmt->fetchAll(PDO::FETCH_ASSOC);
    // Merge owned projects, avoiding duplicates
    foreach ($owned as $proj) {
        $found = false;
        foreach ($assigned as $a) {
            if ($a['id'] === $proj['id']) {
                $found = true;
                break;
            }
        }
        if (! $found) {
            $assigned[] = $proj;
        }
    }
}

$csrf_token = generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script defer src="assets/js/app.js"></script>
</head>
<body>
  <?php
    $title = 'Profile';
    $showMenu = true;
    include 'partials/header.php';
  ?>

  <main class="container">
    <?php if ($message): ?>
      <p style="color:green;"><?= htmlspecialchars($message) ?></p>
    <?php elseif ($error): ?>
      <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <section>
      <h2>Your Details</h2>
      <p><strong>Username:</strong> <?= htmlspecialchars(current_user()['username']) ?></p>
      <p><strong>Role:</strong> <?= htmlspecialchars(current_user()['role']) ?></p>
    </section>

    <section>
      <h2>Assigned Projects</h2>
      <?php if ($assigned): ?>
        <ul>
          <?php foreach ($assigned as $p): ?>
            <li><?= htmlspecialchars($p['name']) ?> (ID: <?= $p['id'] ?>)</li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No projects assigned.</p>
      <?php endif; ?>
    </section>

    <section>
      <h2>Reset Password</h2>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="action" value="update_password">

        <div class="form-group">
          <label for="current_password">Current Password</label>
          <div class="input-with-toggle">
            <input type="password" id="current_password" name="current_password" required>
            <button type="button" class="toggle-password" data-toggle="current_password">Show</button>
          </div>
        </div>

        <div class="form-group">
          <label for="new_password">New Password</label>
          <div class="input-with-toggle">
            <input type="password" id="new_password" name="new_password" required>
            <button type="button" class="toggle-password" data-toggle="new_password">Show</button>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <div class="input-with-toggle">
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="button" class="toggle-password" data-toggle="confirm_password">Show</button>
          </div>
        </div>

        <div class="form-group form-actions">
          <button type="submit" class="btn">Update Password</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
