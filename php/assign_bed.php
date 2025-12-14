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
    // Get POST data
    $patient_id = $_POST['patient_id'] ?? null;
    $bed_id = $_POST['bed_id'] ?? null;
    $priority = $_POST['priority'] ?? 'stable';
    $notes = $_POST['notes'] ?? '';
    $staff_id = $_SESSION['user_id'];
    
    if (!$patient_id || !$bed_id) {
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
    
    // Update patient record with bed assignment
    $update_patient = $conn->prepare("UPDATE admitted_patients SET bed_id = ?, priority = ?, assigned_staff_id = ?, assignment_notes = ? WHERE patient_id = ?");
    $update_patient->bind_param("isisi", $bed_id, $priority, $staff_id, $notes, $patient_id);
    
    if (!$update_patient->execute()) {
        throw new Exception('Failed to assign bed to patient');
    }
    
    // Update bed status to occupied
    $update_bed = $conn->prepare("UPDATE beds SET status = 'occupied' WHERE bed_id = ?");
    $update_bed->bind_param("i", $bed_id);
    
    if (!$update_bed->execute()) {
        throw new Exception('Failed to update bed status');
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Bed assigned successfully'
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
