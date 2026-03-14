<?php
require_once 'db.php';
session_start();
setJsonHeaders();

// Authentication Check middleware
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in again']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- GET JOBS LIST ---
if ($method === 'GET' && empty($action)) {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $whereClause .= " AND (j.title LIKE ? OR j.organization LIKE ? OR j.location LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($category) {
        $whereClause .= " AND j.category_id = ?";
        $params[] = $category;
    }
    if ($status === '0' || $status === '1') {
        $whereClause .= " AND j.is_active = ?";
        $params[] = (int)$status;
    }
    
    // Count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    // Fetch
    $query = "SELECT j.*, c.name as category_name FROM jobs j 
              LEFT JOIN categories c ON j.category_id = c.id 
              $whereClause ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
              
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    echo json_encode([
        'jobs' => $stmt->fetchAll() ?: [],
        'total' => $total,
        'pages' => ceil($total / $limit),
        'current_page' => $page
    ]);
    exit;
}

// --- GET SINGLE JOB FOR EDITING ---
if ($method === 'GET' && $action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $job = $stmt->fetch();
    
    if ($job) echo json_encode($job);
    else { http_response_code(404); echo json_encode(['error' => 'Job not found']); }
    exit;
}

// Ensure POST data wrapper (since PHP standard $_POST only catches form-data, not raw json body)
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, TRUE);
if (!$data && in_array($method, ['POST', 'PUT'])) $data = $_POST;

// --- ADD NEW JOB ---
if ($method === 'POST' && empty($action)) {
    if (empty($data['title']) || empty($data['organization']) || empty($data['description'])) {
        http_response_code(400); echo json_encode(['error' => 'Title, org, and description required']); exit;
    }
    
    $slug = generateSlug($data['title']);
    
    try {
        $query = "INSERT INTO jobs (
            title, slug, organization, category_id, description, eligibility, location,
            salary_min, salary_max, job_type, vacancy_count, form_start_date, form_end_date,
            exam_date, official_website, how_to_apply, is_featured, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['title'], $slug, $data['organization'], $data['category_id'] ?: null, 
            $data['description'], $data['eligibility'] ?: null, $data['location'] ?: null,
            $data['salary_min'] ?: null, $data['salary_max'] ?: null, $data['job_type'] ?: 'Full Time',
            $data['vacancy_count'] ?: null, $data['form_start_date'] ?: null, $data['form_end_date'] ?: null,
            $data['exam_date'] ?: null, $data['official_website'] ?: null, $data['how_to_apply'] ?: null,
            !empty($data['is_featured']) ? 1 : 0,
            isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1
        ]);
        
        $jobId = $pdo->lastInsertId();
        
        // Update category counts
        if (!empty($data['category_id'])) {
            $pdo->prepare("UPDATE categories SET job_count = (SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1) WHERE id = ?")
                ->execute([$data['category_id'], $data['category_id']]);
        }
        
        echo json_encode(['success' => true, 'id' => $jobId, 'slug' => $slug]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- UPDATE EXISTING JOB ---
if ($method === 'PUT' && isset($_GET['id'])) {
    
    // Get old job for category count logic
    $oldStmt = $pdo->prepare("SELECT category_id FROM jobs WHERE id = ?");
    $oldStmt->execute([$_GET['id']]);
    $oldJob = $oldStmt->fetch();
    
    try {
        $query = "UPDATE jobs SET 
            title=?, organization=?, category_id=?, description=?, eligibility=?,
            location=?, salary_min=?, salary_max=?, job_type=?, vacancy_count=?,
            form_start_date=?, form_end_date=?, exam_date=?, official_website=?,
            how_to_apply=?, is_featured=?, is_active=?, updated_at=datetime('now')
        WHERE id=?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['title'], $data['organization'], $data['category_id'] ?: null, 
            $data['description'], $data['eligibility'] ?: null, $data['location'] ?: null,
            $data['salary_min'] ?: null, $data['salary_max'] ?: null, $data['job_type'] ?: 'Full Time',
            $data['vacancy_count'] ?: null, $data['form_start_date'] ?: null, $data['form_end_date'] ?: null,
            $data['exam_date'] ?: null, $data['official_website'] ?: null, $data['how_to_apply'] ?: null,
            !empty($data['is_featured']) ? 1 : 0, isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
            $_GET['id']
        ]);
        
        // Recalculate Category Job Counts
        if ($oldJob && $oldJob['category_id']) {
            $pdo->prepare("UPDATE categories SET job_count = (SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1) WHERE id = ?")->execute([$oldJob['category_id'], $oldJob['category_id']]);
        }
        if (!empty($data['category_id'])) {
            $pdo->prepare("UPDATE categories SET job_count = (SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1) WHERE id = ?")->execute([$data['category_id'], $data['category_id']]);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- DELETE JOB ---
if ($method === 'DELETE' && isset($_GET['id'])) {
    $oldStmt = $pdo->prepare("SELECT category_id FROM jobs WHERE id = ?");
    $oldStmt->execute([$_GET['id']]);
    $oldJob = $oldStmt->fetch();
    
    try {
        $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$_GET['id']]);
        
        if ($oldJob && $oldJob['category_id']) {
            $pdo->prepare("UPDATE categories SET job_count = (SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1) WHERE id = ?")->execute([$oldJob['category_id'], $oldJob['category_id']]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- TOGGLE ACTIVE STATUS ---
if ($method === 'POST' && $action === 'toggle' && isset($_GET['id'])) {
    try {
        $pdo->prepare("UPDATE jobs SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$_GET['id']]);
        
        $oldStmt = $pdo->prepare("SELECT category_id FROM jobs WHERE id = ?");
        $oldStmt->execute([$_GET['id']]);
        $oldJob = $oldStmt->fetch();
        
        if ($oldJob && $oldJob['category_id']) {
            $pdo->prepare("UPDATE categories SET job_count = (SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1) WHERE id = ?")->execute([$oldJob['category_id'], $oldJob['category_id']]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid Request']);
?>
