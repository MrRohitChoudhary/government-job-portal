<?php
require_once 'db.php';
session_start();
setJsonHeaders();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $today = date('Y-m-d');
    $query = "SELECT 
        (SELECT COUNT(*) FROM jobs) as total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1) as active_jobs,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 0) as inactive_jobs,
        (SELECT COUNT(*) FROM jobs WHERE is_featured = 1) as featured_jobs,
        (SELECT COUNT(*) FROM categories) as total_categories,
        (SELECT COUNT(*) FROM sub_categories) as total_subcategories,
        (SELECT COUNT(*) FROM applications) as total_applications,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1 AND form_end_date >= '$today') as live_jobs,
        (SELECT SUM(views) FROM jobs) as total_views,
        (SELECT SUM(vacancy_count) FROM jobs WHERE is_active = 1) as total_vacancies";
        
    $stmt = $pdo->query($query);
    $stats = $stmt->fetch();
    
    // Ensure null sums become 0
    if ($stats) {
        $stats['total_views'] = $stats['total_views'] ?: 0;
        $stats['total_vacancies'] = $stats['total_vacancies'] ?: 0;
    }
    
    echo json_encode($stats ?: []);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
