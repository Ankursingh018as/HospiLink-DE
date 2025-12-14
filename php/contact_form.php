<?php
session_start();
require_once 'email_config.php';
require_once 'email_service_smtp.php';

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $speciality = trim($_POST['speciality'] ?? '');
    $doctor = trim($_POST['doctor'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($speciality) || empty($doctor) || empty($message)) {
        header('Location: /HospiLink-DE/contact.html?error=' . urlencode('All fields are required.'));
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: /HospiLink-DE/contact.html?error=' . urlencode('Invalid email address.'));
        exit();
    }
    
    // Prepare email content
    $subject = "New Contact Form Submission - HospiLink";
    
    $emailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #00adb5 0%, #0e545f 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content {
                padding: 30px;
            }
            .info-section {
                background: #f8f9fa;
                border-left: 4px solid #00adb5;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            .info-row {
                display: flex;
                margin-bottom: 12px;
            }
            .info-label {
                font-weight: 600;
                color: #0e545f;
                min-width: 120px;
            }
            .info-value {
                color: #555;
            }
            .message-section {
                background: #ffffff;
                border: 2px solid #e9ecef;
                padding: 20px;
                border-radius: 8px;
                margin-top: 20px;
            }
            .message-section h3 {
                color: #0e545f;
                margin-top: 0;
            }
            .message-text {
                color: #333;
                line-height: 1.8;
                white-space: pre-wrap;
            }
            .footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #666;
                font-size: 14px;
                border-top: 1px solid #e9ecef;
            }
            .badge {
                display: inline-block;
                background: linear-gradient(135deg, #00adb5 0%, #0e545f 100%);
                color: white;
                padding: 5px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 600;
                margin-top: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📬 New Contact Form Submission</h1>
                <p style='margin: 5px 0 0 0; opacity: 0.9;'>HospiLink Contact System</p>
            </div>
            
            <div class='content'>
                <div class='info-section'>
                    <div class='info-row'>
                        <span class='info-label'>👤 Name:</span>
                        <span class='info-value'><strong>" . htmlspecialchars($name) . "</strong></span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>📧 Email:</span>
                        <span class='info-value'>" . htmlspecialchars($email) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>🏥 Speciality:</span>
                        <span class='info-value'>" . htmlspecialchars($speciality) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>👨‍⚕️ Doctor:</span>
                        <span class='info-value'>" . htmlspecialchars($doctor) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>📅 Date:</span>
                        <span class='info-value'>" . date('F d, Y') . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>🕒 Time:</span>
                        <span class='info-value'>" . date('h:i A') . "</span>
                    </div>
                </div>
                
                <div class='message-section'>
                    <h3>💬 Message Content:</h3>
                    <div class='message-text'>" . htmlspecialchars($message) . "</div>
                </div>
                
                <div style='margin-top: 25px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196F3;'>
                    <p style='margin: 0; color: #1976D2;'>
                        <strong>📌 Action Required:</strong> Please respond to this inquiry at your earliest convenience.
                    </p>
                </div>
            </div>
            
            <div class='footer'>
                <p style='margin: 5px 0;'><strong>HospiLink Hospital</strong></p>
                <p style='margin: 5px 0;'>📞 +91-6353439877 | 📍 Dahod, Gujarat</p>
                <p style='margin: 10px 0 5px 0; color: #999; font-size: 12px;'>
                    This is an automated message from HospiLink Contact System
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using existing SMTP service
    $emailService = new EmailService();
    $result = $emailService->sendEmail(
        SMTP_FROM_EMAIL,  // Send to the configured SMTP email
        SMTP_FROM_NAME,
        $subject,
        $emailBody
    );
    
    if ($result['success']) {
        header('Location: /HospiLink-DE/contact.html?success=' . urlencode('Thank you for contacting us! We will get back to you soon.'));
    } else {
        header('Location: /HospiLink-DE/contact.html?error=' . urlencode('Failed to send message. Please try again or contact us directly.'));
    }
    exit();
} else {
    // If accessed directly, redirect to contact page
    header('Location: /HospiLink-DE/contact.html');
    exit();
}
?>
