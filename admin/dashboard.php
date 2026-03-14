<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get statistics
$stats = [];

// Total jobs
$result = $conn->query("SELECT COUNT(*) as total FROM jobs");
$stats['total_jobs'] = $result->fetch_assoc()['total'];

// Active jobs
$result = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE is_active = 1");
$stats['active_jobs'] = $result->fetch_assoc()['total'];

// Total categories
$result = $conn->query("SELECT COUNT(*) as total FROM categories");
$stats['total_categories'] = $result->fetch_assoc()['total'];

// Total applications (future feature)
$result = $conn->query("SELECT COUNT(*) as total FROM applications");
$stats['total_applications'] = $result->fetch_assoc()['total'];

// Recent jobs
$recent_jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5");

// Today's date for comparison
$today = date('Y-m-d');
$upcoming_deadlines = $conn->query("SELECT * FROM jobs WHERE form_end_date >= '$today' AND is_active = 1 ORDER BY form_end_date ASC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Job Portal Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5a623;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-bg: #f8f9fa;
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
        .sidebar-menu li {
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
        .top-header {
            background: white;
            padding: 15px 30px;
            margin: -30px -30px 30px -30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stats-card h2 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stats-card p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
        }
        .table-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table-card h4 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="add_job.php"><i class="fas fa-plus-circle"></i> Add New Job</a></li>
            <li><a href="manage_jobs.php"><i class="fas fa-list"></i> Manage Jobs</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="sub_categories.php"><i class="fas fa-tags"></i> Sub Categories</a></li>
            <li><a href="applications.php"><i class="fas fa-users"></i> Applications</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h4 style="margin: 0; color: #2c3e50;">Dashboard</h4>
            <div>
                <span style="margin-right: 15px;">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_full_name']); ?></strong></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: #e8f4fd; color: #4a90e2;">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h2><?php echo $stats['total_jobs']; ?></h2>
                    <p>Total Jobs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: #d4edda; color: #27ae60;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2><?php echo $stats['active_jobs']; ?></h2>
                    <p>Active Jobs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: #fff3cd; color: #f5a623;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h2><?php echo $stats['total_categories']; ?></h2>
                    <p>Categories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: #fce4ec; color: #e91e63;">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2><?php echo $stats['total_applications']; ?></h2>
                    <p>Applications</p>
                </div>
            </div>
        </div>

        <!-- Recent Jobs & Upcoming Deadlines -->
        <div class="row">
            <div class="col-md-6">
                <div class="table-card">
                    <h4><i class="fas fa-clock"></i> Recent Jobs</h4>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Organization</th>
                                <th>Status</th>
                                <th>Posted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" style="color: #4a90e2; font-weight: 500;">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($job['organization']); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $job['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $job['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($job['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="table-card">
                    <h4><i class="fas fa-exclamation-triangle"></i> Upcoming Deadlines</h4>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Deadline</th>
                                <th>Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($job = $upcoming_deadlines->fetch_assoc()): 
                                $days_left = (strtotime($job['form_end_date']) - strtotime($today)) / (60 * 60 * 24);
                            ?>
                            <tr>
                                <td>
                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" style="color: #4a90e2; font-weight: 500;">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d M Y', strtotime($job['form_end_date'])); ?></td>
                                <td>
                                    <?php if ($days_left <= 7): ?>
                                        <span class="badge-status badge-inactive"><?php echo round($days_left); ?> days</span>
                                    <?php elseif ($days_left <= 30): ?>
                                        <span class="badge-status badge-featured"><?php echo round($days_left); ?> days</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-active"><?php echo round($days_left); ?> days</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="table-card">
                    <h4><i class="fas fa-bolt"></i> Quick Actions</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="add_job.php" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-plus"></i> Add New Job
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="manage_jobs.php" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-list"></i> Manage Jobs
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="categories.php" class="btn btn-warning btn-block btn-lg">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="../index.php" target="_blank" class="btn btn-info btn-block btn-lg">
                                <i class="fas fa-eye"></i> View Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>
</html>
