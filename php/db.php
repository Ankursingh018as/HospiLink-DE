<?php
// db.php - Database connection file

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Start session if not already started and not running from command line
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

// Database credentials from environment variables
$servername = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$dbname = env('DB_NAME', 'hospilink');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for better character support
$conn->set_charset("utf8mb4");

// Set timezone from environment variables
$timezone = env('TIMEZONE', 'Asia/Kolkata');
$timezone_offset = env('TIMEZONE_OFFSET', '+05:30');
date_default_timezone_set($timezone);
$conn->query("SET time_zone = '$timezone_offset'");
?>
