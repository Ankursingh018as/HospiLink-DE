<?php
/**
 * OTP API Endpoint for HospiLink
 * Handles OTP generation, verification, and resend requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';
require_once 'otp_service.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

$action = $input['action'] ?? '';
$otpService = new OTPService($conn);

switch ($action) {
    case 'generate':
        // Validate required fields
        $requiredFields = ['firstName', 'lastName', 'email', 'phone', 'role', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit();
            }
        }
        
        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }
        
        // Validate role (allow staff and nurse)
        $allowedRoles = ['patient', 'doctor', 'admin', 'staff', 'nurse'];
        if (!in_array($input['role'], $allowedRoles)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            exit();
        }
        
        // Validate doctor-specific fields
        if ($input['role'] === 'doctor') {
            if (empty($input['specialization']) || empty($input['department']) || empty($input['license_number'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing doctor-specific fields']);
                exit();
            }
        }
        
        // Prepare user data
        $userData = [
            'firstName' => trim($input['firstName']),
            'lastName' => trim($input['lastName']),
            'email' => trim($input['email']),
            'phone' => trim($input['phone']),
            'role' => $input['role'],
            'password' => $input['password']
        ];
        
        // Add doctor-specific fields
        if ($input['role'] === 'doctor') {
            $userData['specialization'] = trim($input['specialization']);
            $userData['department'] = trim($input['department']);
            $userData['license_number'] = trim($input['license_number']);
        }
        
        // Add staff-specific fields
        if ($input['role'] === 'staff' || $input['role'] === 'nurse') {
            $userData['department'] = !empty($input['staff_department']) ? trim($input['staff_department']) : 'General Ward';
            $userData['staff_id'] = !empty($input['staff_id']) ? trim($input['staff_id']) : 'STAFF' . time();
        }
        
        $result = $otpService->generateAndSendOTP($userData);
        echo json_encode($result);
        break;
        
    case 'verify':
        // Validate required fields
        if (empty($input['email']) || empty($input['otp'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
            exit();
        }
        
        // Trim and clean OTP
        $otp = trim($input['otp']);
        
        // Validate OTP format (6 digits)
        if (!preg_match('/^\d{6}$/', $otp)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid OTP format. Must be 6 digits.']);
            exit();
        }
        
        $result = $otpService->verifyOTP($input['email'], $otp);
        echo json_encode($result);
        break;
        
    case 'resend':
        // Validate required fields
        if (empty($input['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit();
        }
        
        $result = $otpService->resendOTP($input['email']);
        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
?>
