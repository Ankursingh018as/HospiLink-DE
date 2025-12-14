<?php
include 'php/db.php';

// Check admitted patients
$result = $conn->query("SELECT COUNT(*) as count FROM admitted_patients WHERE discharge_date IS NULL");
$patients = $result->fetch_assoc();
echo "Admitted patients: " . $patients['count'] . "\n";

// Check available beds
$result = $conn->query("SELECT COUNT(*) as count FROM beds WHERE status='available'");
$beds = $result->fetch_assoc();
echo "Available beds: " . $beds['count'] . "\n";

// List patients
$result = $conn->query("SELECT * FROM admitted_patients WHERE discharge_date IS NULL LIMIT 3");
echo "\nSample patients:\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['patient_name'] . " (" . $row['disease'] . ")\n";
}
?>
