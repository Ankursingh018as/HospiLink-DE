<?php
/**
 * Onboarding Email Service for HospiLink
 * Sends role-specific welcome emails after successful registration
 */

require_once 'email_config.php';

class OnboardingEmailService {
    
    /**
     * Send role-specific onboarding email
     */
    public static function sendWelcomeEmail($userData) {
        if (!EMAIL_ENABLED) {
            return true;
        }
        
        $to = $userData['email'];
        $firstName = $userData['firstName'];
        $role = $userData['role'];
        
        $subject = "Welcome to HospiLink - Your Healthcare Journey Begins!";
        
        switch ($role) {
            case 'patient':
                $message = self::createPatientWelcomeEmail($firstName);
                break;
            case 'doctor':
                $message = self::createDoctorWelcomeEmail($firstName);
                break;
            case 'admin':
                $message = self::createAdminWelcomeEmail($firstName);
                break;
            default:
                return false;
        }
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        return mail($to, $subject, $message, $headers);
    }
    
    /**
     * Patient Welcome Email Template
     */
    private static function createPatientWelcomeEmail($firstName) {
        $dashUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/sign_new.html';
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background-color: #f8fafc;
                    color: #334155;
                    margin: 0;
                    padding: 0;
                }
                .wrapper {
                    max-width: 580px;
                    margin: 40px auto;
                    background-color: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                }
                .header {
                    padding: 32px 40px 20px 40px;
                    border-bottom: 1px solid #f1f5f9;
                }
                .header-logo {
                    font-size: 18px;
                    font-weight: 700;
                    color: #0d9488;
                    letter-spacing: -0.5px;
                }
                .header-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 12px;
                    margin-bottom: 0;
                }
                .body {
                    padding: 32px 40px;
                }
                .body h2 {
                    font-size: 18px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 0;
                    margin-bottom: 12px;
                }
                .body p {
                    font-size: 15px;
                    line-height: 1.6;
                    color: #475569;
                    margin-top: 0;
                    margin-bottom: 16px;
                }
                .info-card {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 24px 0;
                }
                .info-card h3 {
                    color: #0f172a;
                    margin: 0 0 12px 0;
                    font-size: 16px;
                }
                .info-card ul {
                    margin: 0;
                    padding-left: 20px;
                    color: #475569;
                    line-height: 1.7;
                }
                .btn {
                    display: inline-block;
                    background-color: #0d9488;
                    color: #ffffff !important;
                    text-decoration: none;
                    padding: 12px 30px;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .pro-tip {
                    background-color: #fffbef;
                    border-left: 4px solid #f59e0b;
                    padding: 12px;
                    margin: 24px 0;
                    border-radius: 4px;
                    font-size: 14px;
                    color: #b45309;
                }
                .footer {
                    background-color: #f8fafc;
                    padding: 24px 40px;
                    border-top: 1px solid #e2e8f0;
                    text-align: center;
                }
                .footer p {
                    font-size: 12px;
                    color: #64748b;
                    line-height: 1.5;
                    margin: 0;
                }
            </style>
        </head>
        <body>
            <div class="wrapper">
                <div class="header">
                    <div class="header-logo">HospiLink</div>
                    <div class="header-title">Welcome to HospiLink</div>
                </div>
                <div class="body">
                    <h2>Hello ' . htmlspecialchars($firstName) . ',</h2>
                    <p>We are thrilled to have you join the HospiLink family! Your account has been successfully created, and you now have access to our comprehensive healthcare platform.</p>
                    
                    <div class="info-card">
                        <h3>Getting Started</h3>
                        <ul>
                            <li><strong>Book Appointments:</strong> Schedule visits with our expert doctors at your convenience.</li>
                            <li><strong>AI Priority System:</strong> Your symptoms are automatically analyzed to prioritize urgent cases.</li>
                            <li><strong>Medical History:</strong> Access your complete health records anytime, anywhere.</li>
                            <li><strong>Reminders:</strong> Receive automated notifications for upcoming appointments.</li>
                        </ul>
                    </div>
                    
                    <h2>How to Book Your First Appointment</h2>
                    <p>1. Login to your patient dashboard.<br>2. Navigate to "Book Appointment".<br>3. Fill in your details and describe your symptoms.<br>4. Our system will analyze your case details and route you to the right specialist.</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $dashUrl . '" class="btn">Access Your Dashboard</a>
                    </div>
                    
                    <div class="pro-tip">
                        <strong>Pro Tip:</strong> Describe your symptoms in detail for more accurate clinical analysis and faster scheduling.
                    </div>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' HospiLink. All rights reserved.<br>Support: ' . SMTP_FROM_EMAIL . '</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Doctor Welcome Email Template
     */
    private static function createDoctorWelcomeEmail($firstName) {
        $dashUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/sign_new.html';
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background-color: #f8fafc;
                    color: #334155;
                    margin: 0;
                    padding: 0;
                }
                .wrapper {
                    max-width: 580px;
                    margin: 40px auto;
                    background-color: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                }
                .header {
                    padding: 32px 40px 20px 40px;
                    border-bottom: 1px solid #f1f5f9;
                }
                .header-logo {
                    font-size: 18px;
                    font-weight: 700;
                    color: #0d9488;
                    letter-spacing: -0.5px;
                }
                .header-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 12px;
                    margin-bottom: 0;
                }
                .body {
                    padding: 32px 40px;
                }
                .body h2 {
                    font-size: 18px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 0;
                    margin-bottom: 12px;
                }
                .body p {
                    font-size: 15px;
                    line-height: 1.6;
                    color: #475569;
                    margin-top: 0;
                    margin-bottom: 16px;
                }
                .info-card {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 24px 0;
                }
                .info-card h3 {
                    color: #0f172a;
                    margin: 0 0 12px 0;
                    font-size: 16px;
                }
                .info-card ul {
                    margin: 0;
                    padding-left: 20px;
                    color: #475569;
                    line-height: 1.7;
                }
                .feature-block {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    padding: 16px;
                    border-radius: 6px;
                    margin-bottom: 12px;
                }
                .feature-block h4 {
                    margin: 0 0 6px 0;
                    color: #0f172a;
                    font-size: 15px;
                }
                .feature-block p {
                    margin: 0;
                    font-size: 13.5px;
                    line-height: 1.5;
                }
                .btn {
                    display: inline-block;
                    background-color: #0d9488;
                    color: #ffffff !important;
                    text-decoration: none;
                    padding: 12px 30px;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .footer {
                    background-color: #f8fafc;
                    padding: 24px 40px;
                    border-top: 1px solid #e2e8f0;
                    text-align: center;
                }
                .footer p {
                    font-size: 12px;
                    color: #64748b;
                    line-height: 1.5;
                    margin: 0;
                }
            </style>
        </head>
        <body>
            <div class="wrapper">
                <div class="header">
                    <div class="header-logo">HospiLink</div>
                    <div class="header-title">Welcome to our Medical Team</div>
                </div>
                <div class="body">
                    <h2>Hello Dr. ' . htmlspecialchars($firstName) . ',</h2>
                    <p>Thank you for joining our medical team! Your account has been successfully created. We are excited to have your expertise on our platform.</p>
                    
                    <div class="info-card">
                        <h3>Your Doctor Dashboard</h3>
                        <ul>
                            <li><strong>AI-Prioritized Queue:</strong> Appointments automatically sorted by urgency.</li>
                            <li><strong>Patient Records:</strong> Instant access to complete medical histories.</li>
                            <li><strong>Appointment Management:</strong> View, update, and manage all your appointments.</li>
                            <li><strong>Real-time Stats:</strong> Monitor your daily appointments and pending cases.</li>
                        </ul>
                    </div>
                    
                    <h2>Key Platform Features</h2>
                    <div class="feature-block">
                        <h4>Priority-Based System</h4>
                        <p>Our system analyzes patient symptoms and assigns priority levels to help you focus on urgent cases first.</p>
                    </div>
                    <div class="feature-block">
                        <h4>Comprehensive Dashboard</h4>
                        <p>Track your appointments, view patient details, add diagnosis notes, and manage your schedule efficiently.</p>
                    </div>
                    <div class="feature-block">
                        <h4>Activity Logging</h4>
                        <p>All clinical actions are logged for security and audit purposes, ensuring accountability and patient safety.</p>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="' . $dashUrl . '" class="btn">Access Your Dashboard</a>
                    </div>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' HospiLink. All rights reserved.<br>Support: ' . SMTP_FROM_EMAIL . '</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Admin Welcome Email Template
     */
    private static function createAdminWelcomeEmail($firstName) {
        $dashUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/sign_new.html';
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background-color: #f8fafc;
                    color: #334155;
                    margin: 0;
                    padding: 0;
                }
                .wrapper {
                    max-width: 580px;
                    margin: 40px auto;
                    background-color: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                }
                .header {
                    padding: 32px 40px 20px 40px;
                    border-bottom: 1px solid #f1f5f9;
                }
                .header-logo {
                    font-size: 18px;
                    font-weight: 700;
                    color: #0d9488;
                    letter-spacing: -0.5px;
                }
                .header-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 12px;
                    margin-bottom: 0;
                }
                .body {
                    padding: 32px 40px;
                }
                .body h2 {
                    font-size: 18px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 0;
                    margin-bottom: 12px;
                }
                .body p {
                    font-size: 15px;
                    line-height: 1.6;
                    color: #475569;
                    margin-top: 0;
                    margin-bottom: 16px;
                }
                .info-card {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 24px 0;
                }
                .info-card h3 {
                    color: #0f172a;
                    margin: 0 0 12px 0;
                    font-size: 16px;
                }
                .info-card ul {
                    margin: 0;
                    padding-left: 20px;
                    color: #475569;
                    line-height: 1.7;
                }
                .feature-block {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    padding: 16px;
                    border-radius: 6px;
                    margin-bottom: 12px;
                }
                .feature-block h4 {
                    margin: 0 0 6px 0;
                    color: #0f172a;
                    font-size: 15px;
                }
                .feature-block p {
                    margin: 0;
                    font-size: 13.5px;
                    line-height: 1.5;
                }
                .btn {
                    display: inline-block;
                    background-color: #0d9488;
                    color: #ffffff !important;
                    text-decoration: none;
                    padding: 12px 30px;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .security-notice {
                    background-color: #fffbef;
                    border-left: 4px solid #f59e0b;
                    padding: 12px;
                    margin: 24px 0;
                    border-radius: 4px;
                    font-size: 14px;
                    color: #b45309;
                }
                .footer {
                    background-color: #f8fafc;
                    padding: 24px 40px;
                    border-top: 1px solid #e2e8f0;
                    text-align: center;
                }
                .footer p {
                    font-size: 12px;
                    color: #64748b;
                    line-height: 1.5;
                    margin: 0;
                }
            </style>
        </head>
        <body>
            <div class="wrapper">
                <div class="header">
                    <div class="header-logo">HospiLink</div>
                    <div class="header-title">Administrator Portal Access</div>
                </div>
                <div class="body">
                    <h2>Hello ' . htmlspecialchars($firstName) . ',</h2>
                    <p>Your admin account has been successfully created. You now have access to comprehensive hospital management tools and oversight capabilities.</p>
                    
                    <div class="info-card">
                        <h3>Admin Dashboard Features</h3>
                        <ul>
                            <li><strong>Patient Admissions:</strong> Manage patient admission and discharge workflows.</li>
                            <li><strong>Bed Management:</strong> Real-time bed availability across all wards.</li>
                            <li><strong>Appointment Oversight:</strong> Monitor all hospital appointments.</li>
                            <li><strong>Activity Logs:</strong> Complete audit trail of all system activities.</li>
                            <li><strong>Coordination Tools:</strong> Facilitate communication between departments.</li>
                        </ul>
                    </div>
                    
                    <h2>Key Responsibilities</h2>
                    <div class="feature-block">
                        <h4>Bed & Ward Management</h4>
                        <p>Track bed availability in real-time, assign patients to appropriate wards, and manage bed status (available, occupied, maintenance).</p>
                    </div>
                    <div class="feature-block">
                        <h4>System Monitoring</h4>
                        <p>View comprehensive statistics, monitor appointment flow, and track system usage across all departments.</p>
                    </div>
                    <div class="feature-block">
                        <h4>Activity Auditing</h4>
                        <p>Access detailed activity logs for security compliance, including user actions, timestamps, and details.</p>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="' . $dashUrl . '" class="btn">Access Admin Dashboard</a>
                    </div>
                    
                    <div class="security-notice">
                        <strong>Security Notice:</strong> As an administrator, you have elevated privileges. Please ensure you follow all security protocols and never share your credentials.
                    </div>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' HospiLink. All rights reserved.<br>Technical Support: ' . SMTP_FROM_EMAIL . '</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
?>
