<?php
require 'php/db.php';
require 'php/patient_qr_helper.php';

echo "Testing QR Helper after gender column fix...\n\n";

// Test token validation
$testToken = 'HOSP-1765703305-F22A979CC009F0C8F91B5D8AD74E2F47';
echo "1. Testing token validation: ";
$isValid = PatientQRHelper::validateToken($testToken);
echo $isValid ? "✓ PASS\n" : "✗ FAIL\n";

// Test admission retrieval
echo "2. Testing admission retrieval: ";
try {
    $result = PatientQRHelper::getAdmissionFromToken($conn, $testToken);
    echo "✓ PASS (No SQL errors)\n";
    if ($result) {
        echo "   - Found admission for: " . $result['patient_name'] . "\n";
        echo "   - Gender: " . $result['gender'] . "\n";
        echo "   - Age: " . $result['age'] . "\n";
        echo "   - Blood Group: " . $result['blood_group'] . "\n";
    } else {
        echo "   - No active admission found (token may be expired/invalid)\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\nAll tests completed!\n";
?>
