<?php
require_once 'admin/db.php';

// Get job slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// Get job by slug
$job = null;
if ($slug) {
    $result = $conn->query("SELECT j.*, c.name as category_name 
        FROM jobs j 
        LEFT JOIN categories c ON j.category_id = c.id 
        WHERE j.slug = '$slug'");
    if ($result->num_rows > 0) {
        $job = $result->fetch_assoc();
        
        // Increment view count
        $conn->query("UPDATE jobs SET views = views + 1 WHERE id = " . $job['id']);
    }
}

// If job not found, redirect to jobs page
if (!$job) {
    header('Location: jobs.php');
    exit;
}

// Get categories for navigation
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
?>

<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($job['title']); ?> - Government Job</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($job['description'], 0, 200)); ?>">
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
                                            <li><a href="index.php">home</a></li>
                                            <li><a href="jobs.php">Browse Jobs</a></li>
                                            <li><a href="#">States <i class="ti-angle-down"></i></a>
                                                <ul class="submenu">
                                                    <li><a href="jobs.php?location=Karnataka">Karnataka Jobs</a></li>
                                                    <li><a href="jobs.php?location=Andhra+Pradesh">Andhra Pradesh Jobs</a></li>
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
                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ bradcam_area  -->

    <div class="job_details_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="job_details_header">
                        <div class="single_jobs white-bg d-flex justify-content-between">
                            <div class="jobs_left d-flex align-items-center">
                                <div class="thumb">
                                    <img src="img/svg_icon/1.svg" alt="">
                                </div>
                                <div class="jobs_conetent">
                                    <a href="#"><h4><?php echo htmlspecialchars($job['title']); ?></h4></a>
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
                                    <a class="heart_mark" href="#"> <i class="ti-heart"></i> </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="descript_wrap white-bg">
                        <div class="single_wrap">
                            <h4>Job description</h4>
                            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        </div>
                        <?php if($job['eligibility']): ?>
                        <div class="single_wrap">
                            <h4>Eligibility / Qualification</h4>
                            <p><?php echo nl2br(htmlspecialchars($job['eligibility'])); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if($job['how_to_apply']): ?>
                        <div class="single_wrap">
                            <h4>How to Apply</h4>
                            <p><?php echo nl2br(htmlspecialchars($job['how_to_apply'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Apply Button -->
                    <div class="apply_job_form white-bg mt-4">
                        <h4>Apply for this Job</h4>
                        <p>You will be redirected to the official website to apply for this job.</p>
                        <a href="<?php echo htmlspecialchars($job['official_website']); ?>" target="_blank" class="boxed-btn3 w-100 text-center">
                            <i class="fa fa-external-link"></i> Apply Now on Official Website
                        </a>
                        <p class="mt-2 text-muted"><small>Note: You will be redirected to <?php echo htmlspecialchars($job['organization']); ?> website to complete your application.</small></p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="job_sumary">
                        <div class="summery_header">
                            <h3>Job Summary</h3>
                        </div>
                        <div class="job_content">
                            <ul>
                                <li>Published on: <span><?php echo date('d M Y', strtotime($job['created_at'])); ?></span></li>
                                <li>Organization: <span><?php echo htmlspecialchars($job['organization']); ?></span></li>
                                <?php if($job['category_name']): ?>
                                <li>Category: <span><?php echo htmlspecialchars($job['category_name']); ?></span></li>
                                <?php endif; ?>
                                <?php if($job['vacancy_count']): ?>
                                <li>Vacancy: <span><?php echo htmlspecialchars($job['vacancy_count']); ?> Positions</span></li>
                                <?php endif; ?>
                                <?php if($job['salary_min'] || $job['salary_max']): ?>
                                <li>Salary: <span><?php echo htmlspecialchars($job['salary_min'] . ' - ' . $job['salary_max']); ?></span></li>
                                <?php endif; ?>
                                <li>Location: <span><?php echo htmlspecialchars($job['location']); ?></span></li>
                                <li>Job Nature: <span><?php echo htmlspecialchars($job['job_type'] ?: 'Full-time'); ?></span></li>
                                <?php if($job['application_fee']): ?>
                                <li>Application Fee: <span><?php echo htmlspecialchars($job['application_fee']); ?></span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Important Dates -->
                    <div class="job_sumary mt-4">
                        <div class="summery_header">
                            <h3>Important Dates</h3>
                        </div>
                        <div class="job_content">
                            <ul>
                                <?php if($job['form_start_date']): ?>
                                <li>Form Start: <span><?php echo date('d M Y', strtotime($job['form_start_date'])); ?></span></li>
                                <?php endif; ?>
                                <?php if($job['form_end_date']): ?>
                                <li>Last Date: <span><?php echo date('d M Y', strtotime($job['form_end_date'])); ?></span></li>
                                <?php endif; ?>
                                <?php if($job['exam_date']): ?>
                                <li>Exam Date: <span><?php echo date('d M Y', strtotime($job['exam_date'])); ?></span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Share -->
                    <div class="share_wrap d-flex mt-4">
                        <span>Share at:</span>
                        <ul>
                            <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank"> <i class="fa fa-facebook"></i></a> </li>
                            <li><a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank"> <i class="fa fa-twitter"></i></a> </li>
                            <li><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank"> <i class="fa fa-linkedin"></i></a> </li>
                            <li><a href="mailto:?subject=<?php echo urlencode($job['title']); ?>&body=<?php echo urlencode('Check out this job: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"> <i class="fa fa-envelope"></i></a> </li>
                        </ul>
                    </div>
                    
                    <!-- Related Jobs -->
                    <?php
                    // Get related jobs from same category
                    $related_jobs = $conn->query("SELECT * FROM jobs WHERE category_id = " . $job['category_id'] . " AND id != " . $job['id'] . " AND is_active = 1 ORDER BY created_at DESC LIMIT 5");
                    if($related_jobs->num_rows > 0):
                    ?>
                    <div class="job_location_wrap mt-4">
                        <div class="summery_header">
                            <h3>Related Jobs</h3>
                        </div>
                        <div class="job_lok_inner">
                            <?php while($related = $related_jobs->fetch_assoc()): ?>
                            <div class="single_jobs mb-3">
                                <a href="job_details.php?slug=<?php echo $related['slug']; ?>">
                                    <h5><?php echo htmlspecialchars($related['title']); ?></h5>
                                </a>
                                <p><?php echo htmlspecialchars($related['organization']); ?></p>
                                <p class="text-muted"><small>Last Date: <?php echo $related['form_end_date'] ? date('d M Y', strtotime($related['form_end_date'])) : 'N/A'; ?></small></p>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

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
