<?php
/**
 * Gmail SMTP Email Service for HospiLink
 * Sends emails directly via Gmail SMTP server
 * 
 * This service uses direct socket connection to Gmail SMTP
 * No external libraries required!
 */

require_once 'email_config.php';

class EmailService {
    
    private $connection;
    private $response;
    
    /**
     * Send appointment confirmation email via Gmail SMTP
     */
    public static function sendAppointmentConfirmation($appointmentData) {
        if (!EMAIL_ENABLED) {
            return true; // Email disabled, return success
        }
        
        $emailService = new self();
        $to = $appointmentData['email'];
        $subject = "Appointment Confirmation - HospiLink (ID: #" . $appointmentData['appointment_id'] . ")";
        
        // Create email body
        $message = self::createEmailTemplate($appointmentData);
        
        // Send via SMTP
        return $emailService->sendEmailViaSMTP($to, $subject, $message);
    }
    
    /**
     * Send email via Gmail SMTP
     */
    private function sendEmailViaSMTP($to, $subject, $body) {
        try {
            // Connect to Gmail SMTP server (without TLS prefix for port 587)
            $this->connection = fsockopen(
                SMTP_HOST,
                SMTP_PORT,
                $errno,
                $errstr,
                30
            );
            
            if (!$this->connection) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return false;
            }
            
            // Set timeout
            stream_set_timeout($this->connection, 30);
            
            // Read initial response
            $this->readResponse();
            
            // Send EHLO command
            $this->sendCommand("EHLO " . gethostname());
            
            // Start TLS
            $this->sendCommand("STARTTLS");
            
            // Enable TLS encryption
            $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
            if (!stream_socket_enable_crypto($this->connection, true, $crypto_method)) {
                error_log("Failed to enable TLS encryption");
                return false;
            }
            
            // Authenticate
            $this->sendCommand("EHLO " . gethostname());
            $authString = base64_encode(SMTP_USERNAME . ":" . SMTP_PASSWORD);
            $this->sendCommand("AUTH LOGIN");
            
            fwrite($this->connection, base64_encode(SMTP_USERNAME) . "\r\n");
            $this->readResponse();
            
            fwrite($this->connection, base64_encode(SMTP_PASSWORD) . "\r\n");
            $this->readResponse();
            
            // Send email
            $this->sendCommand("MAIL FROM: <" . SMTP_FROM_EMAIL . ">");
            $this->sendCommand("RCPT TO: <" . $to . ">");
            $this->sendCommand("DATA");
            
            // Send headers and body
            $emailHeaders = $this->buildEmailHeaders($to, $subject);
            fwrite($this->connection, $emailHeaders . "\r\n\r\n");
            fwrite($this->connection, $body . "\r\n");
            fwrite($this->connection, ".\r\n");
            
            $this->readResponse();
            
            // Close connection
            $this->sendCommand("QUIT");
            fclose($this->connection);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send SMTP command and read response
     */
    private function sendCommand($command) {
        fwrite($this->connection, $command . "\r\n");
        $this->readResponse();
    }
    
    /**
     * Read SMTP response
     */
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->connection, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        $this->response = $response;
        return $response;
    }
    
