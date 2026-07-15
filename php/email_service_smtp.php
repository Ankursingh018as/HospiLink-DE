<?php
/**
 * Gmail SMTP Email Service for HospiLink
 * Sends emails directly via Gmail SMTP server
 * 
 * This service uses direct socket connection to Gmail SMTP
 * No external libraries required!
 */

require_once 'email_config.php';
require_once 'calendar_helper.php';

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
        
        // Generate calendar invite (.ics file)
        $icsContent = CalendarHelper::generateICS($appointmentData);
        $icsFilename = "appointment-" . $appointmentData['appointment_id'] . ".ics";
        
        // Send via SMTP with calendar attachment
        return $emailService->sendEmailViaSMTP($to, $subject, $message, $icsContent, $icsFilename);
    }
    
    /**
     * Send email via Gmail SMTP
     */
    private function sendEmailViaSMTP($to, $subject, $body, $icsContent = null, $icsFilename = null) {
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
            if ($icsContent && $icsFilename) {
                // Send multipart email with calendar attachment
                $emailContent = $this->buildMultipartEmail($to, $subject, $body, $icsContent, $icsFilename);
                fwrite($this->connection, $emailContent);
            } else {
                // Send simple HTML email
                $emailHeaders = $this->buildEmailHeaders($to, $subject);
                fwrite($this->connection, $emailHeaders . "\r\n\r\n");
                fwrite($this->connection, $body . "\r\n");
            }
            
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
     * Build multipart email with calendar attachment
     */
    private function buildMultipartEmail($to, $subject, $htmlBody, $icsContent, $icsFilename) {
        $boundary = md5(time());
        
        // Headers
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "To: " . $to . "\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";
        $headers .= "X-Mailer: HospiLink SMTP Service\r\n";
        $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        
        // Start email body
        $email = $headers . "\r\n";
        $email .= "This is a multi-part message in MIME format.\r\n\r\n";
        
        // HTML part
        $email .= "--" . $boundary . "\r\n";
        $email .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $email .= $htmlBody . "\r\n\r\n";
        
        // Calendar attachment (.ics file)
        $email .= "--" . $boundary . "\r\n";
        $email .= "Content-Type: text/calendar; charset=UTF-8; method=REQUEST; name=\"" . $icsFilename . "\"\r\n";
        $email .= "Content-Transfer-Encoding: base64\r\n";
        $email .= "Content-Disposition: attachment; filename=\"" . $icsFilename . "\"\r\n\r\n";
        $email .= chunk_split(base64_encode($icsContent)) . "\r\n";
        
        // End boundary
        $email .= "--" . $boundary . "--\r\n";
        
        return $email;
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
        
        // Get AI analysis data if available
        $aiAnalyzed = isset($data['ai_analysis']) && isset($data['ai_analysis']['ai_analyzed']) ? $data['ai_analysis']['ai_analyzed'] : false;
        $suspectedConditions = isset($data['suspected_conditions']) ? $data['suspected_conditions'] : '';
        $recommendedSpecialist = isset($data['recommended_specialist']) ? $data['recommended_specialist'] : '';
        $urgencyReason = isset($data['ai_analysis']) ? $data['ai_analysis']['urgency_reason'] : '';
        
        // Priority badge colors
        $priorityColors = [
            'HIGH' => '#ef4444',
            'MEDIUM' => '#f59e0b',
            'LOW' => '#10b981'
        ];
        $priorityColor = $priorityColors[$priority] ?? '#64748b';
        
        // Priority messages
        $priorityMessages = [
            'HIGH' => 'URGENT: Your appointment has been marked as high priority. Please proceed to the emergency department immediately or call emergency services if symptoms worsen.',
            'MEDIUM' => 'Your appointment has been scheduled with medium priority. Expected wait time is 3-5 days.',
            'LOW' => 'Your appointment has been confirmed. Suitable for routine care and follow-ups.'
        ];
        $priorityMessage = $priorityMessages[$priority] ?? 'Your appointment has been scheduled.';
        
        // Build AI analysis section
        $aiAnalysisSection = '';
        if ($aiAnalyzed && !empty($urgencyReason)) {
            $aiAnalysisSection = <<<AIHTML
                            <!-- AI Analysis -->
                            <tr>
                                <td style="padding: 0 40px 24px 40px;">
                                    <div style="background-color: #eff6ff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                                        <h3 style="color: #0f172a; margin: 0 0 12px 0; font-size: 16px; font-weight: 600;">AI Medical Analysis</h3>
                                        <p style="color: #475569; margin: 8px 0; font-size: 14.5px;"><strong>Assessment:</strong> {$urgencyReason}</p>
AIHTML;
            
            if (!empty($suspectedConditions)) {
                $aiAnalysisSection .= <<<AIHTML
                                        <p style="color: #475569; margin: 8px 0; font-size: 14.5px;"><strong>Possible Conditions:</strong> {$suspectedConditions}</p>
AIHTML;
            }
            
            if (!empty($recommendedSpecialist)) {
                $aiAnalysisSection .= <<<AIHTML
                                        <p style="color: #475569; margin: 8px 0; font-size: 14.5px;"><strong>Recommended Specialist:</strong> {$recommendedSpecialist}</p>
AIHTML;
            }
            
            $aiAnalysisSection .= <<<AIHTML
                                    </div>
                                </td>
                            </tr>
AIHTML;
        }
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; color: #334155;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="580" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 40px 20px 40px; border-bottom: 1px solid #f1f5f9;">
                            <div style="font-size: 18px; font-weight: 700; color: #0d9488; letter-spacing: -0.5px;">HospiLink</div>
                            <h1 style="color: #0f172a; margin: 12px 0 0 0; font-size: 20px; font-weight: 600; letter-spacing: -0.5px;">Appointment Confirmation</h1>
                        </td>
                    </tr>
                    
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 40px 20px 40px;">
                            <h2 style="color: #0f172a; margin-top: 0; font-size: 18px; font-weight: 600;">Hello {$name},</h2>
                            <p style="color: #475569; font-size: 15px; line-height: 1.6;">
                                Your appointment has been successfully booked at HospiLink Hospital.
                            </p>
                            
                            <!-- Priority Alert -->
                            <div style="margin: 24px 0; padding: 15px; background-color: #f8fafc; border-left: 4px solid {$priorityColor}; border-radius: 4px; font-size: 14.5px;">
                                <p style="margin: 0; color: #1e293b;"><strong>Priority Level: {$priority}</strong></p>
                                <p style="margin: 6px 0 0 0; color: #475569;">{$priorityMessage}</p>
                            </div>
                            
                            <!-- Details Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14.5px; border-collapse: collapse; overflow: hidden;">
                                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; width: 40%;">Appointment ID</td>
                                    <td style="padding: 12px 16px; color: #0f172a; font-weight: bold;">#{$appointment_id}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Date</td>
                                    <td style="padding: 12px 16px; color: #0f172a;">{$date}</td>
                                </tr>
                                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Time</td>
                                    <td style="padding: 12px 16px; color: #0f172a;">{$time}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Doctor</td>
                                    <td style="padding: 12px 16px; color: #0f172a;">{$doctor}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Contact Email</td>
                                    <td style="padding: 12px 16px; color: #0f172a;">{$email}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- AI Medical Analysis Section -->
                    {$aiAnalysisSection}
                    
                    <!-- Symptoms & Important Info -->
                    <tr>
                        <td style="padding: 0 40px 32px 40px;">
                            <!-- Symptoms Summary -->
                            <div style="margin: 0 0 24px 0; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <h4 style="color: #0f172a; margin-top: 0; margin-bottom: 8px; font-size: 15px; font-weight: 600;">Symptoms Description</h4>
                                <p style="color: #475569; line-height: 1.6; margin: 0; font-size: 14.5px;">{$symptoms}</p>
                            </div>
                            
                            <!-- Important Information -->
                            <div style="margin: 0 0 24px 0; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <h3 style="color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 15px; font-weight: 600;">Important Information</h3>
                                <ul style="color: #475569; line-height: 1.8; padding-left: 20px; margin: 0; font-size: 14px;">
                                    <li>Please arrive 15 minutes before your appointment time</li>
                                    <li>Bring a valid ID and any previous medical records</li>
                                    <li>If you need to reschedule, please contact us at least 24 hours in advance</li>
                                    <li>Keep your Appointment ID (#<strong>{$appointment_id}</strong>) handy for reference</li>
                                </ul>
                            </div>
                            
                            <!-- Contact Details -->
                            <div style="margin: 0 0 24px 0; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; font-size: 14.5px;">
                                <h4 style="color: #0f172a; margin-top: 0; margin-bottom: 8px; font-weight: 600;">Need to reschedule or cancel?</h4>
                                <p style="color: #475569; margin: 0; line-height: 1.5;">
                                    Contact us at: <strong>+91-9856594589</strong><br>
                                    Email: <strong>hospilink@gmail.com</strong>
                                </p>
                            </div>
                            
                            <div style="text-align: center;">
                                <a href="http://localhost/HospiLink-DE/contact.html" style="display: inline-block; background-color: #0d9488; color: white !important; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px;">
                                    Contact Us
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin: 0;">
                                © 2026 HospiLink. Automated notification. Please do not reply.<br>
                                Dahod, Gujarat, India | hospilink@gmail.com
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
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 32px 0; background-color: #f8fafc; color: #334155;">
    <div style="background: white; padding: 40px; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 580px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 18px; font-weight: 700; color: #0d9488; letter-spacing: -0.5px; margin-bottom: 20px;">HospiLink</div>
        <h2 style="color: #0f172a; font-size: 20px; font-weight: 600; margin-top: 0;">Gmail SMTP Configuration Successful</h2>
        <p style="color: #475569; font-size: 15px; line-height: 1.6;">
            If you are seeing this email, your HospiLink Gmail SMTP email service has been configured and is working correctly.
        </p>
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 24px 0; font-size: 14.5px;">
            <p style="color: #475569; margin: 0; line-height: 1.6;">
                <strong>Configuration Details:</strong><br>
                SMTP Host: smtp.gmail.com<br>
                Port: 587 (TLS)<br>
                Username: asrajput5656@gmail.com
            </p>
        </div>
        <p style="color: #475569; font-size: 15px; line-height: 1.6;">You can now send appointment confirmations and notifications to your patients automatically.</p>
        <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 24px 0;">
        <p style="color: #64748b; font-size: 12px; text-align: center; margin: 0;">© 2026 HospiLink Hospital Management System</p>
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
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; color: #334155;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="580" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 40px 20px 40px; border-bottom: 1px solid #f1f5f9;">
                            <div style="font-size: 18px; font-weight: 700; color: #0d9488; letter-spacing: -0.5px;">HospiLink</div>
                            <h1 style="color: #ef4444; margin: 12px 0 0 0; font-size: 20px; font-weight: 600; letter-spacing: -0.5px;">Appointment Cancelled</h1>
                        </td>
                    </tr>
                    
                    <!-- Message -->
                    <tr>
                        <td style="padding: 32px 40px 32px 40px;">
                            <h2 style="color: #0f172a; margin-top: 0; font-size: 18px; font-weight: 600;">Hello {$name},</h2>
                            <p style="color: #475569; font-size: 15px; line-height: 1.6;">
                                Your appointment has been cancelled.
                            </p>
                            
                            <!-- Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14.5px; border-collapse: collapse; overflow: hidden;">
                                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; width: 40%;">Appointment ID</td>
                                    <td style="padding: 12px 16px; color: #0f172a; font-weight: bold;">#{$appointment_id}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Date</td>
                                    <td style="padding: 12px 16px; color: #0f172a;">{$date}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Time</td>
                                    <td style="padding: 12px 16px; color: #0f172a;">{$time}</td>
                                </tr>
                            </table>
                            
                            <!-- Rescheduling Info -->
                            <div style="margin: 24px 0; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14.5px;">
                                <h3 style="color: #0f172a; margin-top: 0; margin-bottom: 8px; font-size: 15px; font-weight: 600;">Reschedule Your Appointment</h3>
                                <p style="color: #475569; line-height: 1.6; margin: 0;">
                                    You can easily book a new appointment by visiting our website or calling us directly.
                                </p>
                            </div>
                            
                            <!-- Contact -->
                            <div style="text-align: center;">
                                <a href="http://localhost/HospiLink-DE/appointment.html" style="display: inline-block; background-color: #0d9488; color: white !important; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px;">
                                    Book New Appointment
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin: 0;">
                                © 2026 HospiLink. Automated notification. Please do not reply.<br>
                                Dahod, Gujarat, India | hospilink@gmail.com
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
     * Send OTP verification email
     */
    public static function sendOTPEmail($to, $subject, $htmlBody) {
        if (!EMAIL_ENABLED) {
            return true; // Email disabled, return success
        }
        
        $emailService = new self();
        return $emailService->sendEmailViaSMTP($to, $subject, $htmlBody);
    }
    
    /**
     * Generic send email method
     */
    public static function sendEmail($to, $toName, $subject, $htmlBody) {
        if (!EMAIL_ENABLED) {
            return ['success' => true, 'message' => 'Email service disabled'];
        }
        
        $emailService = new self();
        $result = $emailService->sendEmailViaSMTP($to, $subject, $htmlBody);
        
        if ($result) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email'];
        }
    }
}
?>
