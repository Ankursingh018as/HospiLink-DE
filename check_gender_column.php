<?php
require 'php/db.php';
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'gender'");
echo $result->num_rows > 0 ? "Gender column exists\n" : "Gender column missing\n";

if ($result->num_rows === 0) {
    echo "Applying migration...\n";
    $sql = file_get_contents('database/add_profile_fields.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $conn->query($statement);
        }
    }
    echo "Migration complete!\n";
}
?>
