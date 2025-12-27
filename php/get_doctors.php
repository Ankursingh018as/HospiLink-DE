<?php
/**
 * Get all active doctors with their credentials
 */
header('Content-Type: application/json');

require_once 'db.php';

$query = "SELECT 
            user_id,
            first_name,
            last_name,
            email,
            phone,
            specialization,
            department,
            license_number,
            status
          FROM users 
          WHERE role = 'doctor' 
          AND status = 'active'
          ORDER BY first_name, last_name";

$result = $conn->query($query);

$doctors = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

echo json_encode($doctors);

$conn->close();
?>
