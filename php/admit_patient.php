<!-- admit_patient.php -->
<?php
// Include database connection file
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $admit_date = mysqli_real_escape_string($conn, $_POST['admit_date']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $disease_description = mysqli_real_escape_string($conn, $_POST['disease']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phno']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Split patient name into first and last name
    $name_parts = explode(' ', $patient_name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Generate a temporary email for the patient
    $temp_email = 'patient_' . time() . '@hospilink.temp';
    $temp_password = password_hash('temp123', PASSWORD_BCRYPT);
    
    // Insert into users table as a patient
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, status) VALUES (?, ?, ?, ?, 'patient', ?, 'active')");
    $stmt->bind_param("sssss", $first_name, $last_name, $temp_email, $temp_password, $phone_number);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Create medical history entry for admission
        $history_stmt = $conn->prepare("INSERT INTO medical_history (patient_id, diagnosis, treatment, visit_date, notes) VALUES (?, ?, ?, ?, ?)");
        $diagnosis = "Patient admitted with: " . $disease_description;
        $treatment = "Under observation";
        $visit_date = date('Y-m-d');
        $notes = "DOB: $dob, Blood Group: $blood_group, Address: $address";
        
        $history_stmt->bind_param("issss", $user_id, $diagnosis, $treatment, $visit_date, $notes);
        $history_stmt->execute();
        $history_stmt->close();
        
        echo "<script>alert('Patient admitted successfully! Patient ID: $user_id'); window.location.href='../admit.html';</script>";
    } else {
        echo "<script>alert('Error admitting patient: " . $conn->error . "'); window.location.href='../admit.html';</script>";
    }
    
    $stmt->close();
    $conn->close();
} else {
    // Handle GET requests - redirect to admit form
    header("Location: ../admit.html");
    exit();
}
?>
