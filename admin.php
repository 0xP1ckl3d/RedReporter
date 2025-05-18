<?php
// File: /var/www/redreporter2/admin.php
require 'config.php';
require 'csrf.php';
require 'auth.php';

// Only admins and consultants may access
require_role(['admin', 'consultant']);

$user = current_user();

/* --------------------------------------------------------------------------
 | Handle User‑management POST actions (Admins only)
   ------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'admin') {
    if (!verifyToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: admin.php');
        exit;
    }

    switch ($_POST['action'] ?? '') {
        case 'create_user':
            $u = trim($_POST['new_username'] ?? '');
            $p = $_POST['new_password'] ?? '';
            $r = $_POST['new_role'] ?? 'client';
            if ($u && $p && in_array($r, ['admin', 'consultant', 'client'], true)) {
                $hash = password_hash($p, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username,password_hash,role) VALUES (?,?,?)');
                $stmt->execute([$u, $hash, $r]);
                $_SESSION['message'] = "User '$u' created.";
            } else {
                $_SESSION['error'] = 'All user fields are required.';
            }
            break;

        case 'delete_user':
            $uid = (int)($_POST['user_id'] ?? 0);
            if ($uid && $uid !== $user['id']) {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
                $_SESSION['message'] = "User ID $uid deleted.";
            } else {
                $_SESSION['error'] = 'Cannot delete this user.';
            }
            break;
    }

    header('Location: admin.php');
    exit;
}

/* --------------------------------------------------------------------------
 | Fetch data for display
   ------------------------------------------------------------------------*/
$users = $pdo->query('SELECT id, username, role FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

$projects = $pdo->query(
    "SELECT p.id, p.name, c.name AS client_name, u.username AS creator
       FROM projects p
       LEFT JOIN clients c ON p.client_id = c.id
       LEFT JOIN users   u ON p.created_by = u.id
       ORDER BY p.id DESC"
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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Panel – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script defer src="assets/js/app.js"></script>
</head>
<body>
<?php $title='Admin Panel'; include 'partials/header.php'; ?>
<main class="container">
  <?php if ($message): ?><p style="color:green;"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <?php if ($error):   ?><p style="color:red;"  ><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <?php if ($user['role'] === 'admin'): ?>
  <!----------------------------- User Management -------------------------->
  <section>
    <h2>User Management</h2>
    <form method="post" class="form-grid">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="action" value="create_user">

      <div class="form-group">
        <label>Username</label>
        <input name="new_username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="new_password" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="new_role">
          <option value="client">Client</option>
          <option value="consultant">Consultant</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-actions">
        <button class="btn">Create User</button>
      </div>
    </form>

    <table>
      <thead><tr><th>ID</th><th>Username</th><th>Role</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= $u['role'] ?></td>
            <td>
              <?php if ($u['id'] !== $user['id']): ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                  <input type="hidden" name="action" value="delete_user">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button class="btn btn-danger" onclick="return confirm('Delete user?')">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endif; ?>

  <!--------------------------- Project Management ------------------------->
  <section style="margin-top:2rem;">
    <h2>Project Management</h2>
    <p>
      <a href="project_builder.php" class="btn">Create / Edit Projects</a>
      <a href="projects.php" class="btn">Open Project List</a>
      <a href="clients.php"  class="btn">Manage Clients</a>
    </p>

    <table>
      <thead><tr><th>ID</th><th>Name</th><th>Client</th><th>Created By</th></tr></thead>
      <tbody>
        <?php foreach ($projects as $p): ?>
          <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['client_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($p['creator'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
</body>
</html>
