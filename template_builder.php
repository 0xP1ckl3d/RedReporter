<?php
// File: /var/www/redreporter2/template_builder.php
require 'config.php';
require 'csrf.php';
require 'auth.php';

// Only admins and consultants may access
require_role(['admin','consultant']);

// Determine if editing existing template
$editing = isset($_GET['id']);
$template = [
    'title'       => '',
    'description' => '',
    'remediation' => '',
    'risk_rating' => 'Medium',
];

if ($editing) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        header('Location: templates.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request.';
        header('Location: template_builder.php' . ($editing?"?id={$id}":''));
        exit;
    }

    // Gather input
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $remediation = trim($_POST['remediation'] ?? '');
    $risk        = $_POST['risk_rating'] ?? 'Medium';

    // Validate
    if ($title === '' || $description === '' || $remediation === ''
        || !in_array($risk, ['Critical','High','Medium','Low','Informational'], true)) {
        $_SESSION['error'] = 'All fields are required and must be valid.';
    } else {
        $userId = current_user()['id'];
        if ($editing) {
            // Update existing
            $upd = $pdo->prepare(
                'UPDATE templates 
                    SET title=?, description=?, remediation=?, risk_rating=?, updated_by=? 
                  WHERE id = ?'
            );
            $upd->execute([$title, $description, $remediation, $risk, $userId, $id]);
            $_SESSION['message'] = 'Template updated.';
        } else {
            // Create new
            $ins = $pdo->prepare(
                'INSERT INTO templates (
                    title,
                    description,
                    remediation,
                    risk_rating,
                    created_by,
                    updated_by
                ) VALUES (?, ?, ?, ?, ?, ?)'
            );
            try {
                $ins->execute([$title, $description, $remediation, $risk, $userId, $userId]);
            } catch (PDOException $e) {
                exit('SQL Error: '.htmlspecialchars($e->getMessage()));
            }
            $_SESSION['message'] = 'Template created.';
            $id = $pdo->lastInsertId();
        }
        header('Location: template_builder.php?id=' . $id);
        exit;
    }

    // On failure, repopulate form
    $template = [
        'title'       => $_POST['title'],
        'description' => $_POST['description'],
        'remediation' => $_POST['remediation'],
        'risk_rating' => $_POST['risk_rating'],
    ];
}

// Flash messages
$message = $_SESSION['message'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$csrf_token = generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $editing ? 'Edit' : 'Create' ?> Template – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
  <script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
  <script defer src="assets/js/app.js"></script>
  <script defer src="assets/js/editor.js"></script>
</head>
<body>
  <?php
    $title = $editing ? 'Edit Template' : 'Create Template';
    include 'partials/header.php';
  ?>
  <main class="container">
    <?php if ($message): ?>
      <p style="color:green;"><?= htmlspecialchars($message) ?></p>
    <?php elseif ($error): ?>
      <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Add novalidate and an ID for JS hook -->
    <form method="post" novalidate id="template-form">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title"
               value="<?= htmlspecialchars($template['title']) ?>" required>
      </div>

      <div class="form-group">
        <label for="description">Description (Markdown)</label>
        <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($template['description']) ?></textarea>
      </div>

      <div class="form-group">
        <label for="remediation">Remediation (Markdown)</label>
        <textarea id="remediation" name="remediation" rows="4" required><?= htmlspecialchars($template['remediation']) ?></textarea>
      </div>

      <div class="form-group">
        <label for="risk_rating">Risk Rating</label>
        <select id="risk_rating" name="risk_rating" class="risk-<?= strtolower($template['risk_rating']) ?>">
          <?php foreach (['Critical','High','Medium','Low','Informational'] as $r): ?>
            <option value="<?= $r ?>" <?= $template['risk_rating'] === $r ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group form-actions">
        <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Create Template' ?></button>
        <button type="button" class="btn btn-danger" onclick="location.href='templates.php'">Cancel</button>
      </div>
    </form>
  </main>
</body>
</html>
