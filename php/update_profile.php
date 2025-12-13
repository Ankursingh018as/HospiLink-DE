<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];

// Get form data
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$age = intval($_POST['age'] ?? 0);
$gender = trim($_POST['gender'] ?? '');
$address = trim($_POST['address'] ?? '');
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit();
}

// Check if email is already taken by another user
$checkEmailQuery = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
$stmt = $conn->prepare($checkEmailQuery);
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
    exit();
}

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
    
    // Update with new password
    $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, age = ?, gender = ?, address = ?, password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssisssi", $first_name, $last_name, $email, $phone, $age, $gender, $address, $hashed_password, $user_id);
} else {
    // Update without password change
    $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, age = ?, gender = ?, address = ? WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssissi", $first_name, $last_name, $email, $phone, $age, $gender, $address, $user_id);
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
