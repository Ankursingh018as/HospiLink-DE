<?php
require 'php/db.php';

echo "Adding missing columns to users table...\n\n";

// Check and add age column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'age'");
if ($result->num_rows === 0) {
    echo "Adding age column... ";
    if ($conn->query("ALTER TABLE users ADD COLUMN age INT NULL AFTER phone")) {
        echo "✓\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "age column already exists ✓\n";
}

// Check and add gender column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'gender'");
if ($result->num_rows === 0) {
    echo "Adding gender column... ";
    if ($conn->query("ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other') NULL AFTER age")) {
        echo "✓\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "gender column already exists ✓\n";
}

// Check and add blood_group column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'blood_group'");
if ($result->num_rows === 0) {
    echo "Adding blood_group column... ";
    if ($conn->query("ALTER TABLE users ADD COLUMN blood_group VARCHAR(10) NULL AFTER gender")) {
        echo "✓\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "blood_group column already exists ✓\n";
}

// Check and add address column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'address'");
if ($result->num_rows === 0) {
    echo "Adding address column... ";
    if ($conn->query("ALTER TABLE users ADD COLUMN address TEXT NULL AFTER blood_group")) {
        echo "✓\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "address column already exists ✓\n";
}

echo "\nMigration complete!\n";
echo "\nVerifying columns:\n";
$cols = $conn->query("SHOW COLUMNS FROM users WHERE Field IN ('age', 'gender', 'blood_group', 'address')");
while($row = $cols->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
