<?php
require 'php/db.php';
echo "Users table columns:\n";
echo str_repeat("=", 50) . "\n";
$cols = $conn->query('SHOW COLUMNS FROM users');
while($row = $cols->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
