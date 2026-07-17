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
$port = (int)env('DB_PORT', 3306);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for better character support
$conn->set_charset("utf8mb4");

// Verify if logged-in session user_id actually exists in the database
if (isset($_SESSION['user_id'])) {
    $session_user_id = (int)$_SESSION['user_id'];
    $session_check_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    if ($session_check_stmt) {
        $session_check_stmt->bind_param("i", $session_user_id);
        $session_check_stmt->execute();
        $session_check_res = $session_check_stmt->get_result();
        if ($session_check_res->num_rows === 0) {
            // User does not exist (stale session). Clear it!
            session_unset();
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
        }
        $session_check_stmt->close();
    }
}

// Set timezone from environment variables
$timezone = env('TIMEZONE', 'Asia/Kolkata');
$timezone_offset = env('TIMEZONE_OFFSET', '+05:30');
date_default_timezone_set($timezone);
$conn->query("SET time_zone = '$timezone_offset'");
?>
