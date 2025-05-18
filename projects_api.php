<?php
require 'config.php'; require 'auth.php'; require_role(['admin','consultant','client']);
header('Content-Type: application/json');

$user = current_user();
$page = max(1,(int)($_GET['page']??1)); $per = 20; $off=($page-1)*$per;

// BEFORE building $sql
$where = '';
$params = [];

if ($user['role'] !== 'admin') {
    $where  = 'JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ?';
    $params[] = $user['id'];
}

// pagination & optional membership filter are set above …

$sql = "
  SELECT p.*, c.name AS client_name
  FROM projects p
  JOIN clients c ON p.client_id = c.id
  $where
  ORDER BY p.id DESC
  LIMIT ? OFFSET ?
";

$params[] = $per;   // not $perPage
$params[] = $off;   // not $offset

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'projects'  => $rows,
  'has_more'  => count($rows) == $per
]);
