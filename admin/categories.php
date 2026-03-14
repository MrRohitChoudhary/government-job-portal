<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle add category
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $slug, $description, $icon);
        
        if ($stmt->execute()) {
            $success = 'Category added successfully!';
        } else {
            $error = 'Error adding category: ' . $conn->error;
        }
    }
}

// Handle delete category
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($conn->query("DELETE FROM categories WHERE id = $id")) {
        $success = 'Category deleted successfully!';
    } else {
        $error = 'Error deleting category';
    }
}

// Handle toggle status
if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE categories SET is_active = NOT is_active WHERE id = $id");
    $success = 'Category status updated!';
}

// Get all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Job Portal Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
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
            <li><a href="manage_jobs.php"><i class="fas fa-list"></i> Manage Jobs</a></li>
            <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="sub_categories.php"><i class="fas fa-tags"></i> Sub Categories</a></li>
            <li><a href="applications.php"><i class="fas fa-users"></i> Applications</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Add Category Form -->
        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Add New Category</h3>
            
            <?php if ($success && isset($_POST['action'])): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Category Name <span style="color:red">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Banking Jobs" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Icon Class (Font Awesome)</label>
                            <input type="text" class="form-control" name="icon" placeholder="e.g., fa-university">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" class="form-control" name="description" placeholder="Short description">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Category
                </button>
            </form>
        </div>

        <!-- Categories List -->
        <div class="card">
            <h3><i class="fas fa-list"></i> All Categories</h3>
            
            <?php if ($success && !isset($_POST['action'])): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Icon</th>
                            <th>Jobs</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories->num_rows > 0): ?>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                                <td><i class="fas <?php echo htmlspecialchars($cat['icon'] ?? 'fa-folder'); ?>"></i></td>
                                <td><span class="badge badge-primary"><?php echo $cat['job_count']; ?></span></td>
                                <td>
                                    <a href="?action=toggle&id=<?php echo $cat['id']; ?>" 
                                       class="badge-status <?php echo $cat['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $cat['id']; ?>" 
                                       class="btn btn-danger btn-action" 
                                       onclick="return confirm('Are you sure? This will also delete all jobs in this category.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No categories found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>
</html>
