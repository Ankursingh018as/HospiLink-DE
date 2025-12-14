<?php
/**
 * Quick Database Installer for QR Patient Management Tables
 * Run this once to create all required tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once 'php/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>HospiLink QR Tables Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid red; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid blue; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-left: 3px solid #333; overflow-x: auto; }
        h1 { color: #333; }
        .step { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <h1>🏥 HospiLink QR Patient Management - Database Installation</h1>";

// Read SQL file
$sqlFile = __DIR__ . '/database/qr_patient_management.sql';

if (!file_exists($sqlFile)) {
    echo "<div class='error'>❌ Error: SQL file not found at: $sqlFile</div>";
    echo "</body></html>";
    exit;
}

echo "<div class='info'>📂 Reading SQL file: database/qr_patient_management.sql</div>";

$sql = file_get_contents($sqlFile);

if ($sql === false) {
    echo "<div class='error'>❌ Error: Could not read SQL file</div>";
    echo "</body></html>";
    exit;
}

// Remove USE database statement and comments for cleaner execution
$sql = preg_replace('/^USE\s+\w+;/m', '', $sql);
$sql = preg_replace('/^--.*$/m', '', $sql);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) { return !empty($stmt); }
);

echo "<div class='info'>📊 Found " . count($statements) . " SQL statements to execute</div>";

// Execute each statement
$successCount = 0;
$errorCount = 0;
$tableNames = [];

echo "<div class='step'><h2>Executing SQL Statements...</h2>";

foreach ($statements as $index => $statement) {
    if (empty(trim($statement))) continue;
    
    // Extract table name if it's a CREATE TABLE statement
    if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
        $tableNames[] = $matches[1];
        echo "<p><strong>Creating table: {$matches[1]}</strong></p>";
    }
    
    if ($conn->query($statement) === TRUE) {
        $successCount++;
        echo "<div class='success'>✅ Statement " . ($index + 1) . " executed successfully</div>";
    } else {
        $errorCount++;
        echo "<div class='error'>❌ Statement " . ($index + 1) . " failed: " . $conn->error . "</div>";
        echo "<pre>" . htmlspecialchars(substr($statement, 0, 200)) . "...</pre>";
    }
}

echo "</div>";

// Summary
echo "<div class='step'><h2>📋 Installation Summary</h2>";
echo "<p>✅ Successful statements: <strong>$successCount</strong></p>";
echo "<p>❌ Failed statements: <strong>$errorCount</strong></p>";

if ($errorCount === 0) {
    echo "<div class='success'>
        <h3>🎉 Installation Completed Successfully!</h3>
        <p>All tables have been created. The following tables are now available:</p>
        <ul>";
    
    foreach ($tableNames as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    
    echo "</ul>
        <h3>✅ Next Steps:</h3>
        <ol>
            <li>Delete this file (install_qr_tables.php) for security</li>
            <li>Go to <a href='index.html'>Homepage</a></li>
            <li>Click 'Sign In' and login as: <code>dr.patel@hospilink.com</code> / <code>doctor123</code></li>
            <li>Click 'Admit Patient' to test the QR system</li>
        </ol>
        <p><a href='admit.html' style='display:inline-block; padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px; margin-top:10px;'>Go to Admit Patient →</a></p>
    </div>";
} else {
    echo "<div class='error'>
        <h3>⚠️ Installation Completed with Errors</h3>
        <p>Some statements failed. Please check the errors above.</p>
        <p>You may need to:</p>
        <ul>
            <li>Check if the 'hospilink' database exists</li>
            <li>Verify database user permissions</li>
            <li>Check if tables already exist (drop them first if needed)</li>
        </ul>
    </div>";
}

echo "</div>";

// Verify tables were created
echo "<div class='step'><h2>🔍 Verifying Tables...</h2>";
$result = $conn->query("SHOW TABLES LIKE 'patient_%'");
if ($result) {
    echo "<p>Found " . $result->num_rows . " patient-related tables:</p><ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>✅ " . $row[0] . "</li>";
    }
    echo "</ul>";
}
echo "</div>";

$conn->close();

echo "</body></html>";
?>
