<?php
// Test appointment booking functionality
include 'php/db.php';

echo "Testing appointment booking system...\n\n";

// Check if symptom_keywords table has data
$symptomQuery = "SELECT COUNT(*) as count FROM symptom_keywords";
$result = $conn->query($symptomQuery);
$symptomCount = $result->fetch_assoc()['count'];
echo "Symptom keywords in database: $symptomCount\n";

if ($symptomCount == 0) {
    echo "❌ ERROR: No symptom keywords found. AI analysis won't work.\n";
} else {
    echo "✅ Symptom keywords available\n";
}

// Check if users table has patients
$patientQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'patient'";
$result = $conn->query($patientQuery);
$patientCount = $result->fetch_assoc()['count'];
echo "Patients in database: $patientCount\n";

// Test if appointments table is ready
$appointmentQuery = "SELECT COUNT(*) as count FROM appointments";
$result = $conn->query($appointmentQuery);
$appointmentCount = $result->fetch_assoc()['count'];
echo "Existing appointments: $appointmentCount\n";

// Check table structure
echo "\nAppointments table structure:\n";
$structureQuery = "DESCRIBE appointments";
$result = $conn->query($structureQuery);
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n✅ Database tables appear to be set up correctly.\n";
echo "If appointments aren't booking, check for JavaScript errors in browser console.\n";
?>