<?php
// Direct Staff Account Creator
// Access this file once at: http://localhost/HospiLink-DE/create_staff_now.php

include 'php/db.php';

echo "<h2>Creating Staff Account...</h2>";

// First, update the role enum
$alterQuery = "ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'staff', 'nurse') NOT NULL DEFAULT 'patient'";
if ($conn->query($alterQuery)) {
    echo "✓ Role enum updated<br>";
} else {
    echo "⚠ Role enum: " . $conn->error . "<br>";
}

// Add staff_id column if not exists
$addColumnQuery = "ALTER TABLE users ADD COLUMN IF NOT EXISTS staff_id VARCHAR(50)";
if ($conn->query($addColumnQuery)) {
    echo "✓ staff_id column checked<br>";
} else {
    echo "⚠ Column: " . $conn->error . "<br>";
}

// Create beds table if not exists
$bedsTable = "CREATE TABLE IF NOT EXISTS beds (
    bed_id INT PRIMARY KEY AUTO_INCREMENT,
    ward_name VARCHAR(100) NOT NULL,
    bed_number VARCHAR(20) NOT NULL,
    bed_type ENUM('General', 'ICU', 'Private', 'Semi-Private') DEFAULT 'General',
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bed (ward_name, bed_number)
)";
if ($conn->query($bedsTable)) {
    echo "✓ beds table ready<br>";
} else {
    echo "⚠ Beds table: " . $conn->error . "<br>";
}

// Create admitted_patients table if not exists
$admittedTable = "CREATE TABLE IF NOT EXISTS admitted_patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    blood_group VARCHAR(10),
    disease VARCHAR(255) NOT NULL,
    address TEXT,
    bed_id INT NULL,
    admission_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    discharge_date DATETIME NULL,
    status ENUM('stable', 'moderate', 'critical') DEFAULT 'stable',
    priority ENUM('stable', 'moderate', 'critical') DEFAULT 'stable',
    assigned_staff_id INT NULL,
    assignment_notes TEXT,
    discharge_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bed_id) REFERENCES beds(bed_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_staff_id) REFERENCES users(user_id) ON DELETE SET NULL
)";
if ($conn->query($admittedTable)) {
    echo "✓ admitted_patients table ready<br>";
} else {
    echo "⚠ Admitted patients table: " . $conn->error . "<br>";
}

// Insert sample beds if table is empty
$checkBeds = $conn->query("SELECT COUNT(*) as count FROM beds");
if ($checkBeds && $checkBeds->fetch_assoc()['count'] == 0) {
    $sampleBeds = "INSERT INTO beds (ward_name, bed_number, bed_type, status) VALUES
    ('General Ward', 'G-101', 'General', 'available'),
    ('General Ward', 'G-102', 'General', 'available'),
    ('ICU', 'ICU-01', 'ICU', 'available'),
    ('ICU', 'ICU-02', 'ICU', 'available'),
    ('Private Ward', 'P-201', 'Private', 'available')";
    if ($conn->query($sampleBeds)) {
        echo "✓ Sample beds added<br>";
    }
}

// Delete existing test accounts
$conn->query("DELETE FROM users WHERE email IN ('staff@hospital.com', 'teststaff@hospilink.com')");
echo "✓ Cleaned old test accounts<br>";

// Create new staff account
$firstName = 'Hospital';
$lastName = 'Staff';
$email = 'staff@hospital.com';
$password = password_hash('12345', PASSWORD_BCRYPT);
$role = 'staff';
$phone = '9876543210';
$department = 'General Ward';
$staff_id = 'STF-001';
$status = 'active';

$insertQuery = "INSERT INTO users (first_name, last_name, email, password, role, phone, department, staff_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("sssssssss", $firstName, $lastName, $email, $password, $role, $phone, $department, $staff_id, $status);

if ($stmt->execute()) {
    echo "<h3 style='color: green;'>✓ Staff Account Created Successfully!</h3>";
    
    // Verify the account
    $verifyQuery = "SELECT user_id, first_name, last_name, email, role, department, staff_id, status FROM users WHERE email = '$email'";
    $result = $conn->query($verifyQuery);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Auto-login the user
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";
        echo "<h3>Account Created & Auto-Login Successful!</h3>";
        echo "<p><strong>Email:</strong> staff@hospital.com</p>";
        echo "<p><strong>Password:</strong> 12345</p>";
        echo "<p><strong>Role:</strong> Hospital Staff</p>";
        echo "<p style='color: green;'>Redirecting to Staff Dashboard in 2 seconds...</p>";
        echo "</div>";
        
        // Redirect to staff dashboard
        header("refresh:2;url=dashboards/staff_dashboard.php");
    }
} else {
    echo "<h3 style='color: red;'>✗ Error: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();

echo "<br><br><p style='color: red;'><strong>⚠ IMPORTANT: Delete this file after creating the account for security!</strong></p>";
?>
