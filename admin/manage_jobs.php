<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get job info before deleting
    $job_result = $conn->query("SELECT category_id FROM jobs WHERE id = $id");
    if ($job_result->num_rows > 0) {
        $job = $job_result->fetch_assoc();
        
        if ($conn->query("DELETE FROM jobs WHERE id = $id")) {
            // Update category count
            if ($job['category_id']) {
                $conn->query("UPDATE categories SET job_count = job_count - 1 WHERE id = " . $job['category_id']);
            }
            $success = 'Job deleted successfully!';
        } else {
            $error = 'Error deleting job: ' . $conn->error;
        }
    }
}

// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE jobs SET is_active = NOT is_active WHERE id = $id");
    $success = 'Job status updated!';
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where_clauses = [];
if ($search) {
    $where_clauses[] = "(title LIKE '%$search%' OR organization LIKE '%$search%')";
}
if ($category_filter > 0) {
    $where_clauses[] = "category_id = $category_filter";
}
if ($status_filter !== '') {
    $where_clauses[] = "is_active = $status_filter";
}

$where = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as total FROM jobs $where");
$total_jobs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_jobs / $limit);

// Get jobs
$jobs = $conn->query("SELECT j.*, c.name as category_name 
    FROM jobs j 
    LEFT JOIN categories c ON j.category_id = c.id 
    $where 
    ORDER BY j.created_at DESC 
    LIMIT $limit OFFSET $offset");

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Job Portal Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding-top: 20px;
            z-index: 1000;
        }
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-brand h3 {
            margin: 0;
            font-size: 20px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu a {
            display: block;
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .table-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table-card h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-featured {
            background: #fff3cd;
            color: #856404;
        }
        .btn-action {
            padding: 5px 10px;
            margin: 0 3px;
            border-radius: 4px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .pagination {
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            margin: 0 3px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .pagination a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h3><i class="fas fa-briefcase"></i> Job Portal</h3>
            <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.8;">Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="add_job.php"><i class="fas fa-plus-circle"></i> Add New Job</a></li>
            <li><a href="manage_jobs.php" class="active"><i class="fas fa-list"></i> Manage Jobs</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="sub_categories.php"><i class="fas fa-tags"></i> Sub Categories</a></li>
            <li><a href="applications.php"><i class="fas fa-users"></i> Applications</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="table-card">
            <h3><i class="fas fa-list"></i> Manage Jobs</h3>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="category">
                            <option value="">All Categories</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="status">
                            <option value="">All Status</option>
                            <option value="1" <?php echo ($status_filter === '1') ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ($status_filter === '0') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Jobs Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th>Organization</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($jobs->num_rows > 0): ?>
                            <?php while ($job = $jobs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $job['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($job['organization']); ?></td>
                                <td><?php echo $job['category_name'] ? htmlspecialchars($job['category_name']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($job['location'] ?: '-'); ?></td>
                                <td><?php echo $job['form_end_date'] ? date('d M Y', strtotime($job['form_end_date'])) : '-'; ?></td>
                                <td>
                                    <a href="?action=toggle_status&id=<?php echo $job['id']; ?>" 
                                       class="badge-status <?php echo $job['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"
                                       onclick="return confirm('Are you sure you want to change status?');">
                                        <?php echo $job['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($job['is_featured']): ?>
                                        <span class="badge-status badge-featured"><i class="fas fa-star"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-info btn-action" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $job['id']; ?>" class="btn btn-danger btn-action" title="Delete" onclick="return confirm('Are you sure you want to delete this job?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="../job_details.php?slug=<?php echo $job['slug']; ?>" target="_blank" class="btn btn-success btn-action" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No jobs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>" 
                       class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <strong>Total Jobs:</strong> <?php echo $total_jobs; ?>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>
</html>
