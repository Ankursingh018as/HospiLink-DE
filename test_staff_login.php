<?php
// Test Staff Login Credentials
include 'php/db.php';

echo "<h2>STAFF LOGIN TEST</h2>";
echo "<hr>";

// Check users with nurse or staff role
$result = $conn->query("SELECT user_id, email, first_name, last_name, role, status FROM users WHERE role IN ('nurse', 'staff', 'admin') ORDER BY role");

if ($result->num_rows > 0) {
    echo "<h3>Available Staff Users:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #4CAF50; color: white;'>";
    echo "<th>User ID</th><th>Email</th><th>Name</th><th>Database Role</th><th>Status</th><th>Login As</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $loginRole = ($row['role'] == 'nurse') ? 'staff (Hospital Staff)' : $row['role'];
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td><strong>" . $row['role'] . "</strong></td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td style='background: #ffeb3b;'><strong>" . $loginRole . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><hr><br>";
    echo "<h3 style='color: #2196F3;'>📌 HOW TO LOGIN:</h3>";
    echo "<ol style='font-size: 16px; line-height: 1.8;'>";
    echo "<li>Go to: <strong>localhost/HospiLink-DE/sign_new.html</strong></li>";
    echo "<li>Enter Email: <strong>nurse.sharma@hospilink.com</strong></li>";
    echo "<li>Enter Password: <strong>nurse123</strong></li>";
    echo "<li>Select Role: <strong>Hospital Staff</strong> (NOT 'nurse' - select 'Hospital Staff')</li>";
    echo "<li>Click Sign In</li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<h3 style='color: #f44336;'>⚠️ IMPORTANT:</h3>";
    echo "<p style='font-size: 16px;'>";
    echo "Even though the database has role='nurse', you MUST select <strong>'Hospital Staff'</strong> from the dropdown.<br>";
    echo "The auth.php checks for BOTH 'staff' OR 'nurse' when you select 'Hospital Staff'.<br>";
    echo "This is backward compatibility feature (see auth.php line 115-117).";
    echo "</p>";
    
    echo "<hr>";
    echo "<h3 style='color: #4CAF50;'>✅ TEST CREDENTIALS:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-left: 4px solid #4CAF50;'>";
    echo "<strong>STAFF USER 1:</strong>\n";
    echo "Email: nurse.sharma@hospilink.com\n";
    echo "Password: nurse123\n";
    echo "Login As: Hospital Staff\n\n";
    
    echo "<strong>STAFF USER 2:</strong>\n";
    echo "Email: nurse.patel@hospilink.com\n";
    echo "Password: nurse123\n";
    echo "Login As: Hospital Staff\n\n";
    
    echo "<strong>ADMIN USER:</strong>\n";
    echo "Email: admin@hospilink.com\n";
    echo "Password: admin123\n";
    echo "Login As: Admin";
    echo "</pre>";
    
} else {
    echo "<p style='color: red;'>No staff users found in database!</p>";
}

$conn->close();
?>
