<?php
include 'db.php';
include 'symptom_analyzer.php';
include 'email_service_smtp.php'; // Include Gmail SMTP email service

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get patient ID from session if logged in
    $patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $doctor_preference = isset($_POST['s_doctor']) ? mysqli_real_escape_string($conn, $_POST['s_doctor']) : null;
    
    // Initialize AI Symptom Analyzer
    $analyzer = new SymptomAnalyzer($conn);
    
    // Analyze symptoms and get priority
    $analysis = $analyzer->analyzeSymptoms($symptoms);
    $priorityLevel = $analysis['priority_level'];
    $priorityScore = $analysis['priority_score'];
    $analysisMessage = $analysis['analysis'];
    
    // Get doctor ID if preference specified
    $doctor_id = null;
    if ($doctor_preference) {
        $doctorQuery = $conn->prepare("SELECT user_id FROM users WHERE CONCAT(first_name, ' ', last_name) = ? AND role = 'doctor'");
        $doctorQuery->bind_param("s", $doctor_preference);
        $doctorQuery->execute();
        $doctorResult = $doctorQuery->get_result();
        if ($doctorResult->num_rows > 0) {
            $doctorRow = $doctorResult->fetch_assoc();
            $doctor_id = $doctorRow['user_id'];
        }
        $doctorQuery->close();
    }
    
    // If no patient_id (guest booking), create a temporary patient account
    if (!$patient_id) {
        // Check if email exists as patient
        $checkPatient = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND role = 'patient'");
        $checkPatient->bind_param("s", $email);
        $checkPatient->execute();
        $result = $checkPatient->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $patient_id = $row['user_id'];
        } else {
            // Create new patient account
            $tempPassword = password_hash('temp' . rand(1000, 9999), PASSWORD_BCRYPT);
            $nameParts = explode(' ', $name, 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            
            $createPatient = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone) VALUES (?, ?, ?, ?, 'patient', ?)");
            $createPatient->bind_param("sssss", $firstName, $lastName, $email, $tempPassword, $phone);
            $createPatient->execute();
            $patient_id = $createPatient->insert_id;
            $createPatient->close();
        }
        $checkPatient->close();
    }

    // Insert appointment with AI priority
    $sql = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, full_name, email, gender, phone, appointment_date, appointment_time, symptoms, priority_level, priority_score, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $sql->bind_param("iissssssssi", $patient_id, $doctor_id, $name, $email, $gender, $phone, $date, $time, $symptoms, $priorityLevel, $priorityScore);

    if ($sql->execute()) {
        $appointment_id = $sql->insert_id;
        
        // Get doctor name if assigned
        $doctor_name = 'To be assigned';
        if ($doctor_id) {
            $doctorQuery = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name, specialization FROM users WHERE user_id = ?");
            $doctorQuery->bind_param("i", $doctor_id);
            $doctorQuery->execute();
            $doctorResult = $doctorQuery->get_result();
            if ($doctorResult->num_rows > 0) {
                $doctorRow = $doctorResult->fetch_assoc();
                $doctor_name = $doctorRow['name'] . ' - ' . $doctorRow['specialization'];
            }
            $doctorQuery->close();
        }
        
        // Prepare email data
        $emailData = [
            'appointment_id' => $appointment_id,
            'full_name' => $name,
            'email' => $email,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'symptoms' => $symptoms,
            'priority_level' => $priorityLevel,
            'priority_score' => $priorityScore,
            'doctor_name' => $doctor_name
        ];
        
        // Send email confirmation
        $emailSent = EmailService::sendAppointmentConfirmation($emailData);
        
        // Get suggestions
        $suggestions = $analyzer->getAppointmentSuggestions($priorityLevel);
        
        // Prepare response message
        $message = "✅ Appointment booked successfully!\n\n";
        $message .= "📋 Appointment ID: #" . $appointment_id . "\n";
        $message .= "👤 Patient: " . $name . "\n";
        $message .= "📅 Date: " . date('d M Y', strtotime($date)) . "\n";
        $message .= "🕐 Time: " . date('h:i A', strtotime($time)) . "\n\n";
        $message .= "🔍 AI Analysis:\n" . $analysisMessage . "\n\n";
        $message .= "⏱️ Expected Wait Time: " . $suggestions['wait_time'] . "\n\n";
        
        if ($emailSent) {
            $message .= "📧 Confirmation email sent to: " . $email . "\n\n";
        } else {
            $message .= "⚠️ Note: Email confirmation could not be sent.\n\n";
        }
        
        if ($priorityLevel === 'critical') {
            $message .= "🚨 URGENT: Please proceed to the emergency department immediately or call emergency services if symptoms worsen!";
        } elseif ($priorityLevel === 'high') {
            $message .= "⚡ Your appointment has been marked as high priority. A doctor will contact you soon.";
        }
        
        // Store appointment ID in session and redirect to success page
        $_SESSION['appointment_id'] = $appointment_id;
        header("Location: ../appointment_success.php");
        exit();
        
    } else {
        echo "<script>
            alert('Error booking appointment: " . $conn->error . "');
            window.location.href='../appointment.html';
        </script>";
    }

    $sql->close();
    $conn->close();
} else {
    // Handle GET requests - redirect to appointment form
    header("Location: ../appointment.html");
    exit();
}
?>
