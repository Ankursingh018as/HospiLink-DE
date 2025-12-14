<?php
include 'db.php';

echo "Checking activity_logs table...\n\n";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'activity_logs'");
if ($tableCheck->num_rows > 0) {
    echo "✓ activity_logs table exists\n\n";
    
    // Count total logs
    $countResult = $conn->query("SELECT COUNT(*) as count FROM activity_logs");
    $count = $countResult->fetch_assoc();
    echo "Total activity logs: " . $count['count'] . "\n\n";
    
    // Get sample logs
    $sampleLogs = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5");
    
    if ($sampleLogs->num_rows > 0) {
        echo "Sample logs:\n";
        echo str_repeat("-", 80) . "\n";
        while($log = $sampleLogs->fetch_assoc()) {
            echo "ID: " . $log['log_id'] . "\n";
            echo "User ID: " . $log['user_id'] . "\n";
            echo "Action: " . $log['action'] . "\n";
            echo "Details: " . ($log['details'] ?: 'N/A') . "\n";
            echo "Created: " . $log['created_at'] . "\n";
            echo str_repeat("-", 80) . "\n";
        }
    } else {
        echo "⚠ No activity logs found in the table!\n";
        echo "The table is empty. Activity logs should be inserted when users perform actions.\n";
    }
    
    // Check table structure
    echo "\nTable structure:\n";
    $structure = $conn->query("DESCRIBE activity_logs");
    while($field = $structure->fetch_assoc()) {
        echo "- " . $field['Field'] . " (" . $field['Type'] . ")\n";
    }
    
} else {
    echo "✗ activity_logs table does NOT exist!\n";
    echo "You need to create this table in the database.\n";
}
?>
