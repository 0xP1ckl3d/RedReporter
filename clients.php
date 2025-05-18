<?php
require 'config.php'; require 'auth.php'; require_role(['admin','consultant']);
$title='Clients'; $user=current_user();
?>
<!DOCTYPE html>
<html lang="en"><head>
  <meta charset="utf-8">
  <title><?= $title ?> – RedReporter2</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script defer src="assets/js/app.js"></script>  <!-- header & theme JS -->
</head>
<body>
<?php include 'partials/header.php'; ?>
<main class="container">
  <div class="header-actions">
    <h1><?= $title ?></h1>
    <button class="btn" onclick="location='client_builder.php'">New Client</button>
  </div>
  <table class="simple-table">
    <thead><tr><th>Name</th><th>Contacts</th><th></th></tr></thead>
    <tbody>
    <?php
      $rows=$pdo->query("SELECT id,name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
      foreach($rows as $c){
        $cnt=$pdo->prepare("SELECT COUNT(*) FROM client_contacts WHERE client_id=?");
        $cnt->execute([$c['id']]); $n=$cnt->fetchColumn();
        echo "<tr><td>{$c['name']}</td><td>{$n}</td>
              <td><a class='btn' href='client_builder.php?id={$c['id']}'>Edit</a></td></tr>";
      }
    ?>
    </tbody>
  </table>
</main>
</body></html>
