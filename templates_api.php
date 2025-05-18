<?php
// File: /var/www/redreporter2/templates_api.php
require 'config.php';
require 'auth.php';

// Only admins and consultants may access any of these endpoints
require_role(['admin','consultant']);

// Always return JSON
header('Content-Type: application/json');

// 1) Handle the toggle action
$action = $_REQUEST['action'] ?? '';
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_REQUEST['id'] ?? 0);
    // Fetch current flag
    $stmt = $pdo->prepare("SELECT is_disabled FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    if (!$row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success'=>false,'error'=>'Template not found']);
        exit;
    }
    // Flip it
    $newFlag = $row['is_disabled'] ? 0 : 1;
    $upd = $pdo->prepare(
        "UPDATE templates 
           SET is_disabled = ?, updated_by = ? 
         WHERE id = ?"
    );
    $upd->execute([$newFlag, current_user()['id'], $id]);
    // Return the new state
    echo json_encode(['success'=>true,'is_disabled'=>$newFlag]);
    exit;
}

// 2) Otherwise fall back to listing

// Pagination parameters
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = max(1, min(100, intval($_GET['per_page'] ?? 20)));
$offset  = ($page - 1) * $perPage;

// Non-admins only see enabled templates (optional)
$user  = current_user();
$where = '';
if ($user['role'] !== 'admin') {
    $where = 'WHERE t.is_disabled = 0';
}

// Total count
$totalSql = "SELECT COUNT(*) FROM templates t $where";
$total    = (int)$pdo->query($totalSql)->fetchColumn();

// Fetch the page
$dataSql = "
  SELECT 
    t.id,
    t.title,
    t.description,
    t.remediation,
    t.risk_rating,
    t.is_disabled,
    u1.username AS created_by,
    t.created_at,
    u2.username AS updated_by,
    t.updated_at
  FROM templates t
  JOIN users u1 ON t.created_by = u1.id
  JOIN users u2 ON t.updated_by = u2.id
  $where
  ORDER BY t.id DESC
  LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($dataSql);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build response
$response = [
    'page'      => $page,
    'per_page'  => $perPage,
    'total'     => $total,
    'has_more'  => ($offset + count($templates)) < $total,
    'templates' => $templates,
];

echo json_encode($response);
