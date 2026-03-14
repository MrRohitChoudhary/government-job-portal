<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Get job ID from URL
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get job data
$job = null;
if ($job_id > 0) {
    $result = $conn->query("SELECT * FROM jobs WHERE id = $job_id");
    if ($result->num_rows > 0) {
        $job = $result->fetch_assoc();
    }
}

if (!$job) {
    header('Location: manage_jobs.php');
    exit;
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// Get sub-categories for dropdown
$sub_categories = $conn->query("SELECT * FROM sub_categories WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $sub_category_id = intval($_POST['sub_category_id'] ?? 0);
    $description = $_POST['description'] ?? '';
    $eligibility = $_POST['eligibility'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $salary_min = trim($_POST['salary_min'] ?? '');
    $salary_max = trim($_POST['salary_max'] ?? '');
    $salary_description = trim($_POST['salary_description'] ?? '');
    $job_type = trim($_POST['job_type'] ?? '');
    $vacancy_count = intval($_POST['vacancy_count'] ?? 0);
    $application_fee = trim($_POST['application_fee'] ?? '');
    $form_start_date = $_POST['form_start_date'] ?? '';
    $form_end_date = $_POST['form_end_date'] ?? '';
    $exam_date = $_POST['exam_date'] ?? '';
    $official_website = trim($_POST['official_website'] ?? '');
    $how_to_apply = $_POST['how_to_apply'] ?? '';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($title) || empty($organization) || empty($description)) {
        $error = 'Please fill in all required fields';
    } else {
        // Update job in database
        $stmt = $conn->prepare("UPDATE jobs SET title=?, organization=?, category_id=?, sub_category_id=?, description=?, eligibility=?, location=?, salary_min=?, salary_max=?, salary_description=?, job_type=?, vacancy_count=?, application_fee=?, form_start_date=?, form_end_date=?, exam_date=?, official_website=?, how_to_apply=?, is_featured=?, is_active=? WHERE id=?");
        
        $stmt->bind_param("sssiisssssssissssssi", 
            $title, 
            $organization, 
            $category_id, 
            $sub_category_id, 
            $description, 
            $eligibility, 
            $location, 
            $salary_min, 
            $salary_max, 
            $salary_description, 
            $job_type, 
            $vacancy_count, 
            $application_fee, 
            $form_start_date, 
            $form_end_date, 
            $exam_date, 
            $official_website, 
            $how_to_apply, 
            $is_featured, 
            $is_active,
            $job_id
        );
        
        if ($stmt->execute()) {
            $success = 'Job updated successfully!';
            // Refresh job data
            $result = $conn->query("SELECT * FROM jobs WHERE id = $job_id");
            $job = $result->fetch_assoc();
        } else {
            $error = 'Error updating job: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Job Portal Admin</title>
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
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-card h3 {
            margin-bottom: 30px;
            color: #2c3e50;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group label .required {
            color: red;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea.form-control {
            min-height: 120px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .checkbox-group input {
            width: 18px;
            height: 18px;
        }
        .btn-submit {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
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
        .section-title {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 25px 0 15px 0;
            font-weight: 600;
            color: #2c3e50;
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
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="sub_categories.php"><i class="fas fa-tags"></i> Sub Categories</a></li>
            <li><a href="applications.php"><i class="fas fa-users"></i> Applications</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="form-card">
            <h3><i class="fas fa-edit"></i> Edit Job</h3>
            
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
            
            <form method="POST" action="">
                <!-- Basic Information -->
                <div class="section-title">Basic Information</div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Job Title <span class="required">*</span></label>
                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Organization <span class="required">*</span></label>
                            <input type="text" class="form-control" name="organization" value="<?php echo htmlspecialchars($job['organization']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category <span class="required">*</span></label>
                            <select class="form-control" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($job['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Sub Category</label>
                            <select class="form-control" name="sub_category_id">
                                <option value="">Select Sub Category</option>
                                <?php while ($sub = $sub_categories->fetch_assoc()): ?>
                                    <option value="<?php echo $sub['id']; ?>" <?php echo ($job['sub_category_id'] == $sub['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sub['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Job Description <span class="required">*</span></label>
                    <textarea class="form-control" name="description" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Eligibility / Qualification</label>
                    <textarea class="form-control" name="eligibility"><?php echo htmlspecialchars($job['eligibility']); ?></textarea>
                </div>
                
                <!-- Job Details -->
                <div class="section-title">Job Details</div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($job['location']); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Job Type</label>
                            <select class="form-control" name="job_type">
                                <option value="">Select Job Type</option>
                                <option value="Full Time" <?php echo ($job['job_type'] == 'Full Time') ? 'selected' : ''; ?>>Full Time</option>
                                <option value="Part Time" <?php echo ($job['job_type'] == 'Part Time') ? 'selected' : ''; ?>>Part Time</option>
                                <option value="Contract" <?php echo ($job['job_type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                <option value="Internship" <?php echo ($job['job_type'] == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Vacancy Count</label>
                            <input type="number" class="form-control" name="vacancy_count" value="<?php echo htmlspecialchars($job['vacancy_count']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Salary (Min)</label>
                            <input type="text" class="form-control" name="salary_min" value="<?php echo htmlspecialchars($job['salary_min']); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Salary (Max)</label>
                            <input type="text" class="form-control" name="salary_max" value="<?php echo htmlspecialchars($job['salary_max']); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Application Fee</label>
                            <input type="text" class="form-control" name="application_fee" value="<?php echo htmlspecialchars($job['application_fee']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Important Dates -->
                <div class="section-title">Important Dates</div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Form Start Date</label>
                            <input type="date" class="form-control" name="form_start_date" value="<?php echo $job['form_start_date']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Form End Date</label>
                            <input type="date" class="form-control" name="form_end_date" value="<?php echo $job['form_end_date']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Exam Date</label>
                            <input type="date" class="form-control" name="exam_date" value="<?php echo $job['exam_date']; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Apply Details -->
                <div class="section-title">Apply Details</div>
                
                <div class="form-group">
                    <label>Official Website URL <span class="required">*</span></label>
                    <input type="url" class="form-control" name="official_website" value="<?php echo htmlspecialchars($job['official_website']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>How to Apply</label>
                    <textarea class="form-control" name="how_to_apply"><?php echo htmlspecialchars($job['how_to_apply']); ?></textarea>
                </div>
                
                <!-- Status -->
                <div class="section-title">Status</div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="is_featured" name="is_featured" <?php echo $job['is_featured'] ? 'checked' : ''; ?>>
                    <label for="is_featured">Mark as Featured Job</label>
                    
                    <input type="checkbox" id="is_active" name="is_active" <?php echo $job['is_active'] ? 'checked' : ''; ?>>
                    <label for="is_active">Active (Visible on Website)</label>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Update Job
                        </button>
                        <a href="manage_jobs.php" class="btn btn-secondary ml-3" style="padding: 12px 30px; border-radius: 5px;">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>
</html>
