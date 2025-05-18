<?php
require 'config.php'; require 'auth.php'; require_role(['admin','consultant']);
$editing=isset($_GET['id']);
$client=['name'=>'','logo'=>'','notes'=>''];
if($editing){
  $st=$pdo->prepare("SELECT * FROM clients WHERE id=?"); $st->execute([$_GET['id']]);
  $client=$st->fetch(PDO::FETCH_ASSOC);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  if($editing){
    $u=$pdo->prepare("UPDATE clients SET name=?, logo=?, notes=? WHERE id=?");
    $u->execute([$_POST['name'],$_POST['logo'],$_POST['notes'],$_GET['id']]);
  }else{
    $i=$pdo->prepare("INSERT INTO clients(name,logo,notes) VALUES(?,?,?)");
    $i->execute([$_POST['name'],$_POST['logo'],$_POST['notes']]);
  }
  header('Location: clients.php'); exit;
}
?>
<!DOCTYPE html><html><head>
<meta charset="utf-8"><title><?= $editing?'Edit':'New' ?> Client</title>
<link rel="stylesheet" href="assets/css/style.css">
<script defer src="assets/js/app.js"></script>
</head><body>
<?php include 'partials/header.php'; ?>
<main class="container">
  <form class="form-grid" method="post">
    <div class="form-group">
      <label>Name</label>
      <input name="name" value="<?= htmlspecialchars($client['name']) ?>" required>
    </div>
    <div class="form-group">
      <label>Logo URL (optional)</label>
      <input name="logo" value="<?= htmlspecialchars($client['logo']) ?>">
    </div>
    <div class="form-group" style="grid-column:1/-1">
      <label>Notes</label>
      <textarea name="notes" rows="4"><?= htmlspecialchars($client['notes']) ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn"><?= $editing?'Save':'Create' ?></button>
      <button type="button" class="btn btn-danger" onclick="location='clients.php'">Cancel</button>
    </div>
  </form>
</main>
</body></html>
