<?php
include 'php/db.php';

echo "<h2>Checking Real Patient Data</h2>";

// Check users with patient role
echo "<h3>Patients in Users Table:</h3>";
$patients = $conn->query("SELECT user_id, first_name, last_name, email, phone FROM users WHERE role = 'patient'");
if ($patients->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>";
    while ($row = $patients->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total patients: " . $patients->num_rows . "</strong></p>";
} else {
    echo "<p style='color: red;'>No patients in users table</p>";
}

// Check appointments
echo "<h3>Appointments Data:</h3>";
$appts = $conn->query("SELECT a.appointment_id, a.patient_id, u.first_name, u.last_name, a.symptoms, a.appointment_date, a.status FROM appointments a JOIN users u ON a.patient_id = u.user_id LIMIT 5");
if ($appts->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient</th><th>Symptoms</th><th>Date</th><th>Status</th></tr>";
    while ($row = $appts->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['appointment_id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>" . substr($row['symptoms'], 0, 50) . "...</td>";
        echo "<td>{$row['appointment_date']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No appointments found</p>";
}

// Check patient_admissions with JOIN
echo "<h3>Patient Admissions (NEW table with JOIN):</h3>";
$admissions = $conn->query("SELECT pa.admission_id, pa.patient_id, u.first_name, u.last_name, u.phone, pa.admission_reason, pa.status, pa.admission_date, b.ward_name, b.bed_number FROM patient_admissions pa JOIN users u ON pa.patient_id = u.user_id LEFT JOIN beds b ON pa.bed_id = b.bed_id WHERE pa.status = 'active' LIMIT 5");
if ($admissions && $admissions->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient</th><th>Phone</th><th>Reason</th><th>Bed</th><th>Status</th><th>Date</th></tr>";
    while ($row = $admissions->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['admission_id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "<td>" . substr($row['admission_reason'] ?? 'N/A', 0, 30) . "</td>";
        echo "<td>" . ($row['ward_name'] ? $row['ward_name'] . ' - ' . $row['bed_number'] : 'No bed') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['admission_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total active admissions: " . $admissions->num_rows . "</strong></p>";
} else {
    echo "<p style='color: orange;'>No active admissions in patient_admissions table</p>";
}

echo "<h3>Recommendation:</h3>";
echo "<p>Use patient_admissions table (NEW) joined with users table for real data.</p>";
echo "<p>Delete or ignore admitted_patients table (OLD) with static sample data.</p>";
?>
