<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get today's stats
$todayStatsQuery = "SELECT 
                        COUNT(*) as total_today,
                        COALESCE(SUM(CASE WHEN priority_level = 'critical' THEN 1 ELSE 0 END), 0) as critical_count,
                        COALESCE(SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END), 0) as high_count,
                        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count
                    FROM appointments 
                    WHERE (doctor_id = ? OR doctor_id IS NULL) 
                    AND appointment_date = ?";
$statsStmt = $conn->prepare($todayStatsQuery);
$statsStmt->bind_param("is", $user_id, $today);
$statsStmt->execute();
$todayStats = $statsStmt->get_result()->fetch_assoc();

// Get total appointments count (all time)
$totalQuery = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? OR doctor_id IS NULL";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bind_param("i", $user_id);
$totalStmt->execute();
$totalResult = $totalStmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'stats' => [
        'total_today' => (int)$todayStats['total_today'],
        'critical_count' => (int)$todayStats['critical_count'],
        'high_count' => (int)$todayStats['high_count'],
        'pending_count' => (int)$todayStats['pending_count'],
        'total_appointments' => (int)$totalResult['total']
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);

$statsStmt->close();
$totalStmt->close();
$conn->close();
?>
