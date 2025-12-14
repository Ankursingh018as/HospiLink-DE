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
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 40px 0;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); padding: 40px; text-align: center; border-radius: 10px 10px 0 0;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 32px;">🏥 Welcome to HospiLink!</h1>
                                    <p style="color: #ffffff; margin: 15px 0 0 0; font-size: 18px;">Your health, our priority</p>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="color: #0e545f; margin: 0 0 20px 0;">Hello ' . htmlspecialchars($firstName) . '!</h2>
                                    <p style="color: #333; font-size: 16px; line-height: 1.8; margin: 0 0 25px 0;">
                                        We\'re thrilled to have you join the HospiLink family! Your account has been successfully created, and you now have access to our comprehensive healthcare platform.
                                    </p>
                                    
                                    <div style="background: linear-gradient(135deg, #e8f8f9 0%, #f0f9fa 100%); border-left: 4px solid #00adb5; padding: 25px; border-radius: 8px; margin: 30px 0;">
                                        <h3 style="color: #0e545f; margin: 0 0 15px 0; font-size: 20px;">🎯 Getting Started</h3>
                                        <ul style="margin: 0; padding-left: 20px; color: #333; line-height: 1.8;">
                                            <li style="margin-bottom: 10px;"><strong>Book Appointments:</strong> Schedule visits with our expert doctors at your convenience</li>
                                            <li style="margin-bottom: 10px;"><strong>AI Priority System:</strong> Your symptoms are automatically analyzed to prioritize urgent cases</li>
                                            <li style="margin-bottom: 10px;"><strong>Medical History:</strong> Access your complete health records anytime, anywhere</li>
                                            <li style="margin-bottom: 10px;"><strong>Reminders:</strong> Receive automated notifications for upcoming appointments</li>
                                        </ul>
                                    </div>
                                    
                                    <h3 style="color: #0e545f; margin: 30px 0 15px 0;">📋 How to Book Your First Appointment</h3>
                                    <ol style="margin: 0; padding-left: 20px; color: #333; line-height: 1.8;">
                                        <li style="margin-bottom: 10px;">Login to your patient dashboard</li>
                                        <li style="margin-bottom: 10px;">Navigate to "Book Appointment"</li>
                                        <li style="margin-bottom: 10px;">Fill in your details and describe your symptoms</li>
                                        <li style="margin-bottom: 10px;">Our AI will prioritize your case and assign you to the right specialist</li>
                                        <li style="margin-bottom: 10px;">You\'ll receive a confirmation email with appointment details</li>
                                    </ol>
                                    
                                    <div style="text-align: center; margin: 40px 0;">
                                        <a href="' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/sign_new.html" style="display: inline-block; background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: bold;">Access Your Dashboard</a>
                                    </div>
                                    
                                    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 30px 0; border-radius: 5px;">
                                        <p style="margin: 0; color: #856404; font-size: 14px;">
                                            💡 <strong>Pro Tip:</strong> Describe your symptoms in detail for better AI analysis and faster appointment scheduling!
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Support -->
                            <tr>
                                <td style="background-color: #f8f9fa; padding: 25px 30px; border-top: 1px solid #e9ecef;">
                                    <h3 style="color: #0e545f; margin: 0 0 15px 0; font-size: 18px;">Need Help?</h3>
                                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                        Our support team is here 24/7 to assist you. Contact us at <a href="mailto:' . SMTP_FROM_EMAIL . '" style="color: #00adb5; text-decoration: none;">' . SMTP_FROM_EMAIL . '</a>
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #0e545f; padding: 25px; text-align: center; border-radius: 0 0 10px 10px;">
                                    <p style="color: #ffffff; margin: 0; font-size: 14px;">
                                        © ' . date('Y') . ' HospiLink. All rights reserved.
                                    </p>
                                    <p style="color: #00adb5; margin: 10px 0 0 0; font-size: 13px;">
                                        Empowering healthcare through technology
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
    
    /**
     * Doctor Welcome Email Template
     */
    private static function createDoctorWelcomeEmail($firstName) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 40px 0;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); padding: 40px; text-align: center; border-radius: 10px 10px 0 0;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 32px;">🩺 Welcome, Dr. ' . htmlspecialchars($firstName) . '!</h1>
                                    <p style="color: #ffffff; margin: 15px 0 0 0; font-size: 18px;">Join our team of healthcare excellence</p>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="color: #0e545f; margin: 0 0 20px 0;">Welcome to HospiLink!</h2>
                                    <p style="color: #333; font-size: 16px; line-height: 1.8; margin: 0 0 25px 0;">
                                        Thank you for joining our medical team! Your account has been successfully created. We\'re excited to have your expertise on our platform.
                                    </p>
                                    
                                    <div style="background: linear-gradient(135deg, #e8f8f9 0%, #f0f9fa 100%); border-left: 4px solid #00adb5; padding: 25px; border-radius: 8px; margin: 30px 0;">
                                        <h3 style="color: #0e545f; margin: 0 0 15px 0; font-size: 20px;">🎯 Your Doctor Dashboard</h3>
                                        <ul style="margin: 0; padding-left: 20px; color: #333; line-height: 1.8;">
                                            <li style="margin-bottom: 10px;"><strong>AI-Prioritized Queue:</strong> Appointments automatically sorted by urgency</li>
                                            <li style="margin-bottom: 10px;"><strong>Patient Records:</strong> Instant access to complete medical histories</li>
                                            <li style="margin-bottom: 10px;"><strong>Appointment Management:</strong> View, update, and manage all your appointments</li>
                                            <li style="margin-bottom: 10px;"><strong>Real-time Stats:</strong> Monitor your daily appointments and pending cases</li>
                                        </ul>
                                    </div>
                                    
                                    <h3 style="color: #0e545f; margin: 30px 0 15px 0;">📋 Key Features</h3>
                                    <div style="margin: 20px 0;">
                                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                                            <h4 style="color: #0e545f; margin: 0 0 10px 0; font-size: 16px;">🚨 Priority-Based System</h4>
                                            <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                                Our AI analyzes patient symptoms and assigns priority levels (Critical, High, Medium, Low) to help you focus on urgent cases first.
                                            </p>
                                        </div>
                                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                                            <h4 style="color: #0e545f; margin: 0 0 10px 0; font-size: 16px;">📊 Comprehensive Dashboard</h4>
                                            <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                                Track your appointments, view patient details, add diagnosis notes, and manage your schedule efficiently.
                                            </p>
                                        </div>
                                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
                                            <h4 style="color: #0e545f; margin: 0 0 10px 0; font-size: 16px;">📝 Activity Logging</h4>
                                            <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                                All actions are logged for security and audit purposes, ensuring accountability and patient safety.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div style="text-align: center; margin: 40px 0;">
                                        <a href="' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/sign_new.html" style="display: inline-block; background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: bold;">Access Your Dashboard</a>
                                    </div>
                                    
                                    <div style="background-color: #d1ecf1; border-left: 4px solid #00adb5; padding: 15px; margin: 30px 0; border-radius: 5px;">
                                        <p style="margin: 0; color: #0c5460; font-size: 14px;">
                                            ℹ️ <strong>First Time Login:</strong> Use your registered email and password to access your doctor dashboard.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Support -->
                            <tr>
                                <td style="background-color: #f8f9fa; padding: 25px 30px; border-top: 1px solid #e9ecef;">
                                    <h3 style="color: #0e545f; margin: 0 0 15px 0; font-size: 18px;">Need Assistance?</h3>
                                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                        Our admin team is here to support you. For any questions or technical issues, reach out at <a href="mailto:' . SMTP_FROM_EMAIL . '" style="color: #00adb5; text-decoration: none;">' . SMTP_FROM_EMAIL . '</a>
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #0e545f; padding: 25px; text-align: center; border-radius: 0 0 10px 10px;">
                                    <p style="color: #ffffff; margin: 0; font-size: 14px;">
                                        © ' . date('Y') . ' HospiLink. All rights reserved.
                                    </p>
                                    <p style="color: #00adb5; margin: 10px 0 0 0; font-size: 13px;">
                                        Advancing healthcare through innovation
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
    
    /**
     * Admin Welcome Email Template
     */
    private static function createAdminWelcomeEmail($firstName) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 40px 0;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); padding: 40px; text-align: center; border-radius: 10px 10px 0 0;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 32px;">🛡️ Welcome, ' . htmlspecialchars($firstName) . '!</h1>
                                    <p style="color: #ffffff; margin: 15px 0 0 0; font-size: 18px;">Hospital Staff Administrator Access</p>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="color: #0e545f; margin: 0 0 20px 0;">Welcome to HospiLink Admin!</h2>
                                    <p style="color: #333; font-size: 16px; line-height: 1.8; margin: 0 0 25px 0;">
                                        Your admin account has been successfully created. You now have access to comprehensive hospital management tools and oversight capabilities.
                                    </p>
                                    
                                    <div style="background: linear-gradient(135deg, #e8f8f9 0%, #f0f9fa 100%); border-left: 4px solid #00adb5; padding: 25px; border-radius: 8px; margin: 30px 0;">
                                        <h3 style="color: #0e545f; margin: 0 0 15px 0; font-size: 20px;">🎯 Admin Dashboard Features</h3>
                                        <ul style="margin: 0; padding-left: 20px; color: #333; line-height: 1.8;">
                                            <li style="margin-bottom: 10px;"><strong>Patient Admissions:</strong> Manage patient admission and discharge workflows</li>
                                            <li style="margin-bottom: 10px;"><strong>Bed Management:</strong> Real-time bed availability across all wards</li>
                                            <li style="margin-bottom: 10px;"><strong>Appointment Oversight:</strong> Monitor all hospital appointments</li>
                                            <li style="margin-bottom: 10px;"><strong>Activity Logs:</strong> Complete audit trail of all system activities</li>
                                            <li style="margin-bottom: 10px;"><strong>Coordination Tools:</strong> Facilitate communication between departments</li>
                                        </ul>
                                    </div>
                                    
                                    <h3 style="color: #0e545f; margin: 30px 0 15px 0;">📋 Key Responsibilities</h3>
                                    <div style="margin: 20px 0;">
                                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                                            <h4 style="color: #0e545f; margin: 0 0 10px 0; font-size: 16px;">🏥 Bed & Ward Management</h4>
                                            <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                                Track bed availability in real-time, assign patients to appropriate wards, and manage bed status (available, occupied, maintenance).
                                            </p>
                                        </div>
                                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                                            <h4 style="color: #0e545f; margin: 0 0 10px 0; font-size: 16px;">📊 System Monitoring</h4>
                                            <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                                View comprehensive statistics, monitor appointment flow, and track system usage across all departments.
                                            </p>
                                        </div>
                                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
                                            <h4 style="color: #0e545f; margin: 0 0 10px 0; font-size: 16px;">🔍 Activity Auditing</h4>
                                            <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                                Access detailed activity logs for security compliance, including user actions, timestamps, and IP addresses.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div style="text-align: center; margin: 40px 0;">
                                        <a href="' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/sign_new.html" style="display: inline-block; background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: bold;">Access Admin Dashboard</a>
                                    </div>
                                    
                                    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 30px 0; border-radius: 5px;">
                                        <p style="margin: 0; color: #856404; font-size: 14px;">
                                            ⚠️ <strong>Security Notice:</strong> As an admin, you have elevated privileges. Please ensure you follow all security protocols and never share your credentials.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Support -->
                            <tr>
                                <td style="background-color: #f8f9fa; padding: 25px 30px; border-top: 1px solid #e9ecef;">
                                    <h3 style="color: #0e545f; margin: 0 0 15px 0; font-size: 18px;">Technical Support</h3>
                                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                        For technical assistance or to report issues, contact our IT team at <a href="mailto:' . SMTP_FROM_EMAIL . '" style="color: #00adb5; text-decoration: none;">' . SMTP_FROM_EMAIL . '</a>
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #0e545f; padding: 25px; text-align: center; border-radius: 0 0 10px 10px;">
                                    <p style="color: #ffffff; margin: 0; font-size: 14px;">
                                        © ' . date('Y') . ' HospiLink. All rights reserved.
                                    </p>
                                    <p style="color: #00adb5; margin: 10px 0 0 0; font-size: 13px;">
                                        Streamlining healthcare operations
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}
?>
