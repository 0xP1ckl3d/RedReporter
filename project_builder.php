<?php
// File: /var/www/redreporter2/project_builder.php
require 'config.php';
require 'csrf.php';
require 'auth.php';

// only admins & consultants may reach this page
require_role(['admin', 'consultant']);

$user      = current_user();
$editing   = isset($_GET['id']);
$viewOnly  = isset($_GET['view']);               // read-only mode for clients
$projectId = $editing ? (int)$_GET['id'] : null;

/* --------------------------------------------------------------------------
 | Default values
 ------------------------------------------------------------------------- */
$data = [
  'name'              => '',
  'client_id'         => '',
  'scope_assets'      => '',
  'executive_summary' => '',
  'engagement_start'  => '',
  'engagement_end'    => '',
  'status'            => 'planning'
];

/* --------------------------------------------------------------------------
 | Load existing project (if editing)
 ------------------------------------------------------------------------- */
if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$projectId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: $data;
}

/* --------------------------------------------------------------------------
 | Handle POST (create / update)
 ------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$viewOnly) {

    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: project_builder.php' . ($editing ? "?id=$projectId" : ''));
        exit;
    }

    /* collect & sanitise */
    $clientId  = (int)($_POST['client_id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');
    $assets    = trim($_POST['scope_assets'] ?? '');
    $start     = $_POST['engagement_start'] ?? null;
    $end       = $_POST['engagement_end']   ?? null;
    $summaryMd = trim($_POST['executive_summary'] ?? '');
    $status    = $_POST['status'] ?? 'planning';

    if (!$clientId || $name === '') {
        $_SESSION['error'] = 'Client and project name are required.';
        header('Location: project_builder.php' . ($editing ? "?id=$projectId" : ''));
        exit;
    }

    if ($editing) {
        /* ---------- update ---------- */
        $upd = $pdo->prepare("
          UPDATE projects SET
            client_id = ?, name = ?, scope_assets = ?, engagement_start = ?,
            engagement_end = ?, executive_summary = ?, status = ?
          WHERE id = ?
        ");
        $upd->execute([
            $clientId, $name, $assets ?: null,
            $start ?: null, $end ?: null,
            $summaryMd, $status, $projectId
        ]);
        $_SESSION['message'] = 'Project updated.';
        header("Location: project_builder.php?id=$projectId");
        exit;

    } else {
        /* ---------- create ---------- */
        $ins = $pdo->prepare("
          INSERT INTO projects
            (client_id, name, scope_assets, engagement_start, engagement_end,
             executive_summary, status, created_by)
          VALUES (?,?,?,?,?,?,?,?)
        ");
        $ins->execute([
            $clientId, $name, $assets ?: null,
            $start ?: null, $end ?: null,
            $summaryMd, $status, $user['id']
        ]);
        $projectId = $pdo->lastInsertId();

        /* add creator as lead in project_members */
        $pdo->prepare("
          INSERT INTO project_members (project_id, user_id, role_in_project)
          VALUES (?,?, 'lead')
        ")->execute([$projectId, $user['id']]);

        $_SESSION['message'] = 'Project created.';
        header("Location: project_builder.php?id=$projectId");
        exit;
    }
}

/* --------------------------------------------------------------------------
 | Support data for the form
 ------------------------------------------------------------------------- */
$clients  = $pdo->query('SELECT id,name FROM clients ORDER BY name')
                ->fetchAll(PDO::FETCH_ASSOC);
$csrfTok  = generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= $editing ? 'Edit' : 'New' ?> Project – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
  <script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
  <script defer src="assets/js/project_builder.js"></script>
  <script defer src="assets/js/app.js"></script>
</head>
<body>
<?php include 'partials/header.php'; ?>
<main class="container">

  <?php if (!empty($_SESSION['message'])): ?>
    <p style="color:green;"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
  <?php elseif (!empty($_SESSION['error'])): ?>
    <p style="color:red;"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
  <?php endif; ?>

  <form method="post" class="form-grid" <?= $viewOnly ? 'style="pointer-events:none;opacity:.6"' : '' ?>>
    <input type="hidden" name="csrf_token" value="<?= $csrfTok ?>">

    <div class="form-group">
      <label>Client</label>
      <div style="display:flex;gap:.5rem;align-items:center">
        <select name="client_id" required style="flex:1">
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$data['client_id']?'selected':'' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <a href="client_builder.php" target="_blank" class="btn" title="Add new client">+</a>
      </div>
    </div>

    <div class="form-group">
      <label>Project Name</label>
      <input name="name" value="<?= htmlspecialchars($data['name']) ?>" required>
    </div>

    <div class="form-group">
      <label>Engagement Start</label>
      <input type="date" name="engagement_start" value="<?= $data['engagement_start'] ?>">
    </div>

    <div class="form-group">
      <label>Engagement End</label>
      <input type="date" name="engagement_end" value="<?= $data['engagement_end'] ?>">
    </div>

    <div class="form-group" style="grid-column:1/-1">
      <label>Assets in Scope</label>
      <textarea name="scope_assets" rows="3"><?= htmlspecialchars($data['scope_assets']) ?></textarea>
    </div>

    <div class="form-group" style="grid-column:1/-1">
      <label>Executive Summary (Markdown)</label>
      <textarea id="exec-summary" name="executive_summary" rows="6"><?= htmlspecialchars($data['executive_summary']) ?></textarea>
    </div>

    <?php if (!$viewOnly): ?>
      <div class="form-actions">
        <button class="btn"><?= $editing ? 'Save' : 'Create' ?></button>
        <button type="button" class="btn btn-danger" onclick="location='projects.php'">Cancel</button>
      </div>
    <?php else: ?>
        <button type="button" class="btn" onclick="location='projects.php'">Back</button>
    <?php endif; ?>
  </form>
</main>
</body>
</html>
