<?php
require 'config.php';
require 'auth.php';
require_role(['admin','consultant','client']);
header('Content-Type: application/json');

try {

    $user = current_user();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = 20;
    $off  = ($page - 1) * $per;

    $where  = '';
    $params = [];

    if ($user['role'] !== 'admin') {
        $where   = 'JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ?';
        $params[] = $user['id'];
    }

    // Build LIMIT/OFFSET safely as ints
    $limit  = (int)$per;
    $offset = (int)$off;

    $sql = "
      SELECT p.*, c.name AS client_name
      FROM projects p
      JOIN clients c ON p.client_id = c.id
      $where
      ORDER BY p.id DESC
      LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    if ($user['role'] !== 'admin') {
        // only one bound param: the viewer’s user_id
        $stmt->execute([$user['id']]);
    } else {
        $stmt->execute();   // admins have no filter param
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      'projects' => $rows,
      'has_more' => count($rows) == $per
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine()
    ]);
}
