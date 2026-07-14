<?php
require_once 'php/db.php';

$users = [
    'nurse.sharma@hospilink.com',
    'nurse.patel@hospilink.com',
    'dr.poonawala@hospilink.com',
    'dr.patel@hospilink.com',
    'admin@hospilink.com',
    'patient@hospilink.com',
    'yadavaman1948@gmail.com',
    'aman.230180107069@gmail.com'
];

echo "=== Checking Provided Credentials ===\n\n";

foreach ($users as $email) {
    $res = $conn->query("SELECT user_id, first_name, last_name, email, role FROM users WHERE email = '$email'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo "[EXISTS] {$row['role']} : {$row['email']} ({$row['first_name']} {$row['last_name']})\n";
    } else {
        echo "[MISSING] {$email}\n";
    }
}
?>
