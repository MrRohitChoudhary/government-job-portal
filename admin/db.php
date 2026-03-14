<?php
/**
 * Database Configuration File
 * Government Job Portal
 */

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'job_portal');

// Create database connection
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL
define('BASE_URL', 'http://localhost/job-portal/');
define('ADMIN_BASE_URL', BASE_URL . 'admin/');
