<?php
require_once 'db.php';
setJsonHeaders();

try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
    echo json_encode($categories ?: []);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch categories']);
}
?>
