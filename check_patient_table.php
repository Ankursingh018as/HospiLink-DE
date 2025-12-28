<?php
include 'php/db.php';

echo "<h2>Checking Patient Admissions Table</h2>";

// Check table structure
echo "<h3>Table Structure:</h3>";
$structure = $conn->query("DESCRIBE patient_admissions");
if ($structure) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
}

// Check if old table exists
$checkOld = $conn->query("SHOW TABLES LIKE 'admitted_patients'");
if ($checkOld->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ Old table 'admitted_patients' still exists!</p>";
    $oldData = $conn->query("SELECT * FROM admitted_patients LIMIT 5");
    echo "<h3>Data in OLD table (admitted_patients):</h3>";
    if ($oldData->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        $first = true;
        while ($row = $oldData->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) echo "<th>$key</th>";
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $val) echo "<td>$val</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: green;'>✓ Old table does not exist</p>";
}

// Check new table data
echo "<h3>Data in NEW table (patient_admissions):</h3>";
$newData = $conn->query("SELECT admission_id, patient_id, patient_name, disease, status, bed_id, admission_date, phone FROM patient_admissions LIMIT 5");
if ($newData->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Name</th><th>Disease</th><th>Status</th><th>Bed ID</th><th>Date</th><th>Phone</th></tr>";
    while ($row = $newData->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['admission_id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>{$row['patient_name']}</td>";
        echo "<td>{$row['disease']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>" . ($row['bed_id'] ?? 'NULL') . "</td>";
        echo "<td>{$row['admission_date']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total records: " . $newData->num_rows . "</strong></p>";
} else {
    echo "<p style='color: red;'>✗ No data found in patient_admissions</p>";
}
?>
