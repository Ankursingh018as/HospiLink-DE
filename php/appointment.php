<?php
include 'db.php';
include 'ai_prioritizer.php'; // AI-powered medical prioritizer using Google Gemini
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
    
    // Get patient age and existing conditions if available
    $patient_age = isset($_POST['age']) ? intval($_POST['age']) : null;
    $existing_conditions = isset($_POST['medical_history']) ? mysqli_real_escape_string($conn, $_POST['medical_history']) : null;
    
    // Initialize AI Prioritizer (uses Google Gemini API)
    $aiPrioritizer = new AIPrioritizer();
    
    // Analyze symptoms using AI and get comprehensive assessment
    $analysis = $aiPrioritizer->getDetailedAssessment($symptoms, $patient_age, $existing_conditions);
    $priorityLevel = $analysis['priority_level'];
    $priorityScore = $analysis['priority_score'];
    $analysisMessage = $analysis['urgency_reason'];
    $suspectedConditions = implode(', ', $analysis['suspected_conditions']);
    $recommendedSpecialist = $analysis['recommended_specialist'];
    $timeSensitivity = $analysis['time_sensitivity'];
    
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
            
            $createPatient = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, status) VALUES (?, ?, ?, ?, 'patient', ?, 'active')");
            $createPatient->bind_param("sssss", $firstName, $lastName, $email, $tempPassword, $phone);
            
            if ($createPatient->execute()) {
                $patient_id = $conn->insert_id;
            } else {
                $error_msg = addslashes($conn->error);
                echo "<script>
                    alert('Error creating patient account: " . $error_msg . "');
                    window.location.href='../appointment.html';
                </script>";
                exit();
            }
            $createPatient->close();
        }
        $checkPatient->close();
    }
    
    // Verify patient_id exists before continuing
    if (!$patient_id || $patient_id <= 0) {
        echo "<script>
            alert('Error: Unable to create or retrieve patient account. Please try again.');
            window.location.href='../appointment.html';
        </script>";
        exit();
    }

    // Ensure AI analysis column exists (add if missing)
    $colCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'ai_analysis'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN ai_analysis TEXT NULL");
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
        
        // Store AI analysis in appointments.ai_analysis as JSON
        $ai_json = json_encode($analysis);
        $safe_ai = $conn->real_escape_string($ai_json);
        $conn->query("UPDATE appointments SET ai_analysis = '{$safe_ai}' WHERE appointment_id = {$appointment_id}");

        // Prepare email data with AI analysis
        $emailData = [
            'appointment_id' => $appointment_id,
            'full_name' => $name,
            'email' => $email,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'symptoms' => $symptoms,
            'priority_level' => $priorityLevel,
            'priority_score' => $priorityScore,
            'doctor_name' => $doctor_name,
            'ai_analysis' => $analysis,
            'suspected_conditions' => $suspectedConditions,
            'recommended_specialist' => $recommendedSpecialist
        ];
        
        // Send email confirmation
        $emailSent = EmailService::sendAppointmentConfirmation($emailData);
        
        // Prepare response message with AI insights
        $message = "✅ Appointment booked successfully!\n\n";
        $message .= "📋 Appointment ID: #" . $appointment_id . "\n";
        $message .= "👤 Patient: " . $name . "\n";
        $message .= "📅 Date: " . date('d M Y', strtotime($date)) . "\n";
        $message .= "🕐 Time: " . date('h:i A', strtotime($time)) . "\n\n";
        $message .= "🤖 AI Medical Analysis:\n";
        $message .= "Priority: " . strtoupper($priorityLevel) . " (Score: $priorityScore/100)\n";
        $message .= "Urgency: " . $analysisMessage . "\n";
        if ($suspectedConditions) {
            $message .= "Suspected conditions: " . $suspectedConditions . "\n";
        }
        $message .= "Recommended specialist: " . $recommendedSpecialist . "\n";
        $message .= "Time sensitivity: " . strtoupper($timeSensitivity) . "\n\n";
        
        if ($emailSent) {
            $message .= "📧 Confirmation email sent to: " . $email . "\n\n";
        } else {
            $message .= "⚠️ Note: Email confirmation could not be sent.\n\n";
        }
        
        if ($timeSensitivity === 'immediate' || $priorityLevel === 'critical') {
            $message .= "🚨 URGENT: Please proceed to the emergency department immediately or call emergency services if symptoms worsen!";
        } elseif ($timeSensitivity === 'urgent' || $priorityLevel === 'high') {
            $message .= "⚡ Your appointment has been marked as high priority. A doctor will contact you within 2-4 hours.";
        } else {
            $message .= "✓ Your appointment is confirmed. Please arrive 15 minutes early.";
        }
        
        // Store appointment ID in session and redirect to success page
        $_SESSION['appointment_id'] = $appointment_id;
        header("Location: ../appointment_success.php");
        exit();
        
    } else {
        $error_msg = addslashes($conn->error);
        echo "<script>
            alert('Error booking appointment: " . $error_msg . "');
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
