<?php
/**
 * Email Debug Script - Diagnose email sending issues
 */

require_once 'email_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Debug - HospiLink</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>📧 Email Configuration Debug</h1>";

// 1. Check email configuration
echo "<div class='section'>
    <h2>1. Email Configuration Check</h2>";

if (defined('EMAIL_ENABLED') && EMAIL_ENABLED) {
    echo "<p class='success'>✓ Email sending is ENABLED</p>";
} else {
    echo "<p class='error'>✗ Email sending is DISABLED - Set EMAIL_ENABLED to true in email_config.php</p>";
}

echo "<p><strong>SMTP Settings:</strong></p>
    <ul>
        <li>Host: <code>" . SMTP_HOST . "</code></li>
        <li>Port: <code>" . SMTP_PORT . "</code></li>
        <li>Security: <code>" . SMTP_SECURITY . "</code></li>
        <li>Username: <code>" . SMTP_USERNAME . "</code></li>
        <li>From Email: <code>" . SMTP_FROM_EMAIL . "</code></li>
        <li>From Name: <code>" . SMTP_FROM_NAME . "</code></li>
        <li>Password Length: <code>" . strlen(SMTP_PASSWORD) . " characters</code></li>
    </ul>
</div>";

// 2. Check PHP extensions
echo "<div class='section'>
    <h2>2. PHP Extension Check</h2>";

$required = ['openssl', 'sockets'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ {$ext} extension is loaded</p>";
    } else {
        echo "<p class='error'>✗ {$ext} extension is NOT loaded</p>";
    }
}
echo "</div>";

// 3. Test SMTP connection
echo "<div class='section'>
    <h2>3. SMTP Connection Test</h2>";

$smtp_host = 'tls://' . SMTP_HOST;
$connection = @fsockopen($smtp_host, SMTP_PORT, $errno, $errstr, 10);

if ($connection) {
    echo "<p class='success'>✓ Successfully connected to Gmail SMTP server</p>";
    $response = fgets($connection, 515);
    echo "<p>Server response: <code>" . htmlspecialchars($response) . "</code></p>";
    fclose($connection);
} else {
    echo "<p class='error'>✗ Failed to connect to Gmail SMTP server</p>";
    echo "<p>Error: {$errstr} ({$errno})</p>";
    echo "<p class='warning'>⚠ Your firewall or ISP might be blocking port 587</p>";
}
echo "</div>";

// 4. Test email sending
echo "<div class='section'>
    <h2>4. Send Test Email</h2>
    <form method='POST' action=''>
        <p>
            <label>Enter your email to receive a test message:</label><br>
            <input type='email' name='test_email' value='" . SMTP_FROM_EMAIL . "' required style='width: 300px; padding: 8px; margin: 10px 0;'>
        </p>
        <button type='submit' name='send_test' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>
            Send Test Email
        </button>
    </form>";

if (isset($_POST['send_test'])) {
    $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
    
    if ($test_email) {
        echo "<hr><p>Sending test email to: <strong>{$test_email}</strong></p>";
        
        require_once 'email_service_smtp.php';
        
        $testData = [
            'appointment_id' => 999,
            'full_name' => 'Test User',
            'email' => $test_email,
            'appointment_date' => date('Y-m-d', strtotime('+1 day')),
            'appointment_time' => '10:00:00',
            'symptoms' => 'This is a test email',
            'priority_level' => 'normal',
            'priority_score' => 50,
            'doctor_name' => 'Dr. Test Doctor'
        ];
        
        $result = EmailService::sendAppointmentConfirmation($testData);
        
        if ($result) {
            echo "<p class='success'>✓ Test email sent successfully! Check your inbox (and spam folder).</p>";
        } else {
            echo "<p class='error'>✗ Failed to send test email. Check error logs.</p>";
        }
    } else {
        echo "<p class='error'>✗ Invalid email address</p>";
    }
}

echo "</div>";

// 5. Check error logs
echo "<div class='section'>
    <h2>5. Recent PHP Errors</h2>";

$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $errors = file($error_log);
    $recent_errors = array_slice($errors, -10);
    
    if (!empty($recent_errors)) {
        echo "<pre style='background: #f4f4f4; padding: 10px; overflow-x: auto; font-size: 12px;'>";
        foreach ($recent_errors as $error) {
            if (stripos($error, 'smtp') !== false || stripos($error, 'email') !== false) {
                echo htmlspecialchars($error);
            }
        }
        echo "</pre>";
    } else {
        echo "<p>No recent errors found</p>";
    }
} else {
    echo "<p class='warning'>⚠ Error log file not found. Check php.ini for error_log location.</p>";
}

echo "</div>";

// 6. Recommendations
echo "<div class='section'>
    <h2>6. Troubleshooting Tips</h2>
    <ul>
        <li><strong>Gmail App Password:</strong> Make sure you're using an App Password, not your regular Gmail password</li>
        <li><strong>2-Step Verification:</strong> Must be enabled in your Google account</li>
        <li><strong>Less Secure Apps:</strong> Not needed when using App Passwords</li>
        <li><strong>Firewall:</strong> Ensure port 587 is not blocked</li>
        <li><strong>Check Spam:</strong> Test emails might go to spam folder</li>
        <li><strong>PHP OpenSSL:</strong> Must be enabled for TLS/SSL connections</li>
    </ul>
    
    <h3>How to Generate Gmail App Password:</h3>
    <ol>
        <li>Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>
        <li>Enable 2-Step Verification if not already enabled</li>
        <li>Go to <a href='https://myaccount.google.com/apppasswords' target='_blank'>App Passwords</a></li>
        <li>Select 'Mail' and 'Other (Custom name)'</li>
        <li>Enter 'HospiLink' as the name</li>
        <li>Click 'Generate'</li>
        <li>Copy the 16-character password and update SMTP_PASSWORD in email_config.php</li>
    </ol>
</div>";

echo "</body></html>";
?>
