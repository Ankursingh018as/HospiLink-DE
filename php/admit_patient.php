<!-- admit_patient.php -->
<?php
// Include database connection file
include 'db.php';
include 'patient_qr_helper.php';

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
    
    // Get bed and doctor if specified
    $bed_id = isset($_POST['bed_id']) ? intval($_POST['bed_id']) : null;
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
    
    // Split patient name into first and last name
    $name_parts = explode(' ', $patient_name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Check if patient already exists by phone
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ? AND role = 'patient'");
    $check_stmt->bind_param("s", $phone_number);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Patient exists
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $check_stmt->close();
    } else {
        // Create new patient
        $temp_email = 'patient_' . time() . '@hospilink.temp';
        $temp_password = password_hash('temp123', PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, status) VALUES (?, ?, ?, ?, 'patient', ?, 'active')");
        $stmt->bind_param("sssss", $first_name, $last_name, $temp_email, $temp_password, $phone_number);
        
        if (!$stmt->execute()) {
            echo "<script>alert('Error creating patient record: " . $conn->error . "'); window.location.href='../admit.html';</script>";
            exit();
        }
        
        $user_id = $stmt->insert_id;
        $stmt->close();
    }
    
    // Create admission with QR code
    $admission_result = PatientQRHelper::createAdmission($conn, $user_id, $bed_id, $disease_description, $doctor_id);
    
    if ($admission_result['success']) {
        $admission_id = $admission_result['admission_id'];
        $qr_token = $admission_result['qr_token'];
        
        // Create initial medical history entry
        $history_stmt = $conn->prepare("INSERT INTO medical_history (patient_id, diagnosis, treatment, visit_date, notes) VALUES (?, ?, ?, ?, ?)");
        $diagnosis = "Patient admitted with: " . $disease_description;
        $treatment = "Under observation";
        $visit_date = date('Y-m-d');
        $notes = "DOB: $dob, Blood Group: $blood_group, Address: $address, Admission ID: $admission_id";
        
        $history_stmt->bind_param("issss", $user_id, $diagnosis, $treatment, $visit_date, $notes);
        $history_stmt->execute();
        $history_stmt->close();
        
        // Redirect to QR code print page
        echo "<script>
            alert('Patient admitted successfully!\\nAdmission ID: $admission_id\\nQR Code generated for bedside monitoring.');
            window.location.href='../qr-print.php?admission_id=$admission_id';
        </script>";
    } else {
        echo "<script>alert('Error creating admission: " . $admission_result['error'] . "'); window.location.href='../admit.html';</script>";
    }
    
    $conn->close();
} else {
    // Handle GET requests - redirect to admit form
    header("Location: ../admit.html");
    exit();
}
?>
