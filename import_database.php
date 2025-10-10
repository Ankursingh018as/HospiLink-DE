<?php
/**
 * HospiLink Database Import Script
 * This PHP script imports the database directly, bypassing command-line issues
 */

// Database configuration
$servername = "localhost";
$username = "root";
$password = "8511";
$dbname = "hospilink";
$sqlFile = __DIR__ . '/database/hospilink_schema.sql';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Import - HospiLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            text-align: center;
        }

        .log {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
        }

        .success {
            color: #4CAF50;
        }

        .error {
            color: #f44336;
        }

        .info {
            color: #2196F3;
        }

        .warning {
            color: #FF9800;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Database Import</h1>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">PHP-based database importer</p>

        <div class="log">
<?php

// Function to execute SQL file
function importDatabase($conn, $sqlFile) {
    if (!file_exists($sqlFile)) {
        echo '<span class="error">✗ ERROR: SQL file not found at: ' . $sqlFile . '</span><br>';
        return false;
    }

    echo '<span class="info">→ Reading SQL file...</span><br>';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        echo '<span class="error">✗ ERROR: Could not read SQL file</span><br>';
        return false;
    }

    echo '<span class="success">✓ SQL file loaded successfully</span><br>';
    echo '<span class="info">→ File size: ' . number_format(strlen($sql)) . ' bytes</span><br><br>';

    // Split SQL into individual statements
    echo '<span class="info">→ Executing SQL statements...</span><br>';
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql);
    
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($statement) {
            return !empty($statement);
        }
    );

    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $statement) {
        if (strlen($statement) < 10) continue;
        
        $result = $conn->query($statement);
        
        if ($result === false) {
            $error_count++;
            // Only show first few errors to avoid cluttering
            if ($error_count <= 3) {
                echo '<span class="error">✗ Error: ' . htmlspecialchars($conn->error) . '</span><br>';
            }
        } else {
            $success_count++;
        }
    }

    echo '<br>';
    echo '<span class="success">✓ Executed ' . $success_count . ' statements successfully</span><br>';
    
    if ($error_count > 0) {
        echo '<span class="warning">⚠ ' . $error_count . ' statements had errors (may be expected)</span><br>';
    }

    return true;
}

// Start import process
echo '<span class="info">═══════════════════════════════════════════</span><br>';
echo '<span class="info">   HospiLink Database Import Process</span><br>';
echo '<span class="info">═══════════════════════════════════════════</span><br><br>';

echo '<span class="info">[1/5] Connecting to MySQL...</span><br>';

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    echo '<span class="error">✗ Connection failed: ' . htmlspecialchars($conn->connect_error) . '</span><br>';
    echo '<br><span class="error">Please check your MySQL credentials in php/db.php</span><br>';
} else {
    echo '<span class="success">✓ Connected to MySQL successfully</span><br>';
    echo '<span class="info">   Server: ' . $servername . '</span><br>';
    echo '<span class="info">   User: ' . $username . '</span><br><br>';

    echo '<span class="info">[2/5] Creating database if not exists...</span><br>';
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if ($conn->query($sql) === TRUE) {
        echo '<span class="success">✓ Database "' . $dbname . '" ready</span><br><br>';
    } else {
        echo '<span class="error">✗ Error creating database: ' . htmlspecialchars($conn->error) . '</span><br>';
    }

    // Select database
    echo '<span class="info">[3/5] Selecting database...</span><br>';
    $conn->select_db($dbname);
    echo '<span class="success">✓ Database selected</span><br><br>';

    echo '<span class="info">[4/5] Importing SQL file...</span><br>';
    echo '<span class="info">   File: ' . basename($sqlFile) . '</span><br>';
    
    $import_result = importDatabase($conn, $sqlFile);

    if ($import_result) {
        echo '<br><span class="info">[5/5] Verifying import...</span><br>';
        
        // Check if tables exist
        $tables = ['users', 'appointments', 'symptom_keywords', 'medical_history', 'beds', 'activity_logs'];
        $all_tables_exist = true;
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo '<span class="success">✓ Table "' . $table . '" exists</span><br>';
            } else {
                echo '<span class="error">✗ Table "' . $table . '" not found</span><br>';
                $all_tables_exist = false;
            }
        }

        // Check sample data
        echo '<br><span class="info">→ Checking sample data...</span><br>';
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo '<span class="success">✓ Users table has ' . $row['count'] . ' records</span><br>';
        }

        $result = $conn->query("SELECT COUNT(*) as count FROM symptom_keywords");
        if ($result) {
            $row = $result->fetch_assoc();
            echo '<span class="success">✓ Symptom keywords: ' . $row['count'] . ' loaded</span><br>';
        }

        echo '<br>';
        echo '<span class="info">═══════════════════════════════════════════</span><br>';
        if ($all_tables_exist) {
            echo '<span class="success">   ✓ DATABASE IMPORT SUCCESSFUL!</span><br>';
        } else {
            echo '<span class="warning">   ⚠ IMPORT COMPLETED WITH WARNINGS</span><br>';
        }
        echo '<span class="info">═══════════════════════════════════════════</span><br>';
    } else {
        echo '<br><span class="error">✗ Import failed. Please try manual import via phpMyAdmin.</span><br>';
    }

    $conn->close();
}

?>
        </div>

        <a href="sign_new.html" class="btn">
            <i class="fas fa-sign-in-alt"></i> Go to Login Page
        </a>

        <a href="dev_panel.html" class="btn" style="background: #6c757d; margin-top: 10px;">
            <i class="fas fa-arrow-left"></i> Back to Developer Panel
        </a>
    </div>
</body>
</html>
