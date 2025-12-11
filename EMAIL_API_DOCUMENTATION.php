<?php
/**
 * EMAIL SERVICE API DOCUMENTATION
 * 
 * This file documents the EmailService class and its public methods
 * Copy this file content to a documentation generator or wiki
 */

/**
 * EmailService Class
 * 
 * Handles all email sending operations using Gmail SMTP
 * 
 * @package HospiLink
 * @author Development Team
 * @version 1.0
 * @since 2025-12-06
 */
class EmailService {
    
    /**
     * Send Appointment Confirmation Email
     * 
     * Sends a professional HTML email confirming an appointment booking.
     * Called automatically when an appointment is successfully created.
     * 
     * @static
     * @param array $appointmentData {
     *     @type int         $appointment_id    Unique appointment identifier
     *     @type string      $full_name         Patient's full name
     *     @type string      $email             Patient's email address
     *     @type string      $appointment_date  Appointment date (YYYY-MM-DD format)
     *     @type string      $appointment_time  Appointment time (HH:MM format)
     *     @type string      $symptoms          Patient's reported symptoms
     *     @type string      $priority_level    Appointment priority (critical/high/medium/low)
     *     @type int         $priority_score    AI-calculated priority score (0-100)
     *     @type string      $doctor_name       Assigned doctor name (optional)
     * }
     * @return bool True if email was sent successfully, false otherwise
     * 
     * @example
     * $appointmentData = [
     *     'appointment_id' => 123,
     *     'full_name' => 'John Doe',
     *     'email' => 'john@example.com',
     *     'appointment_date' => '2025-12-20',
     *     'appointment_time' => '14:30',
     *     'symptoms' => 'Fever and cough',
     *     'priority_level' => 'high',
     *     'priority_score' => 75,
     *     'doctor_name' => 'Dr. Smith - Cardiology'
     * ];
     * 
     * $success = EmailService::sendAppointmentConfirmation($appointmentData);
     * if ($success) {
     *     echo "Confirmation email sent successfully";
     * } else {
     *     echo "Failed to send confirmation email";
     * }
     */
    public static function sendAppointmentConfirmation($appointmentData) {
        // Implementation details
    }
    
    /**
     * Send Test Email
     * 
     * Sends a test email to verify Gmail SMTP configuration is working.
     * Used for troubleshooting and configuration validation.
     * 
     * @static
     * @param string $toEmail Recipient email address
     * @return bool True if email sent successfully, false otherwise
     * 
     * @example
     * $result = EmailService::sendTestEmail('admin@example.com');
     * if ($result) {
     *     echo "Test email sent. Check your inbox.";
     * } else {
     *     echo "Test email failed. Check configuration.";
     * }
     */
    public static function sendTestEmail($toEmail) {
        // Implementation details
    }
    
    /**
     * Send Appointment Cancellation Email
     * 
     * Sends notification email when an appointment is cancelled.
     * Includes option for patient to rebook.
     * 
     * @static
     * @param array $appointmentData {
     *     @type int         $appointment_id    Unique appointment identifier
     *     @type string      $full_name         Patient's full name
     *     @type string      $email             Patient's email address
     *     @type string      $appointment_date  Original appointment date (YYYY-MM-DD)
     *     @type string      $appointment_time  Original appointment time (HH:MM)
     *     @type string      $reason            Reason for cancellation (optional)
     * }
     * @return bool True if email sent successfully, false otherwise
     * 
     * @example
     * $cancelData = [
     *     'appointment_id' => 123,
     *     'full_name' => 'John Doe',
     *     'email' => 'john@example.com',
     *     'appointment_date' => '2025-12-20',
     *     'appointment_time' => '14:30',
     *     'reason' => 'Doctor unavailable'
     * ];
     * 
     * EmailService::sendCancellationEmail($cancelData);
     */
    public static function sendCancellationEmail($appointmentData) {
        // Implementation details
    }
}

