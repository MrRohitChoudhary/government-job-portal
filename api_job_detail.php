<?php
require_once 'db.php';
setJsonHeaders();

$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

if (!$slug) {
    http_response_code(400);
    echo json_encode(['error' => 'Job slug is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT j.*, c.name as category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.slug = ?");
    $stmt->execute([$slug]);
    $job = $stmt->fetch();
    
    if (!$job) {
        http_response_code(404);
        echo json_encode(['error' => 'Job not found']);
    } else {
        // Increment view count dynamically
        $updateStmt = $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?");
        $updateStmt->execute([$job['id']]);
        
        echo json_encode($job);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retreive job details']);
}
?>