    /**
     * Build email headers
     */
    private function buildEmailHeaders($to, $subject) {
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "To: " . $to . "\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "X-Mailer: HospiLink SMTP Service\r\n";
        $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        
        return $headers;
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
                            
                            <!-- Symptoms Summary -->
                            <div style="margin: 20px 0; padding: 15px; background-color: #f0f8ff; border-radius: 8px;">
                                <h4 style="color: #0e545f; margin-top: 0;">📝 Your Symptoms:</h4>
                                <p style="color: #666; line-height: 1.6; margin: 10px 0 0 0;">{$symptoms}</p>
                            </div>
                            
                            <!-- Important Information -->
                            <div style="margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-radius: 8px;">
                                <h3 style="color: #0e545f; margin-top: 0;">📌 Important Information:</h3>
                                <ul style="color: #666; line-height: 1.8; padding-left: 20px; margin-bottom: 0;">
                                    <li>Please arrive 15 minutes before your appointment time</li>
                                    <li>Bring a valid ID and any previous medical records</li>
                                    <li>If you need to reschedule, please contact us at least 24 hours in advance</li>
                                    <li>Wear a mask and maintain social distancing</li>
                                    <li>Keep your Appointment ID (#<strong>{$appointment_id}</strong>) handy for reference</li>
                                </ul>
                            </div>
                            
                            <!-- Contact Information -->
                            <div style="margin: 20px 0; padding: 15px; background-color: #f5f5f5; border-radius: 8px; text-align: center;">
                                <h4 style="color: #0e545f; margin-top: 0;">📞 Need to reschedule or cancel?</h4>
                                <p style="color: #666; margin: 10px 0;">
                                    Contact us at: <strong>+91-9856594589</strong><br>
                                    Email: <strong>hospilink@gmail.com</strong>
                                </p>
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
     * Send test email to verify Gmail SMTP configuration
     */
    public static function sendTestEmail($toEmail) {
        $emailService = new self();
        
        $subject = "HospiLink - Gmail SMTP Configuration Test";
        
        $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #00adb5; text-align: center;">✓ Gmail SMTP Configuration Successful!</h2>
        <p style="color: #666; font-size: 16px; line-height: 1.6;">
            If you're seeing this email, your HospiLink Gmail SMTP email service is working correctly.
        </p>
        <div style="background-color: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="color: #666; margin: 0;">
                <strong>Configuration Details:</strong><br>
                SMTP Host: smtp.gmail.com<br>
                Port: 587 (TLS)<br>
                Username: asrajput5656@gmail.com
            </p>
        </div>
        <p style="color: #666;">You can now send appointment confirmations and notifications to your patients automatically.</p>
        <hr style="border: none; border-top: 1px solid #e0e0e0;">
        <p style="color: #999; font-size: 12px; text-align: center;">HospiLink Hospital Management System</p>
    </div>
</body>
</html>
HTML;
        
        return $emailService->sendEmailViaSMTP($toEmail, $subject, $message);
    }
    
    /**
     * Send appointment cancellation email
     */
    public static function sendCancellationEmail($appointmentData) {
        if (!EMAIL_ENABLED) {
            return true;
        }
        
        $emailService = new self();
        $to = $appointmentData['email'];
        $subject = "Appointment Cancellation Notice - HospiLink (ID: #" . $appointmentData['appointment_id'] . ")";
        
        $message = self::createCancellationEmailTemplate($appointmentData);
        
        return $emailService->sendEmailViaSMTP($to, $subject, $message);
    }
    
    /**
     * Create cancellation email template
     */
    private static function createCancellationEmailTemplate($data) {
        $appointment_id = $data['appointment_id'];
        $name = $data['full_name'];
        $date = date('l, F j, Y', strtotime($data['appointment_date']));
        $time = date('h:i A', strtotime($data['appointment_time']));
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Cancellation</title>
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
                    
                    <!-- Cancellation Notice -->
                    <tr>
                        <td style="padding: 30px; text-align: center;">
                            <div style="display: inline-block; background-color: #dc3545; color: white; padding: 10px 30px; border-radius: 50px; font-size: 16px; font-weight: bold;">
                                ✗ APPOINTMENT CANCELLED
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Message -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <h2 style="color: #333; margin-bottom: 20px;">Hello, {$name}!</h2>
                            <p style="color: #666; font-size: 16px; line-height: 1.6;">
                                Your appointment has been cancelled.
                            </p>
                            
                            <!-- Details -->
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
                                    <td style="padding: 15px;">
                                        <strong style="color: #0e545f;">🕐 Time:</strong><br>
                                        <span style="color: #333;">{$time}</span>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Rescheduling Info -->
                            <div style="margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-radius: 8px;">
                                <h3 style="color: #0e545f; margin-top: 0;">📅 Reschedule Your Appointment</h3>
                                <p style="color: #666; line-height: 1.6;">
                                    You can easily book a new appointment by visiting our website or calling us directly.
                                </p>
                            </div>
                            
                            <!-- Contact -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="http://localhost/HospiLink-DE/appointment.html" style="display: inline-block; background-color: #00adb5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                                    Book New Appointment
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
}
?>
