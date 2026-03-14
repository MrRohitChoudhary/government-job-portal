<?php
require_once 'db.php';
setJsonHeaders();

// Handle cross-origin requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Base query for `/api/jobs` (HomePage logic)
$query = "SELECT j.*, c.name as category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.is_active = 1";
$params = [];

// Only show active jobs (deadline >= today)
$today = date('Y-m-d');
$query .= " AND j.form_end_date >= ?";
$params[] = $today;

// Filters
if ($search) {
    $query .= " AND (j.title LIKE ? OR j.organization LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND j.category_id = ?";
    $params[] = $category;
}

if ($location) {
    $query .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}

if ($job_type) {
    $query .= " AND j.job_type = ?";
    $params[] = $job_type;
}

// Sorting logic
switch ($sort) {
    case 'oldest':
        $query .= ' ORDER BY j.created_at ASC';
        break;
    case 'deadline':
        $query .= ' ORDER BY j.form_end_date ASC';
        break;
    case 'title':
        $query .= ' ORDER BY j.title ASC';
        break;
    default:
        $query .= ' ORDER BY j.is_featured DESC, j.created_at DESC';
}

$query .= " LIMIT ?";
$params[] = $limit > 0 ? $limit : 10;

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    echo json_encode($jobs ?: []);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve jobs']);
    error_log('API Jobs error: ' . $e->getMessage());
}
?>
