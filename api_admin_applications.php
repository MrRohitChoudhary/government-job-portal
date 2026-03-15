<?php
require_once 'db.php';
session_start();
setJsonHeaders();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query('
            SELECT a.*, j.title as job_title 
            FROM applications a 
            LEFT JOIN jobs j ON a.job_id = j.id 
            ORDER BY a.applied_at DESC
        ');
        echo json_encode(['applications' => $stmt->fetchAll() ?: []]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => 'Database error']);
    }
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid Request']);
?>
