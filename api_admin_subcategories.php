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

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, TRUE);
if (!$data && in_array($method, ['POST', 'PUT'])) $data = $_POST;

if ($method === 'GET') {
    $stmt = $pdo->query('
        SELECT sc.*, c.name as category_name 
        FROM sub_categories sc 
        LEFT JOIN categories c ON sc.category_id = c.id 
        ORDER BY sc.name
    ');
    echo json_encode($stmt->fetchAll() ?: []);
    exit;
}

if ($method === 'POST') {
    if (empty($data['name']) || empty($data['category_id'])) {
        http_response_code(400); echo json_encode(['error' => 'Name and Category ID are required']); exit;
    }
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']), '-'));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO sub_categories (category_id, name, slug) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['category_id'], $data['name'], $slug
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE' && isset($_GET['id'])) {
    try {
        $pdo->prepare("DELETE FROM sub_categories WHERE id = ?")->execute([$_GET['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid Request']);
?>
