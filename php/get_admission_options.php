<?php
header('Content-Type: application/json');
require_once 'db.php';

// Get available beds
$beds_query = "SELECT bed_id, ward_name, bed_number, bed_type FROM beds WHERE status = 'available' ORDER BY ward_name, bed_number";
$beds_result = $conn->query($beds_query);
$beds = [];

while ($row = $beds_result->fetch_assoc()) {
    $beds[] = $row;
}

// Get active doctors
$doctors_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as name, specialization, department FROM users WHERE role = 'doctor' AND status = 'active' ORDER BY first_name";
$doctors_result = $conn->query($doctors_query);
$doctors = [];

while ($row = $doctors_result->fetch_assoc()) {
    $doctors[] = $row;
}

$conn->close();

echo json_encode([
    'success' => true,
    'beds' => $beds,
    'doctors' => $doctors
]);
?>
