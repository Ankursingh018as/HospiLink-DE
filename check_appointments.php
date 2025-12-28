<?php
// Check appointments in database
include 'php/db.php';

echo "<h2>Checking Appointments Data</h2>";

// Check if appointments table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'appointments'");
if ($checkTable->num_rows > 0) {
    echo "<p style='color: green;'>✓ Appointments table exists</p>";
} else {
    echo "<p style='color: red;'>✗ Appointments table does NOT exist</p>";
    exit();
}

// Get all appointments
echo "<h3>All Appointments in Database:</h3>";
$allAppts = $conn->query("SELECT appointment_id, patient_id, doctor_id, appointment_date, status, priority_level, symptoms FROM appointments ORDER BY appointment_id DESC LIMIT 10");

if ($allAppts->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Doctor ID</th><th>Date</th><th>Status</th><th>Priority</th><th>Symptoms</th></tr>";
    while ($row = $allAppts->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['patient_id'] . "</td>";
        echo "<td>" . ($row['doctor_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['priority_level'] . "</td>";
        echo "<td>" . substr($row['symptoms'], 0, 50) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total appointments found: " . $allAppts->num_rows . "</strong></p>";
} else {
    echo "<p style='color: red;'>✗ No appointments found in database</p>";
}

// Check doctors
echo "<h3>Doctors in Database:</h3>";
$doctors = $conn->query("SELECT user_id, first_name, last_name, email, specialization FROM users WHERE role = 'doctor'");
if ($doctors->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Specialization</th></tr>";
    while ($row = $doctors->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . ($row['specialization'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ No doctors found</p>";
}

// Check current user session
session_start();
echo "<h3>Current Session:</h3>";
if (isset($_SESSION['logged_in'])) {
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Role: " . $_SESSION['user_role'] . "</p>";
    echo "<p>Name: " . $_SESSION['user_name'] . "</p>";
    
    // Check appointments for this doctor
    if ($_SESSION['user_role'] == 'doctor') {
        $doctorId = $_SESSION['user_id'];
        $doctorAppts = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctorId");
        $count = $doctorAppts->fetch_assoc()['count'];
        echo "<p>Appointments for this doctor (ID $doctorId): <strong>$count</strong></p>";
        
        // Check unassigned appointments
        $unassigned = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE doctor_id IS NULL");
        $unassignedCount = $unassigned->fetch_assoc()['count'];
        echo "<p>Unassigned appointments (doctor_id IS NULL): <strong>$unassignedCount</strong></p>";
    }
} else {
    echo "<p style='color: orange;'>Not logged in. <a href='sign_new.html'>Login here</a></p>";
}

$conn->close();
?>
