<?php
require_once 'php/db.php';

echo "Starting database migration...\n\n";

// Read the SQL file
$sql = file_get_contents('database/allow_public_qr_access.sql');

// Remove comments
$sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Split by semicolons to get individual statements
$statements = explode(';', $sql);

$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    
    if (empty($statement) || strtoupper(substr($statement, 0, 3)) === 'USE') {
        continue;
    }
    
    echo "Executing: " . substr($statement, 0, 60) . "...\n";
    
    try {
        if ($conn->query($statement)) {
            echo "✓ Success\n\n";
            $success++;
        } else {
            echo "✗ Error: " . $conn->error . "\n\n";
            $errors++;
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n\n";
        $errors++;
    }
}

echo str_repeat("=", 50) . "\n";
echo "Migration complete!\n";
echo "Successful: $success\n";
echo "Errors: $errors\n";

$conn->close();
?>
