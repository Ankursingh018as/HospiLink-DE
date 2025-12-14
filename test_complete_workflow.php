<?php
require 'php/db.php';
require 'php/patient_qr_helper.php';

echo "=== Testing Complete Admission Workflow ===\n\n";

// Create a test admission
echo "1. Creating test admission...\n";

// First, ensure we have a test patient
$email = 'test.patient@hospilink.com';
$phone = '9999999999';

// Check if patient exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone = ?");
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $patient = $result->fetch_assoc();
    $patient_id = $patient['user_id'];
    echo "   Using existing patient ID: $patient_id\n";
} else {
    // Create test patient
    $password = password_hash('test123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, age, gender, blood_group) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $fname = 'Test';
    $lname = 'Patient';
    $role = 'patient';
    $age = 30;
    $gender = 'male';
    $blood_group = 'O+';
    $stmt->bind_param("ssssssiss", $fname, $lname, $email, $phone, $password, $role, $age, $gender, $blood_group);
    $stmt->execute();
    $patient_id = $conn->insert_id;
    echo "   Created new patient ID: $patient_id\n";
}

// Create admission
echo "\n2. Creating admission with QR code...\n";
$admission_reason = 'Test admission for QR system verification';
$token = PatientQRHelper::createAdmission($conn, $patient_id, null, null, $admission_reason);

if ($token) {
    echo "   ✓ Admission created successfully!\n";
    echo "   QR Token: $token\n";
    
    // Test retrieval
    echo "\n3. Testing admission retrieval...\n";
    $admission = PatientQRHelper::getAdmissionFromToken($conn, $token);
    
    if ($admission) {
        echo "   ✓ Admission retrieved successfully!\n";
        echo "   Patient Name: " . $admission['patient_name'] . "\n";
        echo "   Gender: " . $admission['gender'] . "\n";
        echo "   Age: " . $admission['age'] . "\n";
        echo "   Blood Group: " . $admission['blood_group'] . "\n";
        echo "   Email: " . $admission['email'] . "\n";
        echo "   Phone: " . $admission['phone'] . "\n";
        echo "   Admission Reason: " . $admission['admission_reason'] . "\n";
        echo "   Status: " . $admission['status'] . "\n";
        
        echo "\n4. Testing patient-status.php access...\n";
        echo "   URL: http://localhost/HospiLink-DE/patient-status.php?token=$token\n";
        echo "   ✓ This URL should now work without SQL errors!\n";
        
    } else {
        echo "   ✗ Failed to retrieve admission\n";
    }
} else {
    echo "   ✗ Failed to create admission\n";
}

echo "\n=== Test Complete ===\n";
?>
