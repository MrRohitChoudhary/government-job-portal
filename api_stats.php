<?php
require_once 'db.php';
setJsonHeaders();

try {
    $today = date('Y-m-d');
    
    $query = "SELECT 
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1 AND form_end_date >= ?) as total_jobs,
        (SELECT COUNT(*) FROM categories WHERE is_active = 1) as total_categories,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1 AND form_end_date >= ?) as active_jobs";
        
    $stmt = $pdo->prepare($query);
    $stmt->execute([$today, $today]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        $stats = ['total_jobs' => 0, 'total_categories' => 0, 'active_jobs' => 0];
    }
    
    echo json_encode($stats);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch stats']);
}
?>
