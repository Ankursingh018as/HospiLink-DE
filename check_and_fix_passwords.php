<?php
// Check and fix staff passwords
include 'php/db.php';

echo "<h2>Checking Staff Passwords</h2><hr>";

// Check current password hashes
$result = $conn->query("SELECT user_id, email, password, role FROM users WHERE role IN ('nurse', 'staff', 'admin')");

echo "<h3>Current Password Hashes:</h3>";
while ($row = $result->fetch_assoc()) {
    echo "Email: " . $row['email'] . "<br>";
    echo "Role: " . $row['role'] . "<br>";
    echo "Password Hash: " . substr($row['password'], 0, 30) . "...<br>";
    echo "Hash Length: " . strlen($row['password']) . "<br><br>";
}

// Now let's reset the passwords to known values
$updates = [
    ['email' => 'nurse.sharma@hospilink.com', 'password' => 'nurse123'],
    ['email' => 'nurse.patel@hospilink.com', 'password' => 'nurse123'],
    ['email' => 'admin@hospilink.com', 'password' => 'admin123']
];

echo "<hr><h3>Resetting Passwords...</h3>";

foreach ($updates as $user) {
    $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $user['email']);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "✅ Updated password for: " . $user['email'] . " → " . $user['password'] . "<br>";
        } else {
            echo "⚠️ No user found for: " . $user['email'] . "<br>";
        }
    } else {
        echo "❌ Failed to update: " . $user['email'] . "<br>";
    }
    $stmt->close();
}

echo "<hr><h3>Verifying Updated Passwords:</h3>";

// Verify the passwords work
$result = $conn->query("SELECT email, password, role FROM users WHERE role IN ('nurse', 'staff', 'admin')");

while ($row = $result->fetch_assoc()) {
    $testPassword = ($row['role'] == 'admin') ? 'admin123' : 'nurse123';
    $verified = password_verify($testPassword, $row['password']);
    
    echo "Email: " . $row['email'] . "<br>";
    echo "Test Password: " . $testPassword . "<br>";
    echo "Verification: " . ($verified ? "✅ SUCCESS" : "❌ FAILED") . "<br><br>";
}

echo "<hr><h3>✅ Final Credentials:</h3>";
echo "<pre style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50;'>";
echo "Staff User 1:\n";
echo "Email: nurse.sharma@hospilink.com\n";
echo "Password: nurse123\n";
echo "Login As: Hospital Staff\n\n";

echo "Staff User 2:\n";
echo "Email: nurse.patel@hospilink.com\n";
echo "Password: nurse123\n";
echo "Login As: Hospital Staff\n\n";

echo "Admin User:\n";
echo "Email: admin@hospilink.com\n";
echo "Password: admin123\n";
echo "Login As: Admin\n";
echo "</pre>";

$conn->close();
?>
