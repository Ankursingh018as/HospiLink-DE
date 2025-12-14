<?php
/**
 * Patient Admission Processing
 * Handles patient admission with QR code generation
 * Optimized with transaction support and comprehensive validation
 */

require_once 'db.php';
require_once 'patient_qr_helper.php';

// Enable error reporting for debugging (disable in production)
if (env('APP_DEBUG', 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Validate and sanitize input
        $patient_name = trim($_POST['patient_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $gender = trim($_POST['gender'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $admit_date = trim($_POST['admit_date'] ?? '');
        $blood_group = trim($_POST['blood_group'] ?? '');
        $disease_description = trim($_POST['disease'] ?? '');
        $phone_number = preg_replace('/[^0-9]/', '', $_POST['phno'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validate required fields
        if (empty($patient_name) || empty($email) || empty($phone_number)) {
            throw new Exception('Patient name, email, and phone number are required');
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }
        
        // Validate phone number (10 digits)
        if (!preg_match('/^[6-9]\d{9}$/', $phone_number)) {
            throw new Exception('Invalid phone number. Must be 10 digits starting with 6-9');
        }
        
        // Validate dates
        $dob_date = new DateTime($dob);
        $admit_date_obj = new DateTime($admit_date);
        $today = new DateTime();
        
        if ($dob_date >= $today) {
            throw new Exception('Date of birth must be in the past');
        }
        
        if ($admit_date_obj < new DateTime('today')) {
            throw new Exception('Admission date cannot be in the past');
        }
        
        // Get bed and doctor if specified
        $bed_id = !empty($_POST['bed_id']) ? intval($_POST['bed_id']) : null;
        $doctor_id = !empty($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
    
        // Split patient name into first and last name
        $name_parts = explode(' ', $patient_name, 2);
        $first_name = mysqli_real_escape_string($conn, trim($name_parts[0]));
        $last_name = isset($name_parts[1]) ? mysqli_real_escape_string($conn, trim($name_parts[1])) : '';
        
        // Check if patient already exists by email or phone
        $check_stmt = $conn->prepare("SELECT user_id, email, phone, first_name, last_name FROM users WHERE (email = ? OR phone = ?) AND role = 'patient'");
        $check_stmt->bind_param("ss", $email, $phone_number);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Patient exists - update information if needed
            $row = $result->fetch_assoc();
            $user_id = $row['user_id'];
            
            // Check if any information needs updating
            $needs_update = (
                $row['email'] !== $email || 
                $row['phone'] !== $phone_number ||
                $row['first_name'] !== $first_name ||
                $row['last_name'] !== $last_name
            );
            
            if ($needs_update) {
                $update_stmt = $conn->prepare("UPDATE users SET email = ?, first_name = ?, last_name = ?, phone = ?, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("ssssi", $email, $first_name, $last_name, $phone_number, $user_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception('Failed to update patient information: ' . $conn->error);
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        } else {
            // Create new patient record
            // Generate secure temporary password
            $temp_password = password_hash('Temp@' . bin2hex(random_bytes(4)), PASSWORD_BCRYPT);
            
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, gender, status, created_at) VALUES (?, ?, ?, ?, 'patient', ?, ?, 'active', NOW())");
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $temp_password, $phone_number, $gender);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create patient record: ' . $conn->error);
            }
            
            $user_id = $stmt->insert_id;
            $stmt->close();
        }
    
        // Validate bed availability if bed is assigned
        if ($bed_id) {
            $bed_check = $conn->prepare("SELECT status FROM beds WHERE bed_id = ?");
            $bed_check->bind_param("i", $bed_id);
            $bed_check->execute();
            $bed_result = $bed_check->get_result();
            
            if ($bed_result->num_rows === 0) {
                throw new Exception('Selected bed does not exist');
            }
            
            $bed_data = $bed_result->fetch_assoc();
            if ($bed_data['status'] !== 'available') {
                throw new Exception('Selected bed is not available');
            }
            $bed_check->close();
        }
        
        // Validate doctor exists if assigned
        if ($doctor_id) {
            $doc_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'doctor' AND status = 'active'");
            $doc_check->bind_param("i", $doctor_id);
            $doc_check->execute();
            $doc_result = $doc_check->get_result();
            
            if ($doc_result->num_rows === 0) {
                throw new Exception('Selected doctor is not available');
            }
            $doc_check->close();
        }
        
        // Create admission with QR code
        $admission_result = PatientQRHelper::createAdmission($conn, $user_id, $bed_id, $disease_description, $doctor_id);
        
        if (!$admission_result['success']) {
            throw new Exception('Failed to create admission: ' . ($admission_result['error'] ?? 'Unknown error'));
        }
        
        $admission_id = $admission_result['admission_id'];
        $qr_token = $admission_result['qr_token'];
        
        // Create initial medical history entry
        $history_stmt = $conn->prepare("INSERT INTO medical_history (patient_id, diagnosis, treatment, visit_date, notes) VALUES (?, ?, ?, ?, ?)");
        $diagnosis = "Patient admitted with: " . mysqli_real_escape_string($conn, $disease_description);
        $treatment = "Under observation";
        $visit_date = date('Y-m-d');
        $notes = "DOB: $dob, Blood Group: $blood_group, Gender: $gender, Address: " . mysqli_real_escape_string($conn, $address) . ", Admission ID: $admission_id";
        
        $history_stmt->bind_param("issss", $user_id, $diagnosis, $treatment, $visit_date, $notes);
        
        if (!$history_stmt->execute()) {
            throw new Exception('Failed to create medical history: ' . $conn->error);
        }
        $history_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log successful admission
        error_log("Patient admitted successfully - Admission ID: $admission_id, Patient ID: $user_id");
        
        // Redirect to QR code print page with success message
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Admission Successful</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; }
                .success-msg {
                    background: white;
                    padding: 30px;
                    margin: 50px auto;
                    max-width: 500px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .success-msg h2 { color: #4CAF50; margin-bottom: 20px; }
                .info { margin: 15px 0; padding: 10px; background: #f0f0f0; border-radius: 4px; }
                .btn { 
                    background: #00adb5; 
                    color: white; 
                    padding: 12px 30px; 
                    border: none; 
                    border-radius: 4px; 
                    cursor: pointer; 
                    font-size: 16px;
                    margin-top: 20px;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn:hover { background: #008c94; }
            </style>
        </head>
        <body>
            <div class='success-msg'>
                <h2>✓ Patient Admitted Successfully!</h2>
                <div class='info'><strong>Patient Name:</strong> $patient_name</div>
                <div class='info'><strong>Admission ID:</strong> $admission_id</div>
                <div class='info'><strong>Admission Date:</strong> " . date('d M Y', strtotime($admit_date)) . "</div>
                <p style='margin-top:20px; color:#666;'>QR code has been generated for bedside monitoring</p>
                <a href='../qr-print.php?admission_id=$admission_id' class='btn'>Print QR Code</a>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '../qr-print.php?admission_id=$admission_id';
                }, 3000);
            </script>
        </body>
        </html>";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log error
        error_log("Patient admission error: " . $e->getMessage());
        
        // Show user-friendly error
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Admission Failed</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; }
                .error-msg {
                    background: white;
                    padding: 30px;
                    margin: 50px auto;
                    max-width: 500px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .error-msg h2 { color: #f44336; margin-bottom: 20px; }
                .btn { 
                    background: #00adb5; 
                    color: white; 
                    padding: 12px 30px; 
                    border: none; 
                    border-radius: 4px; 
                    cursor: pointer; 
                    font-size: 16px;
                    margin-top: 20px;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn:hover { background: #008c94; }
            </style>
        </head>
        <body>
            <div class='error-msg'>
                <h2>✗ Admission Failed</h2>
                <p style='color:#666;margin:20px 0;'>" . htmlspecialchars($e->getMessage()) . "</p>
                <a href='../admit.html' class='btn'>Try Again</a>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '../admit.html';
                }, 5000);
            </script>
        </body>
        </html>";
    } finally {
        $conn->close();
    }
} else {
    // Handle non-POST requests
    header("Location: ../admit.html");
    exit();
}
?>
