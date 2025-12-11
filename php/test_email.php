<?php
/**
 * Email Configuration Test Script for HospiLink
 * 
 * This script tests if the Gmail SMTP configuration is working correctly
 * Usage: Open this file in a browser or run from command line
 * 
 * To use: 
 * 1. Edit the $testEmail variable below with your email
 * 2. Access this file via browser at: http://localhost/HospiLink-DE/php/test_email.php
 * 3. Check your email inbox for the test message
 */

include 'email_service_smtp.php';

// Change this to your email address to test
$testEmail = "asrajput5656@gmail.com";

// Check if this is a form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = isset($_POST['email']) ? $_POST['email'] : $testEmail;
    
    echo '<h2>Testing Gmail SMTP Configuration...</h2>';
    echo '<p>Sending test email to: <strong>' . htmlspecialchars($testEmail) . '</strong></p>';
    
    $result = EmailService::sendTestEmail($testEmail);
    
    if ($result) {
        echo '<div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">';
        echo '<h3>✓ Success!</h3>';
        echo '<p>Test email sent successfully! Please check your inbox (and spam folder).</p>';
        echo '</div>';
    } else {
        echo '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">';
        echo '<h3>✗ Failed!</h3>';
        echo '<p>Failed to send test email. Please check:</p>';
        echo '<ul>';
        echo '<li>Gmail SMTP credentials are correct</li>';
        echo '<li>Gmail account has 2-Step Verification enabled</li>';
        echo '<li>You have generated an App Password</li>';
        echo '<li>Firewall/ISP allows SMTP port 587</li>';
        echo '</ul>';
        echo '<p>Check error logs for more details.</p>';
        echo '</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HospiLink - Email Configuration Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            color: #0e545f;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #00adb5;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .config-info {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }
        
        .config-info h3 {
            color: #0e545f;
            margin-bottom: 10px;
        }
        
        .config-info p {
            margin: 5px 0;
            line-height: 1.6;
        }
        
        .config-info code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            color: #d63384;
        }
        
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 HospiLink Email Test</h1>
        <p class="subtitle">Test Gmail SMTP Configuration</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Test Email Address:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($testEmail); ?>" required>
            </div>
            <button type="submit">Send Test Email</button>
        </form>
        
        <hr>
        
        <div class="config-info">
            <h3>Current Configuration</h3>
            <p><strong>SMTP Server:</strong> <code>smtp.gmail.com</code></p>
            <p><strong>Port:</strong> <code>587</code> (TLS)</p>
            <p><strong>From Email:</strong> <code>asrajput5656@gmail.com</code></p>
            <p><strong>Status:</strong> <?php echo EMAIL_ENABLED ? '<span style="color: green;">✓ Enabled</span>' : '<span style="color: red;">✗ Disabled</span>'; ?></p>
        </div>
        
        <div class="config-info">
            <h3>Setup Instructions</h3>
            <p><strong>If email is not working:</strong></p>
            <ol style="margin-left: 20px;">
                <li>Ensure 2-Step Verification is enabled on Gmail</li>
                <li>Generate an App Password at <code>myaccount.google.com/apppasswords</code></li>
                <li>Update <code>SMTP_PASSWORD</code> in <code>email_config.php</code></li>
                <li>Make sure firewall allows SMTP port 587</li>
                <li>Check PHP error logs for detailed error messages</li>
            </ol>
        </div>
    </div>
</body>
</html>
