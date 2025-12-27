<?php
/**
 * Reschedule Appointment API
 * Allows patients to reschedule their appointments
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
$new_date = isset($input['new_date']) ? trim($input['new_date']) : '';
$new_time = isset($input['new_time']) ? trim($input['new_time']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($appointment_id <= 0 || empty($new_date) || empty($new_time)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Validate date format
$dateObj = DateTime::createFromFormat('Y-m-d', $new_date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $new_date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

// Check if date is in the future
$today = new DateTime();
$today->setTime(0, 0, 0);
if ($dateObj < $today) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot reschedule to a past date']);
    exit();
}

// Validate time format
if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $new_time)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit();
}

$patient_id = $_SESSION['user_id'];

// Verify the appointment belongs to this patient and is not cancelled/completed
$checkStmt = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ? AND patient_id = ?");
$checkStmt->bind_param("ii", $appointment_id, $patient_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit();
}

$appointment = $result->fetch_assoc();

if ($appointment['status'] === 'cancelled' || $appointment['status'] === 'completed') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot reschedule a ' . $appointment['status'] . ' appointment']);
    exit();
}

$checkStmt->close();

// Update the appointment
$updateStmt = $conn->prepare("
    UPDATE appointments 
    SET appointment_date = ?, 
        appointment_time = ?,
        status = 'pending',
        updated_at = NOW()
    WHERE appointment_id = ? AND patient_id = ?
");

$updateStmt->bind_param("ssii", $new_date, $new_time, $appointment_id, $patient_id);

if ($updateStmt->execute()) {
    // Log the activity
    if (function_exists('logActivity')) {
        $details = "Rescheduled appointment #$appointment_id to $new_date $new_time";
        if (!empty($reason)) {
            $details .= " - Reason: $reason";
        }
        logActivity($conn, $patient_id, "Appointment Rescheduled", $details);
    } else {
        // Manual activity log
        $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $action = "Appointment Rescheduled";
        $details = "Rescheduled appointment #$appointment_id to $new_date $new_time";
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
        'message' => 'Appointment rescheduled successfully',
        'new_date' => $new_date,
        'new_time' => $new_time
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to reschedule appointment: ' . $conn->error]);
}

$updateStmt->close();
$conn->close();
?>
