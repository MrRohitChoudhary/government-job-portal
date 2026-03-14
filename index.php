<?php
require_once 'admin/db.php';

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Build where clause for active jobs
$where = "WHERE j.is_active = 1";

// Get current date for filtering
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

// Get categories
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// Get recent jobs (latest 10)
$recent_jobs = $conn->query("SELECT j.*, c.name as category_name 
    FROM jobs j 
    LEFT JOIN categories c ON j.category_id = c.id 
    $where 
    ORDER BY j.created_at DESC 
    LIMIT 10");

// Get featured jobs
$featured_jobs = $conn->query("SELECT j.*, c.name as category_name 
    FROM jobs j 
    LEFT JOIN categories c ON j.category_id = c.id 
    WHERE j.is_active = 1 AND j.is_featured = 1 AND j.form_end_date >= '$today'
    ORDER BY j.created_at DESC 
    LIMIT 5");

// Get all jobs for counting
$total_jobs = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE is_active = 1 AND form_end_date >= '$today'");
$total_jobs_count = $total_jobs->fetch_assoc()['total'];

// Get unique locations for filter
$locations = $conn->query("SELECT DISTINCT location FROM jobs WHERE is_active = 1 AND location != '' ORDER BY location");
?>

<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Karnataka & AP Government Jobs - Sarkari Result</title>
    <meta name="description" content="Find latest Government Jobs in Karnataka and Andhra Pradesh. Sarkari Naukri for IAS, IPS, Banking, Teaching, Engineering and more.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/flaticon.css">
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
                                            <li><a href="index.php" class="active">home</a></li>
                                            <li><a href="jobs.php">Browse Jobs</a></li>
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

    <!-- slider_area_start -->
    <div class="slider_area">
        <div class="single_slider  d-flex align-items-center slider_bg_1">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-7 col-md-6">
                        <div class="slider_text">
                            <h5 class="wow fadeInLeft" data-wow-duration="1s" data-wow-delay=".2s"><?php echo $total_jobs_count; ?>+ Jobs available</h5>
                            <h3 class="wow fadeInLeft" data-wow-duration="1s" data-wow-delay=".3s">Find your Dream Government Job</h3>
                            <p class="wow fadeInLeft" data-wow-duration="1s" data-wow-delay=".4s">Karnataka & Andhra Pradesh Government Jobs | Sarkari Naukri</p>
                            <div class="sldier_btn wow fadeInLeft" data-wow-duration="1s" data-wow-delay=".5s">
                                <a href="jobs.php" class="boxed-btn3">Browse All Jobs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="ilstration_img wow fadeInRight d-none d-lg-block text-right" data-wow-duration="1s" data-wow-delay=".2s">
            <img src="img/banner/illustration.png" alt="">
        </div>
    </div>
    <!-- slider_area_end -->

    <!-- catagory_area -->
    <div class="catagory_area">
        <div class="container">
            <form action="jobs.php" method="GET" class="row cat_search">
                <div class="col-lg-3 col-md-4">
                    <div class="single_input">
                        <input type="text" name="search" placeholder="Search keyword" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="single_input">
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
                <div class="col-lg-3 col-md-4">
                    <div class="single_input">
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
                <div class="col-lg-3 col-md-12">
                    <div class="job_btn">
                        <button type="submit" class="boxed-btn3">Find Job</button>
                    </div>
                </div>
            </form>
            <div class="row">
                <div class="col-lg-12">
                    <div class="popular_search d-flex align-items-center">
                        <span>Popular Search:</span>
                        <ul>
                            <li><a href="jobs.php?category=1">IAS (Indian Administrative Service)</a></li>
                            <li><a href="jobs.php?category=2">IPS (Indian Police Service)</a></li>
                            <li><a href="jobs.php?category=3">Banking Sector</a></li>
                            <li><a href="jobs.php?category=4">Teaching Jobs</a></li>
                            <li><a href="jobs.php?category=5">Engineering</a></li>
                            <li><a href="jobs.php?category=7">Karnataka Government</a></li>
                            <li><a href="jobs.php?category=8">Andhra Pradesh</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ catagory_area -->

    <!-- popular_catagory_area_start  -->
    <div class="popular_catagory_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title mb-40">
                        <h3>Popular Categories</h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php 
                $categories->data_seek(0);
                while($cat = $categories->fetch_assoc()): ?>
                <div class="col-lg-4 col-xl-3 col-md-6">
                    <div class="single_catagory">
                        <a href="jobs.php?category=<?php echo $cat['id']; ?>"><h4><?php echo htmlspecialchars($cat['name']); ?></h4></a>
                        <p> <span><?php echo $cat['job_count']; ?></span> Available position</p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <!-- popular_catagory_area_end  -->

    <!-- job_listing_area_start  -->
    <div class="job_listing_area">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="section_title">
                        <h3>Latest Government Jobs</h3>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="brouse_job text-right">
                        <a href="jobs.php" class="boxed-btn4">Browse More Job</a>
                    </div>
                </div>
            </div>
            <div class="job_lists">
                <div class="row">
                    <?php if($recent_jobs->num_rows > 0): ?>
                        <?php while($job = $recent_jobs->fetch_assoc()): ?>
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
                                        <a class="heart_mark" href="job_details.php?slug=<?php echo $job['slug']; ?>"> <i class="ti-heart"></i> </a>
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
                            <div class="text-center">
                                <h4>No jobs found</h4>
                                <p>Please try different search criteria</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- job_listing_area_end  -->

    <!-- featured_candidates_area_start  -->
    <div class="featured_candidates_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title text-center mb-40">
                        <h3>Featured Government Jobs</h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="candidate_active owl-carousel">
                        <?php while($job = $featured_jobs->fetch_assoc()): ?>
                        <div class="single_candidates text-center">
                            <div class="thumb">
                                <img src="img/candiateds/1.png" alt="">
                            </div>
                            <a href="job_details.php?slug=<?php echo $job['slug']; ?>"><h4><?php echo htmlspecialchars($job['title']); ?></h4></a>
                            <p><?php echo htmlspecialchars($job['organization']); ?></p>
                            <p><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                        </div>
                        <?php endwhile; ?>
                        <!-- Fallback if no featured jobs -->
                        <?php if($featured_jobs->num_rows == 0): ?>
                        <div class="single_candidates text-center">
                            <div class="thumb">
                                <img src="img/candiateds/1.png" alt="">
                            </div>
                            <a href="jobs.php"><h4>Check All Jobs</h4></a>
                            <p>Browse latest government jobs</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- featured_candidates_area_end  -->

    <div class="top_companies_area">
        <div class="container">
            <div class="row align-items-center mb-40">
                <div class="col-lg-6 col-md-6">
                    <div class="section_title">
                        <h3>Top Organizations</h3>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="brouse_job text-right">
                        <a href="jobs.php" class="boxed-btn4">Browse More Job</a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-xl-3 col-md-6">
                    <div class="single_company">
                        <div class="thumb">
                            <img src="img/svg_icon/5.svg" alt="">
                        </div>
                        <a href="jobs.php?search=Karnataka"><h3>Karnataka Govt</h3></a>
                        <p>State Government Jobs</p>
                    </div>
                </div>
                <div class="col-lg-4 col-xl-3 col-md-6">
                    <div class="single_company">
                        <div class="thumb">
                            <img src="img/svg_icon/4.svg" alt="">
                        </div>
                        <a href="jobs.php?search=APPSC"><h3>APPSC</h3></a>
                        <p>Andhra Pradesh PSC</p>
                    </div>
                </div>
                <div class="col-lg-4 col-xl-3 col-md-6">
                    <div class="single_company">
                        <div class="thumb">
                            <img src="img/svg_icon/3.svg" alt="">
                        </div>
                        <a href="jobs.php?search=KPSC"><h3>KPSC</h3></a>
                        <p>Karnataka PSC</p>
                    </div>
                </div>
                <div class="col-lg-4 col-xl-3 col-md-6">
                    <div class="single_company">
                        <div class="thumb">
                            <img src="img/svg_icon/1.svg" alt="">
                        </div>
                        <a href="jobs.php?category=3"><h3>Banking Jobs</h3></a>
                        <p>Public Sector Banks</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- job_searcing_wrap  -->
    <div class="job_searcing_wrap overlay">
        <div class="container">
            <div class="row">
                <div class="col-lg-5 offset-lg-1 col-md-6">
                    <div class="searching_text">
                        <h3>Looking for a Government Job?</h3>
                        <p>Find latest Sarkari Naukri in Karnataka and Andhra Pradesh</p>
                        <a href="jobs.php" class="boxed-btn3">Browse Job</a>
                    </div>
                </div>
                <div class="col-lg-5 offset-lg-1 col-md-6">
                    <div class="searching_text">
                        <h3>For Employers</h3>
                        <p>Post your government job notifications</p>
                        <a href="admin/login.php" class="boxed-btn3">Post a Job</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- job_searcing_wrap end  -->

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
    <script src="js/gijgo.min.js"></script>
    <script src="js/contact.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.form.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/mail-script.js"></script>
    <script src="js/main.js"></script>
</body>

</html>
