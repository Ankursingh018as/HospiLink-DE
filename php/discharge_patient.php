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
    
    // First check if patient exists and get their current status
    $check_patient = $conn->prepare("
        SELECT pa.admission_id, pa.bed_id, pa.patient_id, pa.status, pa.discharge_date,
               CONCAT(u.first_name, ' ', u.last_name) as patient_name
        FROM patient_admissions pa
        JOIN users u ON pa.patient_id = u.user_id
        WHERE pa.patient_id = ?
        ORDER BY pa.admission_date DESC 
        LIMIT 1
    ");
    $check_patient->bind_param("i", $patient_id);
    $check_patient->execute();
    $check_result = $check_patient->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('No admission record found for this patient');
    }
    
    $patient_data = $check_result->fetch_assoc();
    
    // Check if already discharged
    if ($patient_data['status'] === 'discharged' || $patient_data['discharge_date'] !== null) {
        throw new Exception('Patient has already been discharged on ' . date('M d, Y', strtotime($patient_data['discharge_date'])));
    }
    
    // Check if status is active
    if ($patient_data['status'] !== 'active') {
        throw new Exception('Patient status is "' . $patient_data['status'] . '". Only active patients can be discharged.');
    }
    
    $admission_id = $patient_data['admission_id'];
    $bed_id = $patient_data['bed_id'];
    
    // Update admission record with discharge date
    $discharge_date = date('Y-m-d H:i:s');
    $update_admission = $conn->prepare("UPDATE patient_admissions SET discharge_date = ?, status = 'discharged' WHERE admission_id = ?");
    $update_admission->bind_param("si", $discharge_date, $admission_id);
    
    if (!$update_admission->execute()) {
        throw new Exception('Failed to discharge patient');
    }
    
    // Add discharge summary to medical history
    if (!empty($discharge_summary)) {
        $add_history = $conn->prepare("INSERT INTO medical_history (patient_id, diagnosis, treatment, visit_date, notes) VALUES (?, 'Discharge Summary', ?, ?, ?)");
        $visit_date = date('Y-m-d');
        $notes = "Admission ID: $admission_id";
        $add_history->bind_param("isss", $patient_id, $discharge_summary, $visit_date, $notes);
        $add_history->execute();
        $add_history->close();
    }
    
    // If patient had a bed assigned, free it up
    if ($bed_id) {
        $update_bed = $conn->prepare("UPDATE beds SET status = 'available', patient_id = NULL, admitted_date = NULL WHERE bed_id = ?");
        $update_bed->bind_param("i", $bed_id);
        
        if (!$update_bed->execute()) {
            throw new Exception('Failed to update bed status');
        }
        $update_bed->close();
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
