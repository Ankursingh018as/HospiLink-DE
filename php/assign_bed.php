<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is staff
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['staff', 'nurse'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include 'db.php';

try {
    // Get POST data - using admission_id instead of patient_id
    $admission_id = $_POST['patient_id'] ?? null; // This is actually admission_id from the form
    $bed_id = $_POST['bed_id'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $notes = $_POST['notes'] ?? '';
    $staff_id = $_SESSION['user_id'];
    
    if (!$admission_id || !$bed_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if bed is still available
    $check_bed = $conn->prepare("SELECT status FROM beds WHERE bed_id = ?");
    $check_bed->bind_param("i", $bed_id);
    $check_bed->execute();
    $bed_result = $check_bed->get_result();
    
    if ($bed_result->num_rows === 0) {
        throw new Exception('Bed not found');
    }
    
    $bed_status = $bed_result->fetch_assoc()['status'];
    if ($bed_status !== 'available') {
        throw new Exception('Bed is no longer available');
    }
    $check_bed->close();
    
    // Get patient_id from admission
    $get_patient = $conn->prepare("SELECT patient_id FROM patient_admissions WHERE admission_id = ?");
    $get_patient->bind_param("i", $admission_id);
    $get_patient->execute();
    $patient_result = $get_patient->get_result();
    
    if ($patient_result->num_rows === 0) {
        throw new Exception('Admission not found');
    }
    
    $patient_id = $patient_result->fetch_assoc()['patient_id'];
    $get_patient->close();
    
    // Update patient_admissions record with bed assignment
    $update_admission = $conn->prepare("UPDATE patient_admissions SET bed_id = ?, updated_at = NOW() WHERE admission_id = ?");
    $update_admission->bind_param("ii", $bed_id, $admission_id);
    
    if (!$update_admission->execute()) {
        throw new Exception('Failed to assign bed to patient');
    }
    $update_admission->close();
    
    // Update bed status to occupied
    $update_bed = $conn->prepare("UPDATE beds SET status = 'occupied', patient_id = ?, admitted_date = NOW() WHERE bed_id = ?");
    $update_bed->bind_param("ii", $patient_id, $bed_id);
    
    if (!$update_bed->execute()) {
        throw new Exception('Failed to update bed status');
    }
    $update_bed->close();
    
    // Log activity
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $action = "Bed Assignment";
    $details = "Assigned bed #$bed_id to admission #$admission_id. Priority: $priority. Notes: $notes";
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("isss", $staff_id, $action, $details, $ip);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Bed assigned successfully',
        'admission_id' => $admission_id,
        'bed_id' => $bed_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
