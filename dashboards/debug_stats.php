<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'doctor') {
    echo "Not logged in as doctor";
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "User ID: " . $user_id . "<br>";
echo "Today: " . $today . "<br><br>";

// Get today's stats
$todayStatsQuery = "SELECT 
                        COUNT(*) as total_today,
                        COALESCE(SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END), 0) as high_count,
                        COALESCE(SUM(CASE WHEN priority_level = 'medium' THEN 1 ELSE 0 END), 0) as medium_count,
                        COALESCE(SUM(CASE WHEN priority_level = 'low' THEN 1 ELSE 0 END), 0) as low_count,
                        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count
                    FROM appointments 
                    WHERE (doctor_id = ? OR doctor_id IS NULL) 
                    AND appointment_date = ?";

$statsStmt = $conn->prepare($todayStatsQuery);
$statsStmt->bind_param("is", $user_id, $today);
$statsStmt->execute();
$todayStats = $statsStmt->get_result()->fetch_assoc();

echo "Raw stats from database:<br>";
print_r($todayStats);
echo "<br><br>";

// Ensure values are numbers, not null
$todayStats['total_today'] = intval($todayStats['total_today'] ?? 0);
$todayStats['critical_count'] = intval($todayStats['critical_count'] ?? 0);
$todayStats['high_count'] = intval($todayStats['high_count'] ?? 0);
$todayStats['pending_count'] = intval($todayStats['pending_count'] ?? 0);

echo "Converted stats:<br>";
echo "Total Today: " . $todayStats['total_today'] . "<br>";
echo "Critical: " . $todayStats['critical_count'] . "<br>";
echo "High: " . $todayStats['high_count'] . "<br>";
echo "Pending: " . $todayStats['pending_count'] . "<br><br>";

// Check all appointments for this doctor
$allQuery = "SELECT * FROM appointments WHERE doctor_id = ? OR doctor_id IS NULL";
$allStmt = $conn->prepare($allQuery);
$allStmt->bind_param("i", $user_id);
$allStmt->execute();
$allResults = $allStmt->get_result();

echo "Total appointments in database for doctor ID " . $user_id . ": " . $allResults->num_rows . "<br><br>";

if ($allResults->num_rows > 0) {
    echo "Sample appointments:<br>";
    while($apt = $allResults->fetch_assoc()) {
        echo "ID: " . $apt['appointment_id'] . " | Date: " . $apt['appointment_date'] . " | Priority: " . $apt['priority_level'] . " | Status: " . $apt['status'] . "<br>";
    }
}

$statsStmt->close();
$allStmt->close();
$conn->close();
?>