/**
 * CONFIGURATION
 * 
 * Email configuration is stored in email_config.php
 * Key configuration constants:
 * 
 * - SMTP_HOST         : SMTP server address (smtp.gmail.com)
 * - SMTP_PORT         : SMTP port number (587 for TLS)
 * - SMTP_SECURITY     : Security protocol (tls or ssl)
 * - SMTP_USERNAME     : Gmail account username
 * - SMTP_PASSWORD     : Gmail app password (not regular password)
 * - SMTP_FROM_EMAIL   : Sender email address
 * - SMTP_FROM_NAME    : Sender display name
 * - EMAIL_ENABLED     : Enable/disable emails globally (true/false)
 * 
 * @example
 * define('SMTP_HOST', 'smtp.gmail.com');
 * define('SMTP_PORT', 587);
 * define('SMTP_SECURITY', 'tls');
 * define('SMTP_USERNAME', 'your-email@gmail.com');
 * define('SMTP_PASSWORD', 'your-16-char-app-password');
 * define('EMAIL_ENABLED', true);
 */

/**
 * ERROR HANDLING
 * 
 * The EmailService uses error_log() for error tracking:
 * - SMTP connection errors logged to PHP error log
 * - Failed authentications logged
 * - Detailed error messages for debugging
 * 
 * Check PHP error log location:
 * - Apache: /var/log/apache2/error.log
 * - Nginx: /var/log/nginx/error.log
 * - Windows: Event Viewer or php_errors.log in temp folder
 * 
 * @example
 * // Check errors programmatically
 * // Errors are logged with error_log() function
 * // View logs in your PHP error log file
 */

/**
 * INTEGRATION POINTS
 * 
 * The email service is integrated at:
 * 
 * 1. Appointment Booking (php/appointment.php)
 *    - Triggered after successful appointment creation
 *    - Sends confirmation email to patient
 * 
 * 2. Email Test Page (php/test_email.php)
 *    - Allows administrators to test configuration
 *    - Verifies Gmail SMTP connectivity
 * 
 * 3. Future Integrations (potential)
 *    - Appointment reminders (24 hours before)
 *    - Appointment cancellations
 *    - Doctor assignments
 *    - Follow-up appointments
 */

/**
 * SECURITY CONSIDERATIONS
 * 
 * 1. App Password Only
 *    - Use Gmail App Password, NOT your main password
 *    - Generate at myaccount.google.com/apppasswords
 * 
 * 2. Environment Variables (Production)
 *    - Use environment variables instead of hardcoded credentials
 *    - Example: $_ENV['GMAIL_APP_PASSWORD']
 * 
 * 3. HTTPS Connections
 *    - Use TLS (port 587) or SSL (port 465)
 *    - Encrypt all SMTP traffic
 * 
 * 4. Error Logging
 *    - Log errors but don't expose credentials in logs
 *    - Sanitize error messages before display to users
 * 
 * 5. Rate Limiting
 *    - Gmail has sending limits (~500 emails per day)
 *    - Implement queue system for high volume
 */

/**
 * TROUBLESHOOTING GUIDE
 * 
 * PROBLEM: "Connection failed"
 * SOLUTION:
 * - Verify Gmail credentials
 * - Check firewall allows port 587
 * - Ensure 2-Step Verification is enabled
 * - Test with: ping smtp.gmail.com
 * 
 * PROBLEM: "Authentication failed"
 * SOLUTION:
 * - Use App Password, not regular Gmail password
 * - Regenerate App Password from Gmail settings
 * - Verify username is correct Gmail address
 * 
 * PROBLEM: "Email not received"
 * SOLUTION:
 * - Check spam/junk folder
 * - Add sender to contacts
 * - Mark as "Not spam"
 * - Wait for Gmail to learn the pattern
 * 
 * PROBLEM: "Email goes to spam"
 * SOLUTION:
 * - Check SPF/DKIM/DMARC records (if using custom domain)
 * - Add authentication headers
 * - Test email formatting
 * - Check Gmail reputation
 */

