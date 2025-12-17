<?php
/**
 * Get Appointment Data for Admission Pre-fill
 * Returns appointment details to pre-populate the admission form
 */

session_start();
header('Content-Type: application/json');
require_once 'db.php';

try {
    if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'doctor') {
        throw new Exception('Unauthorized access');
    }

    $appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
    
    if ($appointment_id <= 0) {
        throw new Exception('Invalid appointment ID');
    }

    // Fetch appointment with patient details
    $query = "SELECT 
                a.*,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.age,
                u.gender as patient_gender,
                u.address,
                CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                d.user_id as doctor_id
              FROM appointments a
              JOIN users u ON a.patient_id = u.user_id
              LEFT JOIN users d ON a.doctor_id = d.user_id
              WHERE a.appointment_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Appointment not found');
    }
    
    $appointment = $result->fetch_assoc();
    
    // Parse AI analysis if available
    $ai_analysis = null;
    if (!empty($appointment['ai_analysis'])) {
        $ai_analysis = json_decode($appointment['ai_analysis'], true);
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'appointment_id' => $appointment['appointment_id'],
        'patient_id' => $appointment['patient_id'],
        'full_name' => $appointment['full_name'],
        'first_name' => $appointment['first_name'],
        'last_name' => $appointment['last_name'],
        'email' => $appointment['email'],
        'phone' => $appointment['phone'],
        'age' => $appointment['age'],
        'gender' => $appointment['patient_gender'] ?: $appointment['gender'],
        'address' => $appointment['address'],
        'symptoms' => $appointment['symptoms'],
        'priority_level' => $appointment['priority_level'],
        'priority_score' => $appointment['priority_score'],
        'appointment_date' => $appointment['appointment_date'],
        'appointment_time' => $appointment['appointment_time'],
        'doctor_id' => $appointment['doctor_id'],
        'doctor_name' => $appointment['doctor_name'],
        'ai_analysis' => $ai_analysis,
        'admission_reason' => $appointment['symptoms'] // Use symptoms as initial admission reason
    ];
    
    // Add suspected conditions if available from AI
    if ($ai_analysis && isset($ai_analysis['suspected_conditions'])) {
        $conditions = is_array($ai_analysis['suspected_conditions']) 
            ? implode(', ', $ai_analysis['suspected_conditions']) 
            : $ai_analysis['suspected_conditions'];
        $response['suspected_conditions'] = $conditions;
        $response['admission_reason'] = $appointment['symptoms'] . "\n\nSuspected: " . $conditions;
    }
    
    echo json_encode($response);
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
