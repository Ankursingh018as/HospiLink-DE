<?php
// Update appointment status and notes
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['appointment_id'])) {
        $appointment_id = intval($_POST['appointment_id']);
        
        // Update status
        if (isset($_POST['status'])) {
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            $updateQuery = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $status, $appointment_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Status updated successfully';
                
                // Log activity
                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];
                    $logQuery = "INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)";
                    $logStmt = $conn->prepare($logQuery);
                    $action = "Updated appointment status";
                    $details = "Appointment #$appointment_id status changed to $status";
                    $logStmt->bind_param("iss", $user_id, $action, $details);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } else {
                $response['message'] = 'Failed to update status';
            }
            $stmt->close();
        }
        
        // Update doctor notes
        if (isset($_POST['doctor_notes'])) {
            $notes = mysqli_real_escape_string($conn, $_POST['doctor_notes']);
            $updateQuery = "UPDATE appointments SET doctor_notes = ? WHERE appointment_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $notes, $appointment_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Notes saved successfully';
            } else {
                $response['message'] = 'Failed to save notes';
            }
            $stmt->close();
        }
    } else {
        $response['message'] = 'Appointment ID is required';
    }
    
    echo json_encode($response);
    $conn->close();
}
?>
