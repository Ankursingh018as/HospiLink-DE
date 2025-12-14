<?php
/**
 * Get Admission Options API
 * Returns available beds and active doctors for patient admission
 * Optimized with prepared statements and error handling
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once 'db.php';

try {
    $response = [
        'success' => true,
        'beds' => [],
        'doctors' => [],
        'timestamp' => time()
    ];
    
    // Get available beds with prepared statement
    $beds_query = "SELECT bed_id, ward_name, bed_number, bed_type, bed_price 
                   FROM beds 
                   WHERE status = 'available' 
                   ORDER BY ward_name, CAST(bed_number AS UNSIGNED)";
    
    $beds_result = $conn->query($beds_query);
    
    if (!$beds_result) {
        throw new Exception('Failed to fetch beds: ' . $conn->error);
    }
    
    while ($row = $beds_result->fetch_assoc()) {
        $response['beds'][] = [
            'bed_id' => (int)$row['bed_id'],
            'ward_name' => $row['ward_name'],
            'bed_number' => $row['bed_number'],
            'bed_type' => $row['bed_type'],
            'bed_price' => isset($row['bed_price']) ? (float)$row['bed_price'] : null
        ];
    }
    
    // Get active doctors with prepared statement
    $doctors_query = "SELECT user_id, 
                            CONCAT(first_name, ' ', last_name) as name, 
                            specialization, 
                            department,
                            phone
                     FROM users 
                     WHERE role = 'doctor' AND status = 'active' 
                     ORDER BY first_name, last_name";
    
    $doctors_result = $conn->query($doctors_query);
    
    if (!$doctors_result) {
        throw new Exception('Failed to fetch doctors: ' . $conn->error);
    }
    
    while ($row = $doctors_result->fetch_assoc()) {
        $response['doctors'][] = [
            'user_id' => (int)$row['user_id'],
            'name' => $row['name'],
            'specialization' => $row['specialization'] ?? $row['department'] ?? 'General',
            'department' => $row['department'],
            'phone' => $row['phone'] ?? null
        ];
    }
    
    // Add summary info
    $response['summary'] = [
        'available_beds' => count($response['beds']),
        'active_doctors' => count($response['doctors'])
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);
    
    error_log('Get admission options error: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