/**
 * PERFORMANCE OPTIMIZATION
 * 
 * Current Implementation:
 * - Direct socket connection to Gmail SMTP
 * - No external libraries (lightweight)
 * - Synchronous sending (blocking)
 * 
 * Future Optimizations:
 * - Queue system for batch sending
 * - Asynchronous background processing
 * - Connection pooling for multiple emails
 * - Retry mechanism for failed emails
 * - Email templates caching
 */

/**
 * EMAIL TEMPLATES
 * 
 * Available Templates:
 * 
 * 1. Appointment Confirmation
 *    - Full appointment details
 *    - AI priority analysis
 *    - Important instructions
 *    - Professional branding
 * 
 * 2. Appointment Cancellation
 *    - Cancellation notice
 *    - Original appointment info
 *    - Rebooking options
 *    - Contact information
 * 
 * 3. Test Email
 *    - Configuration verification
 *    - System status check
 *    - Troubleshooting tips
 * 
 * Template Customization:
 * - Edit templates in email_service_smtp.php
 * - Look for createEmailTemplate() method
 * - Modify HTML, colors, text as needed
 */

/**
 * API RESPONSE CODES
 * 
 * Success:
 * - true  : Email sent successfully
 * 
 * Failure:
 * - false : Email sending failed (check error log)
 * 
 * Reasons for Failure:
 * - Invalid email address format
 * - SMTP connection error
 * - Authentication failure
 * - Email disabled in configuration
 * - Invalid appointment data
 */

/**
 * USAGE EXAMPLES
 * 
 * Example 1: Send Confirmation Email
 * ----
 * include 'email_service_smtp.php';
 * 
 * $data = [
 *     'appointment_id' => 456,
 *     'full_name' => 'Jane Smith',
 *     'email' => 'jane@example.com',
 *     'appointment_date' => '2025-12-25',
 *     'appointment_time' => '10:00',
 *     'symptoms' => 'Headache and dizziness',
 *     'priority_level' => 'medium',
 *     'priority_score' => 65,
 *     'doctor_name' => 'Dr. Johnson - Neurology'
 * ];
 * 
 * $sent = EmailService::sendAppointmentConfirmation($data);
 * ----
 * 
 * Example 2: Send Test Email (Admin)
 * ----
 * include 'email_service_smtp.php';
 * 
 * $testResult = EmailService::sendTestEmail('admin@hospital.com');
 * if ($testResult) {
 *     echo "Configuration OK";
 * }
 * ----
 * 
 * Example 3: Send Cancellation Email
 * ----
 * include 'email_service_smtp.php';
 * 
 * $cancelData = [
 *     'appointment_id' => 456,
 *     'full_name' => 'Jane Smith',
 *     'email' => 'jane@example.com',
 *     'appointment_date' => '2025-12-25',
 *     'appointment_time' => '10:00'
 * ];
 * 
 * EmailService::sendCancellationEmail($cancelData);
 * ----
 */

/**
 * DEPLOYMENT CHECKLIST
 * 
 * Before Going Live:
 * [ ] Test Gmail SMTP configuration
 * [ ] Verify email templates look correct
 * [ ] Test with real appointment booking
 * [ ] Verify emails in multiple clients (Gmail, Outlook, etc.)
 * [ ] Check spam folder filtering
 * [ ] Enable EMAIL_ENABLED in production
 * [ ] Set up error logging and monitoring
 * [ ] Document configuration for team
 * [ ] Create backup/recovery procedures
 * [ ] Monitor email delivery metrics
 * [ ] Set up alerts for failures
 * 
 * @return void This is documentation only
 */

// ============================================================================
// END OF API DOCUMENTATION
// ============================================================================
// 
// This documentation file serves as reference for developers integrating
// the email system into HospiLink or extending its functionality.
//
// For quick setup, see: EMAIL_QUICK_SETUP.md
// For detailed guide, see: EMAIL_NOTIFICATION_GUIDE.md
// For implementation details, see: EMAIL_IMPLEMENTATION_SUMMARY.md
//
// Questions? Review the documentation or check email_service_smtp.php source.
// ============================================================================
?>
