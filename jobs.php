<?php
require_once 'admin/db.php';

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? trim($_GET['job_type']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build where clause for active jobs
$where = "WHERE j.is_active = 1";

// Get current date for filtering expired jobs
$today = date('Y-m-d');
$where .= " AND j.form_end_date >= '$today'";

if ($search) {
    $where .= " AND (j.title LIKE '%$search%' OR j.organization LIKE '%$search%' OR j.description LIKE '%$search%')";
}
if ($category > 0) {
    $where .= " AND j.category_id = $category";
}
if ($location) {
    $where .= " AND j.location LIKE '%$location%'";
}
if ($job_type) {
    $where .= " AND j.job_type = '$job_type'";
}

// Sorting
switch($sort) {
    case 'oldest':
        $order_by = "ORDER BY j.created_at ASC";
        break;
    case 'deadline':
        $order_by = "ORDER BY j.form_end_date ASC";
        break;
    case 'title':
        $order_by = "ORDER BY j.title ASC";
        break;
    default: // newest
        $order_by = "ORDER BY j.created_at DESC";
}

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as total FROM jobs j $where");
$total_jobs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_jobs / $limit);

// Get jobs
$jobs = $conn->query("SELECT j.*, c.name as category_name 
    FROM jobs j 
    LEFT JOIN categories c ON j.category_id = c.id 
    $where 
    $order_by 
    LIMIT $limit OFFSET $offset");

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// Get unique locations for filter
$locations = $conn->query("SELECT DISTINCT location FROM jobs WHERE is_active = 1 AND location != '' ORDER BY location");

// Get total jobs count for display
$total_jobs_all = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE is_active = 1 AND form_end_date >= '$today'");
$total_jobs_count = $total_jobs_all->fetch_assoc()['total'];
?>

<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Browse Government Jobs - Karnataka & AP</title>
    <meta name="description" content="Browse latest government jobs in Karnataka and Andhra Pradesh. Sarkari Naukri for all categories.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/jquery-ui.css">
    <link rel="stylesheet" href="css/gijgo.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/slicknav.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- header-start -->
    <header>
        <div class="header-area ">
            <div id="sticky-header" class="main-header-area">
                <div class="container-fluid ">
                    <div class="header_bottom_border">
                        <div class="row align-items-center">
                            <div class="col-xl-3 col-lg-2">
                                <div class="logo">
                                    <a href="index.php">
                                        <img src="img/logo.png" alt="">
                                    </a>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-7">
                                <div class="main-menu  d-none d-lg-block">
                                    <nav>
                                        <ul id="navigation">
                                            <li><a href="index.php">home</a></li>
                                            <li><a href="jobs.php" class="active">Browse Jobs</a></li>
                                            <li><a href="#">States <i class="ti-angle-down"></i></a>
                                                <ul class="submenu">
                                                    <li><a href="jobs.php?location=Karnataka">Karnataka Jobs</a></li>
                                                    <li><a href="jobs.php?location=Andhra+Pradesh">Andhra Pradesh Jobs</a></li>
                                                    <li><a href="jobs.php?location=All+India">All India Jobs</a></li>
                                                </ul>
                                            </li>
                                            <li><a href="#">Category <i class="ti-angle-down"></i></a>
                                                <ul class="submenu">
                                                    <?php while($cat = $categories->fetch_assoc()): ?>
                                                    <li><a href="jobs.php?category=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                                                    <?php endwhile; ?>
                                                </ul>
                                            </li>
                                            <li><a href="contact.html">Contact</a></li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 d-none d-lg-block">
                                <div class="Appointment">
                                    <div class="phone_num d-none d-xl-block">
                                        <a href="admin/login.php">Admin Login</a>
                                    </div>
                                    <div class="d-none d-lg-block">
                                        <a class="boxed-btn3" href="admin/add_job.php">Post a Job</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mobile_menu d-block d-lg-none"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- header-end -->

    <!-- bradcam_area  -->
    <div class="bradcam_area bradcam_bg_1">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="bradcam_text">
                        <h3><?php echo $total_jobs_count; ?>+ Jobs Available</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ bradcam_area  -->

    <!-- job_listing_area_start  -->
    <div class="job_listing_area plus_padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="job_filter white-bg">
                        <div class="form_inner white-bg">
                            <h3>Filter Jobs</h3>
                            <form action="jobs.php" method="GET">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="single_field">
                                            <input type="text" name="search" placeholder="Search keyword" value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="single_field">
                                            <select name="location" class="wide">
                                                <option value="">All Locations</option>
                                                <option value="Karnataka" <?php echo ($location == 'Karnataka') ? 'selected' : ''; ?>>Karnataka</option>
                                                <option value="Andhra Pradesh" <?php echo ($location == 'Andhra Pradesh') ? 'selected' : ''; ?>>Andhra Pradesh</option>
                                                <option value="All India" <?php echo ($location == 'All India') ? 'selected' : ''; ?>>All India</option>
                                                <option value="Bangalore" <?php echo ($location == 'Bangalore') ? 'selected' : ''; ?>>Bangalore</option>
                                                <option value="Hyderabad" <?php echo ($location == 'Hyderabad') ? 'selected' : ''; ?>>Hyderabad</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="single_field">
                                            <select name="category" class="wide">
                                                <option value="">All Categories</option>
                                                <?php 
                                                $categories->data_seek(0);
                                                while($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="single_field">
                                            <select name="job_type" class="wide">
                                                <option value="">Job Type</option>
                                                <option value="Full Time" <?php echo ($job_type == 'Full Time') ? 'selected' : ''; ?>>Full Time</option>
                                                <option value="Part Time" <?php echo ($job_type == 'Part Time') ? 'selected' : ''; ?>>Part Time</option>
                                                <option value="Contract" <?php echo ($job_type == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                                <option value="Internship" <?php echo ($job_type == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="reset_btn">
                                    <button type="submit" class="boxed-btn3 w-100">Apply Filters</button>
                                </div>
                                <?php if($search || $category || $location || $job_type): ?>
                                <div class="reset_btn mt-2">
                                    <a href="jobs.php" class="boxed-btn3-outline w-100">Reset Filters</a>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="recent_joblist_wrap">
                        <div class="recent_joblist white-bg ">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h4>Job Listing (<?php echo $total_jobs; ?> jobs found)</h4>
                                </div>
                                <div class="col-md-6">
                                    <div class="serch_cat d-flex justify-content-end">
                                        <select onchange="window.location.href=this.value" class="wide">
                                            <option value="jobs.php?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Most Recent</option>
                                            <option value="jobs.php?<?php echo http_build_query(array_merge($_GET, ['sort' => 'oldest'])); ?>" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                                            <option value="jobs.php?<?php echo http_build_query(array_merge($_GET, ['sort' => 'deadline'])); ?>" <?php echo ($sort == 'deadline') ? 'selected' : ''; ?>>Deadline Soon</option>
                                            <option value="jobs.php?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title'])); ?>" <?php echo ($sort == 'title') ? 'selected' : ''; ?>>A-Z</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="job_lists m-0">
                        <div class="row">
                            <?php if($jobs->num_rows > 0): ?>
                                <?php while($job = $jobs->fetch_assoc()): ?>
                                <div class="col-lg-12 col-md-12">
                                    <div class="single_jobs white-bg d-flex justify-content-between">
                                        <div class="jobs_left d-flex align-items-center">
                                            <div class="thumb">
                                                <img src="img/svg_icon/1.svg" alt="">
                                            </div>
                                            <div class="jobs_conetent">
                                                <a href="job_details.php?slug=<?php echo $job['slug']; ?>"><h4><?php echo htmlspecialchars($job['title']); ?></h4></a>
                                                <div class="links_locat d-flex align-items-center">
                                                    <div class="location">
                                                        <p> <i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                                                    </div>
                                                    <div class="location">
                                                        <p> <i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($job['job_type'] ?: 'Full Time'); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="jobs_right">
                                            <div class="apply_now">
                                                <a class="heart_mark" href="job_details.php?slug=<?php echo $job['slug']; ?>"> <i class="fa fa-heart"></i> </a>
                                                <a href="job_details.php?slug=<?php echo $job['slug']; ?>" class="boxed-btn3">Apply Now</a>
                                            </div>
                                            <div class="date">
                                                <p>Last Date: <?php echo $job['form_end_date'] ? date('d M Y', strtotime($job['form_end_date'])) : 'N/A'; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-lg-12">
                                    <div class="text-center py-5">
                                        <h4>No jobs found</h4>
                                        <p>Try adjusting your search filters</p>
                                        <a href="jobs.php" class="boxed-btn3">View All Jobs</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="pagination_wrap">
                                    <ul>
                                        <?php if ($page > 1): ?>
                                        <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"> <i class="ti-angle-left"></i> </a></li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                                            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" <?php echo ($page == $i) ? 'class="active"' : ''; ?>><span><?php echo $i; ?></span></a></li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                        <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"> <i class="ti-angle-right"></i> </a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- job_listing_area_end  -->

    <!-- footer start -->
    <footer class="footer">
        <div class="footer_top">
            <div class="container">
                <div class="row">
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="footer_widget wow fadeInUp" data-wow-duration="1s" data-wow-delay=".3s">
                            <div class="footer_logo">
                                <a href="index.php">
                                    <img src="img/logo.png" alt="">
                                </a>
                            </div>
                            <p>
                                info@jobportal.com <br>
                                +91 98765 43210 <br>
                                Karnataka, India
                            </p>
                            <div class="socail_links">
                                <ul>
                                    <li>
                                        <a href="#">
                                            <i class="ti-facebook"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#">
                                            <i class="fa fa-google-plus"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#">
                                            <i class="fa fa-twitter"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#">
                                            <i class="fa fa-instagram"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6 col-lg-2">
                        <div class="footer_widget wow fadeInUp" data-wow-duration="1.1s" data-wow-delay=".4s">
                            <h3 class="footer_title">
                                Company
                            </h3>
                            <ul>
                                <li><a href="#">About </a></li>
                                <li><a href="#"> Pricing</a></li>
                                <li><a href="#">Career Tips</a></li>
                                <li><a href="#">FAQ</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="footer_widget wow fadeInUp" data-wow-duration="1.2s" data-wow-delay=".5s">
                            <h3 class="footer_title">
                                Category
                            </h3>
                            <ul>
                                <li><a href="jobs.php?category=1">KAS / IAS</a></li>
                                <li><a href="jobs.php?category=2">Police & IPS</a></li>
                                <li><a href="jobs.php?category=3">Banking Jobs</a></li>
                                <li><a href="jobs.php?category=4">Teaching Jobs</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 col-lg-4">
                        <div class="footer_widget wow fadeInUp" data-wow-duration="1.3s" data-wow-delay=".6s">
                            <h3 class="footer_title">
                                Subscribe
                            </h3>
                            <form action="#" class="newsletter_form">
                                <input type="text" placeholder="Enter your mail">
                                <button type="submit">Subscribe</button>
                            </form>
                            <p class="newsletter_text">Subscribe for latest government job updates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="copy-right_text wow fadeInUp" data-wow-duration="1.4s" data-wow-delay=".3s">
            <div class="container">
                <div class="footer_border"></div>
                <div class="row">
                    <div class="col-xl-12">
                        <p class="copy_right text-center">
Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | Government Job Portal
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!--/ footer end  -->

    <!-- JS here -->
    <script src="js/vendor/modernizr-3.5.0.min.js"></script>
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/isotope.pkgd.min.js"></script>
    <script src="js/ajax-form.js"></script>
    <script src="js/waypoints.min.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/imagesloaded.pkgd.min.js"></script>
    <script src="js/scrollIt.js"></script>
    <script src="js/jquery.scrollUp.min.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/nice-select.min.js"></script>
    <script src="js/jquery.slicknav.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/range.js"></script>
    <script src="js/contact.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.form.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/mail-script.js"></script>
    <script src="js/main.js"></script>

    <script>
        $( function() {
            $( "#slider-range" ).slider({
                range: true,
                min: 0,
                max: 24600,
                values: [ 750, 24600 ],
                slide: function( event, ui ) {
                    $( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] +"/ Year" );
                }
            });
            $( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
                " - $" + $( "#slider-range" ).slider( "values", 1 ) + "/ Year");
        } );
    </script>
</body>

</html>
