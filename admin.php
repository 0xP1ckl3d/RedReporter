<?php
// File: /var/www/redreporter2/admin.php
require 'config.php';
require 'csrf.php';
require 'auth.php';

// Only admins and consultants may access
require_role(['admin','consultant']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_user':
            if (current_user()['role'] === 'admin' && verifyToken($_POST['csrf_token'])) {
                $newUser = trim($_POST['new_username'] ?? '');
                $newPass = $_POST['new_password'] ?? '';
                $newRole = $_POST['new_role'] ?? 'client';
                if ($newUser && $newPass && in_array($newRole, ['admin','consultant','client'], true)) {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username,password_hash,role) VALUES (?, ?, ?)");
                    $stmt->execute([$newUser, $hash, $newRole]);
                    $_SESSION['message'] = "User '{$newUser}' created.";
                } else {
                    $_SESSION['error'] = 'All user fields are required.';
                }
            }
            break;

        case 'delete_user':
            if (current_user()['role'] === 'admin' && verifyToken($_POST['csrf_token'])) {
                $uid = intval($_POST['user_id']);
                if ($uid !== current_user()['id']) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$uid]);
                    $_SESSION['message'] = "User ID {$uid} deleted.";
                } else {
                    $_SESSION['error'] = 'Cannot delete yourself.';
                }
            }
            break;

        case 'create_project':
            if (verifyToken($_POST['csrf_token'])) {
                $projName = trim($_POST['project_name'] ?? '');
                if ($projName) {
                    $owner = (current_user()['role'] === 'admin')
                        ? intval($_POST['owner_id'] ?? current_user()['id'])
                        : current_user()['id'];
                    $stmt = $pdo->prepare("INSERT INTO projects (name, owner_id) VALUES (?, ?)");
                    $stmt->execute([$projName, $owner]);
                    $_SESSION['message'] = "Project '{$projName}' created.";
                } else {
                    $_SESSION['error'] = 'Project name is required.';
                }
            }
            break;

        case 'delete_project':
            if (verifyToken($_POST['csrf_token'])) {
                $pid = intval($_POST['project_id']);
                $user = current_user();
                if ($user['role'] === 'consultant') {
                    $chk = $pdo->prepare("SELECT owner_id FROM projects WHERE id = ?");
                    $chk->execute([$pid]);
                    $row = $chk->fetch(PDO::FETCH_ASSOC);
                    if (!$row || $row['owner_id'] !== $user['id']) {
                        $_SESSION['error'] = 'Forbidden.';
                        break;
                    }
                }
                $del = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                $del->execute([$pid]);
                $_SESSION['message'] = "Project ID {$pid} deleted.";
            }
            break;
    }

    header('Location: admin.php');
    exit;
}

// Fetch data for display
$users = $pdo->query("SELECT id, username, role FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$projects = $pdo->query(
    "SELECT p.id, p.name, p.owner_id, u.username AS owner_name
     FROM projects p
     JOIN users u ON p.owner_id = u.id
     ORDER BY p.id"
)->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = generateToken();
$message    = $_SESSION['message'] ?? '';
$error      = $_SESSION['error']   ?? '';
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script defer src="assets/js/app.js"></script>
</head>
<body>
  <?php
    $title = 'Admin Panel';
    include 'partials/header.php';
  ?>

  <main class="container">
    <?php if ($message): ?>
      <p style="color:green;"><?= htmlspecialchars($message) ?></p>
    <?php elseif ($error): ?>
      <p style="color:red;"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>

    <?php if (current_user()['role'] === 'admin'): ?>
      <section>
        <h2>User Management</h2>
        <form method="post" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="action" value="create_user">

    <div class="form-group">
        <label for="new_username">Username</label>
        <input type="text" id="new_username" name="new_username" required>
    </div>

    <div class="form-group">
        <label for="new_password">Password</label>
        <div class="input-with-toggle">
            <input type="password" id="new_password" name="new_password" required>
            <button type="button" class="toggle-password" data-toggle="new_password">Show</button>
        </div>
    </div>

    <div class="form-group">
        <label for="new_role">Role</label>
        <select id="new_role" name="new_role">
            <option value="client">Client</option>
            <option value="consultant">Consultant</option>
            <option value="admin">Admin</option>
        </select>
    </div>

    <div class="form-group form-actions">
        <button type="submit" class="btn">Create User</button>
    </div>
</form>

        <table>
          <thead>
            <tr><th>ID</th><th>Username</th><th>Role</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= $u['role'] ?></td>
                <td>
                  <?php if ($u['id'] !== current_user()['id']): ?>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php endif; ?>

    <section>
      <h2>Project Management</h2>
      <form method="post" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="action" value="create_project">

    <div class="form-group">
        <label for="project_name">Project Name</label>
        <input type="text" id="project_name" name="project_name" required>
    </div>

    <?php if (current_user()['role'] === 'admin'): ?>
    <div class="form-group">
        <label for="owner_id">Owner</n        </label>
        <select id="owner_id" name="owner_id">
            <?php foreach ($users as $u): ?>
                <?php if ($u['role'] !== 'client'): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="form-group form-actions">
        <button type="submit" class="btn">Create Project</button>
    </div>
</form>

      <table>
        <thead>
          <tr><th>ID</th><th>Name</th><th>Owner</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $p): ?>
            <?php $me = current_user();
                  $canDelete = $me['role'] === 'admin' 
                               || ($me['role'] === 'consultant' && $p['owner_id'] === $me['id']); ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['owner_name']) ?></td>
              <td>
                <?php if ($canDelete): ?>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="delete_project">
                    <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                    <button type="submit">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
