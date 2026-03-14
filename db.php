<?php
// Prevent direct access to errors in production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set default timezone for dates (matches India time per original app scope)
date_default_timezone_set('Asia/Kolkata');

// Database Configuration
$db_file = __DIR__ . '/jobportal.db';

// Create (connect to) SQLite database
try {
    $pdo = new PDO("sqlite:" . $db_file);
    // Set errormode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Performance configurations for SQLite based on original Node logic
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA temp_store = MEMORY');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    
} catch (PDOException $e) {
    // If connection fails, log it and return severe JSON error
    error_log('Database Connection failed: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed. Please contact administrator.']);
    exit;
}

// Function to generate a slug from a title (matches original Node.js slug logic)
function generateSlug($title) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    return $slug . '-' . base_convert(time(), 10, 36);
}

// Ensure the request sends JSON response type for APIs
function setJsonHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
}
?>
