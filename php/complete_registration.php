<?php
/**
 * Complete Registration after OTP Verification
 * Creates user account and sends onboarding email
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';
// require_once 'onboarding_email.php'; // Temporarily disabled

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Debug log
error_log("[REGISTRATION] Received request");
error_log("[REGISTRATION] Input data: " . json_encode($input));

if (!$input) {
    error_log("[REGISTRATION] ERROR: Invalid JSON input");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Function to log activity
function logActivity($conn, $user_id, $action, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
    $stmt->execute();
    $stmt->close();
}

try {
    // Extract user data
    $firstName = mysqli_real_escape_string($conn, trim($input['firstName']));
    $lastName = mysqli_real_escape_string($conn, trim($input['lastName']));
    $email = mysqli_real_escape_string($conn, trim($input['email']));
    $phone = mysqli_real_escape_string($conn, trim($input['phone']));
    $role = mysqli_real_escape_string($conn, $input['role']);
    $password = $input['password'];
    
    error_log("[REGISTRATION] Validating user: $email with role: $role");
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($role) || empty($password)) {
        error_log("[REGISTRATION] ERROR: Missing required fields");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Validate role
    $allowedRoles = ['patient', 'doctor', 'admin'];
    if (!in_array($role, $allowedRoles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit();
    }
    
    // Check if email already exists (double-check)
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        $checkEmail->close();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit();
    }
    $checkEmail->close();
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Handle doctor-specific fields
    if ($role === 'doctor') {
        $specialization = mysqli_real_escape_string($conn, trim($input['specialization'] ?? ''));
        $department = mysqli_real_escape_string($conn, trim($input['department'] ?? ''));
        $license_number = mysqli_real_escape_string($conn, trim($input['license_number'] ?? ''));
        
        if (empty($specialization) || empty($department) || empty($license_number)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing doctor-specific fields']);
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, specialization, department, license_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $firstName, $lastName, $email, $hashedPassword, $role, $phone, $specialization, $department, $license_number);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $role, $phone);
    }
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        error_log("[REGISTRATION] SUCCESS: User created with ID: $user_id");
        
        // Log registration activity
        logActivity($conn, $user_id, "User Registration", "New $role registered via OTP verification");
        
        // Send onboarding email (temporarily disabled to avoid mail() issues)
        // $emailData = [
        //     'firstName' => $firstName,
        //     'lastName' => $lastName,
        //     'email' => $email,
        //     'role' => $role
        // ];
        
        // $emailSent = OnboardingEmailService::sendWelcomeEmail($emailData);
        
        // if (!$emailSent) {
        //     error_log("Failed to send onboarding email to: $email");
        //     // Don't fail registration if email fails
        // }
        
        error_log("[REGISTRATION] Sending success response for user ID: $user_id");
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration completed successfully!',
            'user_id' => $user_id,
            'role' => $role
        ]);
    } else {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again.'
    ]);
}

$conn->close();
?>
