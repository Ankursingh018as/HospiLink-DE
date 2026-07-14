<?php
/**
 * HospiLink Notification Bell API
 * GET  /php/notifications_api.php          — list notifications for current role
 * GET  /php/notifications_api.php?action=count — unread count
 * POST /php/notifications_api.php?action=read&id=X — mark as read
 * POST /php/notifications_api.php?action=read_all  — mark all read
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/drip_monitor.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Run dynamic check on all running IV drips
DripMonitor::checkAllDrips($conn);

// Ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS hospi_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    target_role VARCHAR(20),
    admission_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (target_role),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_GET['action'] ?? 'list';
$role   = $_SESSION['user_role'] ?? 'guest';

// Map roles: admin sees everything, others see their role + 'all'
$roleFilter = "'$role', 'all'";
if ($role === 'admin') $roleFilter = "'admin','staff','nurse','doctor','all'";
if ($role === 'doctor') $roleFilter = "'doctor','all'";
if ($role === 'staff' || $role === 'nurse') $roleFilter = "'staff','nurse','all'";
if ($role === 'patient') $roleFilter = "'patient','all'";

if ($action === 'count') {
    $result = $conn->query("SELECT COUNT(*) AS cnt FROM hospi_notifications WHERE is_read = 0 AND target_role IN ($roleFilter)");
    $row = $result->fetch_assoc();
    echo json_encode(['count' => (int)$row['cnt']]);
    exit;
}

if ($action === 'read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE hospi_notifications SET is_read = 1 WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'read_all') {
    $conn->query("UPDATE hospi_notifications SET is_read = 1 WHERE target_role IN ($roleFilter)");
    echo json_encode(['success' => true]);
    exit;
}

// Default: list last 50 notifications
$result = $conn->query("SELECT id, type, title, message, target_role, admission_id, is_read,
    DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') AS created_fmt,
    created_at
    FROM hospi_notifications
    WHERE target_role IN ($roleFilter)
    ORDER BY created_at DESC
    LIMIT 50");

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $row['is_read'] = (bool)$row['is_read'];
    $notifications[] = $row;
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
