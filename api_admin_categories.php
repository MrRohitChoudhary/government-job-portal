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

// Ensure POST data wrapper
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, TRUE);
if (!$data && in_array($method, ['POST', 'PUT'])) $data = $_POST;

// --- GET ALL CATEGORIES ---
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
    echo json_encode($stmt->fetchAll() ?: []);
    exit;
}

// --- ADD CATEGORY ---
if ($method === 'POST') {
    if (empty($data['name'])) {
        http_response_code(400); echo json_encode(['error' => 'Category name is required']); exit;
    }
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']), '-'));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['name'], $slug, $data['description'] ?: null, $data['icon'] ?: null
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- UPDATE CATEGORY ---
if ($method === 'PUT' && isset($_GET['id'])) {
    if (empty($data['name'])) {
        http_response_code(400); echo json_encode(['error' => 'Category name is required']); exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, icon=?, is_active=? WHERE id=?");
        $stmt->execute([
            $data['name'], $data['description'] ?: null, $data['icon'] ?: null,
            isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
            $_GET['id']
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- DELETE CATEGORY ---
if ($method === 'DELETE' && isset($_GET['id'])) {
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$_GET['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid Request']);
?>
