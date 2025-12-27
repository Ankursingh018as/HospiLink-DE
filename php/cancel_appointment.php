<?php
/**
 * Cancel Appointment API
 * Allows patients to cancel their appointments
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'patient') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once 'db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Validate required fields
$appointment_id = isset($input['appointment_id']) ? intval($input['appointment_id']) : 0;
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($appointment_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit();
}

$patient_id = $_SESSION['user_id'];

// Verify the appointment belongs to this patient and is not already cancelled/completed
$checkStmt = $conn->prepare("SELECT status, appointment_date, appointment_time FROM appointments WHERE appointment_id = ? AND patient_id = ?");
$checkStmt->bind_param("ii", $appointment_id, $patient_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit();
}

$appointment = $result->fetch_assoc();

if ($appointment['status'] === 'cancelled') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Appointment is already cancelled']);
    exit();
}

if ($appointment['status'] === 'completed') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot cancel a completed appointment']);
    exit();
}

$checkStmt->close();

// Update appointment status to cancelled
$updateStmt = $conn->prepare("
    UPDATE appointments 
    SET status = 'cancelled',
        updated_at = NOW()
    WHERE appointment_id = ? AND patient_id = ?
");

$updateStmt->bind_param("ii", $appointment_id, $patient_id);

if ($updateStmt->execute()) {
    // If appointment had a bed assigned, release it
    $releaseStmt = $conn->prepare("
        UPDATE beds 
        SET status = 'available', 
            patient_id = NULL 
        WHERE bed_id IN (
            SELECT bed_id 
            FROM patient_admissions 
            WHERE appointment_id = ?
        )
    ");
    $releaseStmt->bind_param("i", $appointment_id);
    $releaseStmt->execute();
    $releaseStmt->close();
    
    // Update patient admission status if exists
    $admissionStmt = $conn->prepare("
        UPDATE patient_admissions 
        SET status = 'cancelled' 
        WHERE appointment_id = ?
    ");
    $admissionStmt->bind_param("i", $appointment_id);
    $admissionStmt->execute();
    $admissionStmt->close();
    
    // Log the activity
    if (function_exists('logActivity')) {
        $details = "Cancelled appointment #$appointment_id scheduled for " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'];
        if (!empty($reason)) {
            $details .= " - Reason: $reason";
        }
        logActivity($conn, $patient_id, "Appointment Cancelled", $details);
    } else {
        // Manual activity log
        $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $action = "Appointment Cancelled";
        $details = "Cancelled appointment #$appointment_id scheduled for " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'];
        if (!empty($reason)) {
            $details .= " - Reason: $reason";
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("isss", $patient_id, $action, $details, $ip);
        $logStmt->execute();
        $logStmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment cancelled successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment: ' . $conn->error]);
}

$updateStmt->close();
$conn->close();
?>
