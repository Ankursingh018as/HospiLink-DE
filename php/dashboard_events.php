<?php
/**
 * Dashboard Events - Server-Sent Events (SSE) Endpoint
 * Provides real-time updates for doctor dashboard
 * Streams new high-priority appointments and status changes
 */

session_start();
require_once 'db.php';

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Verify authentication
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    echo "event: error\n";
    echo "data: {\"error\": \"Unauthorized\"}\n\n";
    ob_flush();
    flush();
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$last_check = time();

// Keep connection alive
set_time_limit(0);
ob_implicit_flush(true);

// Track last known appointment ID to detect new appointments
$last_appointment_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// Send initial connection confirmation
echo "event: connected\n";
echo "data: {\"status\": \"connected\", \"role\": \"$user_role\"}\n\n";
ob_flush();
flush();

// Main event loop
while (true) {
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }
    
    try {
        // Check for new high-priority appointments
        if ($user_role === 'doctor') {
            $query = "SELECT a.*, 
                      CONCAT(u.first_name, ' ', u.last_name) as patient_name
                      FROM appointments a
                      JOIN users u ON a.patient_id = u.user_id
                      WHERE a.appointment_id > ? 
                      AND (a.doctor_id = ? OR a.doctor_id IS NULL)
                      AND a.priority_level IN ('high', 'critical')
                      AND a.status = 'pending'
                      ORDER BY a.appointment_id DESC
                      LIMIT 5";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $last_appointment_id, $user_id);
        } else {
            // Admin sees all new high-priority appointments
            $query = "SELECT a.*, 
                      CONCAT(u.first_name, ' ', u.last_name) as patient_name
                      FROM appointments a
                      JOIN users u ON a.patient_id = u.user_id
                      WHERE a.appointment_id > ? 
                      AND a.priority_level IN ('high', 'critical')
                      AND a.status = 'pending'
                      ORDER BY a.appointment_id DESC
                      LIMIT 5";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $last_appointment_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($appointment = $result->fetch_assoc()) {
                // Send new appointment event
                $event_data = [
                    'type' => 'new_appointment',
                    'appointment_id' => $appointment['appointment_id'],
                    'patient_name' => $appointment['patient_name'],
                    'priority_level' => $appointment['priority_level'],
                    'priority_score' => $appointment['priority_score'],
                    'symptoms' => substr($appointment['symptoms'], 0, 100) . '...',
                    'appointment_date' => $appointment['appointment_date'],
                    'appointment_time' => $appointment['appointment_time'],
                    'timestamp' => time()
                ];
                
                echo "event: new_appointment\n";
                echo "data: " . json_encode($event_data) . "\n\n";
                ob_flush();
                flush();
                
                // Update last appointment ID
                if ($appointment['appointment_id'] > $last_appointment_id) {
                    $last_appointment_id = $appointment['appointment_id'];
                }
            }
        }
        
        $stmt->close();
        
        // Send heartbeat every 30 seconds
        if (time() - $last_check >= 30) {
            echo "event: heartbeat\n";
            echo "data: {\"time\": " . time() . "}\n\n";
            ob_flush();
            flush();
            $last_check = time();
        }
        
    } catch (Exception $e) {
        echo "event: error\n";
        echo "data: {\"error\": \"" . addslashes($e->getMessage()) . "\"}\n\n";
        ob_flush();
        flush();
    }
    
    // Sleep for 5 seconds before next check
    sleep(5);
}

$conn->close();
?>
