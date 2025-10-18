<!-- db.php -->
<?php
// Start session if not already started and not running from command line
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

$servername = "localhost";
$username = "root";  // Default username for XAMPP/WAMP
$password = "";      // No password for XAMPP default installation
$dbname = "hospilink"; // Updated database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for better character support
$conn->set_charset("utf8mb4");
?>
