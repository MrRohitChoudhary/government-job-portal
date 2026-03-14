<?php
require_once 'db.php';
setJsonHeaders();

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$whereClause = "WHERE j.is_active = 1";
$params = [];

$today = date('Y-m-d');
$whereClause .= " AND j.form_end_date >= ?";
$params[] = $today;

if ($search) {
    $whereClause .= " AND (j.title LIKE ? OR j.organization LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $whereClause .= " AND j.category_id = ?";
    $params[] = $category;
}
if ($location) {
    $whereClause .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}
if ($job_type) {
    $whereClause .= " AND j.job_type = ?";
    $params[] = $job_type;
}

try {
    // 1. Get Total Count for Pagination
    $countQuery = "SELECT COUNT(*) as total FROM jobs j $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    // 2. Sorting
    switch ($sort) {
        case 'oldest': $orderClause = 'ORDER BY j.created_at ASC'; break;
        case 'deadline': $orderClause = 'ORDER BY j.form_end_date ASC'; break;
        case 'title': $orderClause = 'ORDER BY j.title ASC'; break;
        default: $orderClause = 'ORDER BY j.is_featured DESC, j.created_at DESC';
    }
    
    // 3. Fetch Data
    $query = "SELECT j.*, c.name as category_name FROM jobs j 
              LEFT JOIN categories c ON j.category_id = c.id 
              $whereClause $orderClause LIMIT ? OFFSET ?";
              
    // Add pagination params to existing filters
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    echo json_encode([
        'jobs' => $jobs ?: [],
        'total' => $total,
        'pages' => ceil($total / $limit),
        'current_page' => $page
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch jobs listing']);
    error_log('API All-Jobs error: ' . $e->getMessage());
}
?>
