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
    $discharge_summary = $_POST['discharge_summary'] ?? '';
    
    if (!$patient_id) {
        echo json_encode(['success' => false, 'message' => 'Missing patient ID']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get patient's bed assignment
    $get_bed = $conn->prepare("SELECT bed_id FROM admitted_patients WHERE patient_id = ?");
    $get_bed->bind_param("i", $patient_id);
    $get_bed->execute();
    $bed_result = $get_bed->get_result();
    
    if ($bed_result->num_rows === 0) {
        throw new Exception('Patient not found');
    }
    
    $bed_id = $bed_result->fetch_assoc()['bed_id'];
    
    // Update patient record with discharge date and summary
    $discharge_date = date('Y-m-d H:i:s');
    $update_patient = $conn->prepare("UPDATE admitted_patients SET discharge_date = ?, discharge_summary = ? WHERE patient_id = ?");
    $update_patient->bind_param("ssi", $discharge_date, $discharge_summary, $patient_id);
    
    if (!$update_patient->execute()) {
        throw new Exception('Failed to discharge patient');
    }
    
    // If patient had a bed assigned, free it up
    if ($bed_id) {
        $update_bed = $conn->prepare("UPDATE beds SET status = 'available' WHERE bed_id = ?");
        $update_bed->bind_param("i", $bed_id);
        
        if (!$update_bed->execute()) {
            throw new Exception('Failed to update bed status');
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Patient discharged successfully'
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
