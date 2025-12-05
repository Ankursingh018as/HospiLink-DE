<?php
/**
 * Email Service for HospiLink
 * Handles sending appointment confirmation emails
 */

require_once 'email_config.php';

class EmailService {
    
    /**
     * Send appointment confirmation email using PHP mail() function
     * This is a simple implementation that works with most servers
     */
    public static function sendAppointmentConfirmation($appointmentData) {
        if (!EMAIL_ENABLED) {
            return true; // Email disabled, return success
        }
        
        $to = $appointmentData['email'];
        $subject = "Appointment Confirmation - HospiLink (ID: #" . $appointmentData['appointment_id'] . ")";
        
        // Create email body
        $message = self::createEmailTemplate($appointmentData);
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send email
        $sent = mail($to, $subject, $message, $headers);
        
        return $sent;
    }
    
    /**
     * Create HTML email template
     */
    private static function createEmailTemplate($data) {
        $appointment_id = $data['appointment_id'];
        $name = $data['full_name'];
        $email = $data['email'];
        $date = date('l, F j, Y', strtotime($data['appointment_date']));
        $time = date('h:i A', strtotime($data['appointment_time']));
        $symptoms = $data['symptoms'];
        $priority = strtoupper($data['priority_level']);
        $doctor = $data['doctor_name'] ?? 'To be assigned';
        
        // Priority badge colors
        $priorityColors = [
            'CRITICAL' => '#dc3545',
            'HIGH' => '#fd7e14',
            'MEDIUM' => '#ffc107',
            'LOW' => '#28a745'
        ];
        $priorityColor = $priorityColors[$priority] ?? '#6c757d';
        
        // Priority messages
        $priorityMessages = [
            'CRITICAL' => '🚨 URGENT: Please proceed to the emergency department immediately or call emergency services if symptoms worsen!',
            'HIGH' => '⚡ Your appointment has been marked as high priority. A doctor will contact you soon.',
            'MEDIUM' => '📋 Your appointment has been scheduled. Please arrive 10 minutes early.',
            'LOW' => '✓ Your appointment has been confirmed. See you on the scheduled date.'
        ];
        $priorityMessage = $priorityMessages[$priority] ?? 'Your appointment has been scheduled.';
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">HospiLink</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 14px;">Your Healthcare Partner</p>
                        </td>
                    </tr>
                    
                    <!-- Appointment Confirmed -->
                    <tr>
                        <td style="padding: 30px; text-align: center;">
                            <div style="display: inline-block; background-color: #28a745; color: white; padding: 10px 30px; border-radius: 50px; font-size: 16px; font-weight: bold;">
                                ✓ APPOINTMENT CONFIRMED
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Appointment Details -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <h2 style="color: #333; margin-bottom: 20px;">Hello, {$name}!</h2>
                            <p style="color: #666; font-size: 16px; line-height: 1.6;">
                                Your appointment has been successfully booked at HospiLink Hospital.
                            </p>
                            
                            <!-- Priority Badge -->
                            <div style="margin: 20px 0; padding: 15px; background-color: {$priorityColor}; color: white; border-radius: 8px; text-align: center;">
                                <strong>Priority Level: {$priority}</strong>
                            </div>
                            
                            <div style="margin: 20px 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid {$priorityColor}; border-radius: 4px;">
                                <p style="margin: 0; color: #856404; font-size: 14px;">{$priorityMessage}</p>
                            </div>
                            
                            <!-- Details Box -->
                            <table width="100%" cellpadding="10" style="margin: 20px 0; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <tr style="background-color: #f8f9fa;">
                                    <td style="padding: 15px; border-bottom: 1px solid #e0e0e0;">
                                        <strong style="color: #0e545f;">📋 Appointment ID:</strong><br>
                                        <span style="color: #333; font-size: 18px;">#{$appointment_id}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #e0e0e0;">
                                        <strong style="color: #0e545f;">📅 Date:</strong><br>
                                        <span style="color: #333;">{$date}</span>
                                    </td>
                                </tr>
                                <tr style="background-color: #f8f9fa;">
                                    <td style="padding: 15px; border-bottom: 1px solid #e0e0e0;">
                                        <strong style="color: #0e545f;">🕐 Time:</strong><br>
                                        <span style="color: #333;">{$time}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #e0e0e0;">
                                        <strong style="color: #0e545f;">👨‍⚕️ Doctor:</strong><br>
                                        <span style="color: #333;">{$doctor}</span>
                                    </td>
                                </tr>
                                <tr style="background-color: #f8f9fa;">
                                    <td style="padding: 15px;">
                                        <strong style="color: #0e545f;">📧 Contact Email:</strong><br>
                                        <span style="color: #333;">{$email}</span>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Important Information -->
                            <div style="margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-radius: 8px;">
                                <h3 style="color: #0e545f; margin-top: 0;">📌 Important Information:</h3>
                                <ul style="color: #666; line-height: 1.8; padding-left: 20px;">
                                    <li>Please arrive 15 minutes before your appointment time</li>
                                    <li>Bring a valid ID and any previous medical records</li>
                                    <li>If you need to reschedule, please contact us at least 24 hours in advance</li>
                                    <li>Wear a mask and maintain social distancing</li>
                                </ul>
                            </div>
                            
                            <!-- Contact Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="http://localhost/HospiLink-DE/contact.html" style="display: inline-block; background-color: #00adb5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                                    Contact Us
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0e545f; padding: 20px; text-align: center;">
                            <p style="color: #ffffff; margin: 0; font-size: 14px;">
                                <strong>HospiLink Hospital</strong><br>
                                Dahod, Gujarat, India<br>
                                Phone: +91-9856594589 | Email: hospilink@gmail.com
                            </p>
                            <p style="color: #aaa; margin: 10px 0 0 0; font-size: 12px;">
                                © 2025 HospiLink. All Rights Reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Send test email to verify configuration
     */
    public static function sendTestEmail($toEmail) {
        $subject = "HospiLink - Email Configuration Test";
        
        $message = <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2 style="color: #00adb5;">Email Configuration Successful!</h2>
    <p>If you're seeing this email, your HospiLink email system is working correctly.</p>
    <p>You can now send appointment confirmations to your patients.</p>
    <hr>
    <p style="color: #666; font-size: 12px;">HospiLink Hospital Management System</p>
</body>
</html>
HTML;
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
        
        return mail($toEmail, $subject, $message, $headers);
    }
}
?>
