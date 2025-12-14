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

// Select fields based on role
if ($user_role === 'staff') {
    $query = "SELECT first_name, last_name, email, phone, address, department, staff_id FROM users WHERE user_id = ?";
} elseif ($user_role === 'doctor') {
    $query = "SELECT first_name, last_name, email, phone, address, specialization, license_number FROM users WHERE user_id = ?";
} else {
    $query = "SELECT first_name, last_name, email, phone, age, gender, address FROM users WHERE user_id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$conn->close();
?>
