<?php
// Simple QR Code Test
$test_token = "HOSP-TEST-123456-ABCDEF";
$test_url = "http://localhost/HospiLink-DE/patient-status.php?token=" . urlencode($test_token);
$qr_api_url = "https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=" . urlencode($test_url);
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Test</title>
</head>
<body>
    <h1>QR Code Test</h1>
    
    <h2>Test Data:</h2>
    <p><strong>Token:</strong> <?php echo $test_token; ?></p>
    <p><strong>Target URL:</strong> <?php echo $test_url; ?></p>
    
    <h2>QR Code API URL:</h2>
    <textarea style="width:100%; height:100px;"><?php echo $qr_api_url; ?></textarea>
    
    <h2>Generated QR Code:</h2>
    <div style="border: 2px solid black; padding: 20px; display: inline-block;">
        <img src="<?php echo $qr_api_url; ?>" alt="Test QR Code" style="display: block;">
    </div>
    
    <h2>If you see a QR code above, the system works!</h2>
    <p>If not, check:</p>
    <ul>
        <li>Internet connection (Google Charts API needs internet)</li>
        <li>Browser console for errors (F12)</li>
        <li>Firewall blocking Google APIs</li>
    </ul>
</body>
</html>
