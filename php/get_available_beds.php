<?php
/**
 * Get Available Beds - Real-time API
 * Returns bed statistics and ward-wise breakdown
 * Works for both public and authenticated users
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

try {
    // Check if requesting detailed stats (for staff) or simple list
    $detailed = isset($_GET['detailed']) && $_GET['detailed'] === 'true';
    
    // Get overall bed statistics
    $statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
                  FROM beds";
    
    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
    
    // Get ward-wise breakdown
    $wardsQuery = "SELECT 
                    ward_name,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
                  FROM beds
                  GROUP BY ward_name
                  ORDER BY ward_name";
    
    $wardsResult = $conn->query($wardsQuery);
    $wards = [];
    
    while ($row = $wardsResult->fetch_assoc()) {
        $wards[] = [
            'name' => $row['ward_name'],
            'total' => (int)$row['total'],
            'available' => (int)$row['available'],
            'occupied' => (int)$row['occupied'],
            'maintenance' => (int)$row['maintenance']
        ];
    }
    
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'stats' => [
            'total' => (int)$stats['total'],
            'available' => (int)$stats['available'],
            'occupied' => (int)$stats['occupied'],
            'maintenance' => (int)$stats['maintenance']
        ],
        'wards' => $wards
    ];
    
    // If detailed view requested and user is staff, include bed list
    if ($detailed && isset($_SESSION['logged_in']) && in_array($_SESSION['user_role'], ['staff', 'nurse', 'admin'])) {
        $bedsQuery = "SELECT bed_id, ward_name, bed_number, bed_type, status 
                      FROM beds 
                      WHERE status = 'available' 
                      ORDER BY ward_name, bed_number";
        $bedsResult = $conn->query($bedsQuery);
        
        $beds = [];
        while ($row = $bedsResult->fetch_assoc()) {
            $beds[] = $row;
        }
        $response['beds'] = $beds;
    }
    
    // Return response
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching bed data: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
