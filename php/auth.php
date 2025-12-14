<?php
// Authentication handler for HospiLink
include 'db.php';

// Handle Logout via GET
if (isset($_GET['logout'])) {
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], "User Logout", "Logged out");
    }
    
    session_unset();
    session_destroy();
    header("Location: ../sign_new.html");
    exit();
}

// Redirect if not a POST request (except for logout)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../sign_new.html");
    exit();
}

// Function to log activity
function logActivity($conn, $user_id, $action, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Handle Registration
if (isset($_POST['signUp'])) {
    $firstName = mysqli_real_escape_string($conn, trim($_POST['fName']));
    $lastName = mysqli_real_escape_string($conn, trim($_POST['lName']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.location.href='../sign_new.html';</script>";
        exit();
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        echo "<script>alert('Password must be at least 6 characters long!'); window.location.href='../sign_new.html';</script>";
        exit();
    }
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        echo "<script>alert('Email already registered! Please login or use a different email.'); window.location.href='../sign_new.html';</script>";
        exit();
    }
    $checkEmail->close();
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Handle doctor-specific fields
    if ($role === 'doctor') {
        $specialization = mysqli_real_escape_string($conn, trim($_POST['specialization']));
        $department = mysqli_real_escape_string($conn, trim($_POST['department']));
        $license_number = mysqli_real_escape_string($conn, trim($_POST['license_number']));
        
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, specialization, department, license_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $firstName, $lastName, $email, $hashedPassword, $role, $phone, $specialization, $department, $license_number);
    } elseif ($role === 'staff') {
        $staff_department = mysqli_real_escape_string($conn, trim($_POST['staff_department']));
        $staff_id = mysqli_real_escape_string($conn, trim($_POST['staff_id']));
        
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, department, staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $firstName, $lastName, $email, $hashedPassword, $role, $phone, $staff_department, $staff_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $role, $phone);
    }
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        logActivity($conn, $user_id, "User Registration", "New $role registered");
        echo "<script>alert('Registration successful! Please login.'); window.location.href='../sign_new.html';</script>";
    } else {
        echo "<script>alert('Registration failed! Please try again.'); window.location.href='../sign_new.html';</script>";
    }
    
    $stmt->close();
}

// Handle Login
if (isset($_POST['signIn'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Prepare and execute query
    // For staff role, also check for 'nurse' role (backward compatibility)
    if ($role === 'staff') {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role, status FROM users WHERE email = ? AND (role = 'staff' OR role = 'nurse')");
        $stmt->bind_param("s", $email);
    } else {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role, status FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            echo "<script>alert('Your account is inactive. Please contact administrator.'); window.location.href='../sign_new.html';</script>";
            exit();
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Log activity
            logActivity($conn, $user['user_id'], "User Login", "Logged in as $role");
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: ../dashboards/admin_dashboard.php");
                    break;
                case 'doctor':
                    header("Location: ../dashboards/doctor_dashboard.php");
                    break;
                case 'staff':
                case 'nurse':
                    header("Location: ../dashboards/staff_dashboard.php");
                    break;
                case 'patient':
                    header("Location: ../dashboards/patient_dashboard.php");
                    break;
                default:
                    header("Location: ../index.html");
            }
            exit();
        } else {
            echo "<script>alert('Invalid password!'); window.location.href='../sign_new.html';</script>";
        }
    } else {
        echo "<script>alert('No account found with this email and role!'); window.location.href='../sign_new.html';</script>";
    }
    
    $stmt->close();
}

$conn->close();
?>
