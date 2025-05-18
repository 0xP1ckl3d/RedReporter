<?php
// /var/www/redreporter2/project_builder.php
require 'config.php'; require 'auth.php'; require_role(['admin','consultant']);

$editing = isset($_GET['id']);
$viewOnly = isset($_GET['view']);           // clients open in read-only
$template = [
  'name'=>'','client_id'=>'','scope_assets'=>'','executive_summary'=>'',
  'engagement_start'=>'','engagement_end'=>'','status'=>'planning'
];

if($editing){
  $stmt=$pdo->prepare("SELECT * FROM projects WHERE id=?"); $stmt->execute([$_GET['id']]);
  $template=$stmt->fetch(PDO::FETCH_ASSOC) ?: $template;
}
if($_SERVER['REQUEST_METHOD']==='POST' && !$viewOnly){
  // verify CSRF, sanitize, insert/update similar to template_builder.php
}

$clients = $pdo->query("SELECT id,name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head>
<meta charset="utf-8"><title><?= $editing?'Edit':'New' ?> Project</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<script defer src="assets/js/project_builder.js"></script>
<script defer src="assets/js/app.js"></script>
</head><body>
<?php include 'partials/header.php'; ?>
<main class="container">
  <form method="post" id="proj-form" class="form-grid" <?= $viewOnly?'style="pointer-events:none;opacity:.6"':'' ?>>
    <div class="form-group">
      <label>Client</label>
      <div style="display:flex;gap:0.5rem;align-items:center">
        <select name="client_id" required style="flex:1">
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$template['client_id']?'selected':'' ?>>
              <?= $c['name'] ?>
            </option>
          <?php endforeach; ?>
        </select>
        <a href="client_builder.php" target="_blank" class="btn" title="Add new client">+</a>
      </div>
    </div>
    <div class="form-group">
      <label>Project Name</label>
      <input name="name" value="<?= htmlspecialchars($template['name']) ?>" required>
    </div>

    <div class="form-group">
      <label>Engagement Start</label>
      <input type="date" name="engagement_start" value="<?= $template['engagement_start'] ?>">
    </div>

    <div class="form-group">
      <label>Engagement End</label>
      <input type="date" name="engagement_end" value="<?= $template['engagement_end'] ?>">
    </div>

    <div class="form-group" style="grid-column:1/-1">
      <label>Assets in Scope</label>
      <textarea name="scope_assets" rows="3"><?= htmlspecialchars($template['scope_assets']) ?></textarea>
    </div>

    <div class="form-group" style="grid-column:1/-1">
      <label>Executive Summary (Markdown)</label>
      <textarea id="exec-summary" name="executive_summary" rows="6"><?= htmlspecialchars($template['executive_summary']) ?></textarea>
    </div>

    <?php if(!$viewOnly): ?>
    <div class="form-actions">
      <button class="btn"><?= $editing?'Save':'Create' ?></button>
      <button type="button" class="btn btn-danger" onclick="location='projects.php'">Cancel</button>
    </div>
    <?php else: ?>
      <button type="button" class="btn" onclick="location='projects.php'">Back</button>
    <?php endif; ?>
  </form>
</main>
</body></html>
