<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['staff', 'nurse'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$bed_id = $data['bed_id'] ?? null;
$status = $data['status'] ?? null;

if (!$bed_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

// Validate status
if (!in_array($status, ['available', 'occupied', 'maintenance'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE beds SET status = ? WHERE bed_id = ?");
    $stmt->bind_param("si", $status, $bed_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Bed status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update bed status']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
