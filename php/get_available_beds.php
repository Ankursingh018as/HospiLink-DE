<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is staff
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['staff', 'nurse'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include 'db.php';

try {
    // Get all available beds
    $query = "SELECT bed_id, ward_name, bed_number, bed_type 
              FROM beds 
              WHERE status = 'available' 
              ORDER BY ward_name, bed_number";
    
    $result = $conn->query($query);
    
    $beds = [];
    while ($row = $result->fetch_assoc()) {
        $beds[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'beds' => $beds
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching beds: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
