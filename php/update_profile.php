<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get form data
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

// Role-specific fields
$age = intval($_POST['age'] ?? 0);
$gender = trim($_POST['gender'] ?? '');
$department = trim($_POST['department'] ?? '');
$staff_id = trim($_POST['staff_id'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$license_number = trim($_POST['license_number'] ?? '');

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit();
}

// Check if email is already taken by another user (email is not being changed, so skip this check)
/*
$checkEmailQuery = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
$stmt = $conn->prepare($checkEmailQuery);
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
    exit();
}
*/

// If password change is requested, verify current password
if (!empty($new_password)) {
    if (empty($current_password)) {
        echo json_encode(['success' => false, 'message' => 'Current password is required to change password']);
        exit();
    }
    
    // Verify current password
    $checkPasswordQuery = "SELECT password FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($checkPasswordQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update based on role with new password
    if ($user_role === 'staff') {
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, department = ?, staff_id = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $address, $department, $staff_id, $hashed_password, $user_id);
    } elseif ($user_role === 'doctor') {
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, specialization = ?, license_number = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssssi", $first_name, $last_name, $phone, $address, $specialization, $license_number, $hashed_password, $user_id);
    } else {
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, age = ?, gender = ?, address = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssissssi", $first_name, $last_name, $phone, $age, $gender, $address, $hashed_password, $user_id);
    }
} else {
    // Update based on role without password change
    if ($user_role === 'staff') {
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, department = ?, staff_id = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $address, $department, $staff_id, $user_id);
    } elseif ($user_role === 'doctor') {
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, specialization = ?, license_number = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $address, $specialization, $license_number, $user_id);
    } else {
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, age = ?, gender = ?, address = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssisssi", $first_name, $last_name, $phone, $age, $gender, $address, $user_id);
    }
}

if ($stmt->execute()) {
    // Update session variables
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    $_SESSION['email'] = $email;
    
    // Log activity
    $logQuery = "INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Profile Update', 'User updated their profile information')";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bind_param("i", $user_id);
    $logStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile: ' . $conn->error]);
}

$conn->close();
?>
