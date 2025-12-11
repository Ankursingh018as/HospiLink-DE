<?php
// Email Configuration for HospiLink
// This file contains email settings for sending appointment confirmations

// Email settings - Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');                      // Gmail SMTP server
define('SMTP_PORT', 587);                                    // TLS port (use 465 for SSL)
define('SMTP_SECURITY', 'tls');                              // TLS or ssl
define('SMTP_USERNAME', 'asrajput5656@gmail.com');           // Your Gmail address
define('SMTP_PASSWORD', 'ueyd siaj lkfv iykk');              // Gmail App Password
define('SMTP_FROM_EMAIL', 'asrajput5656@gmail.com');         // Sender email
define('SMTP_FROM_NAME', 'HospiLink Hospital');              // Sender name

// Enable/disable email notifications
define('EMAIL_ENABLED', true); // Set to true to enable emails

/**
 * IMPORTANT SETUP INSTRUCTIONS:
 * 
 * For Gmail:
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable 2-Step Verification
 * 3. Go to https://myaccount.google.com/apppasswords
 * 4. Generate an "App Password" for "Mail"
 * 5. Use that 16-character password in SMTP_PASSWORD above
 * 
 * For Other Email Providers:
 * - Outlook/Hotmail: smtp.office365.com, port 587
 * - Yahoo: smtp.mail.yahoo.com, port 587
 * - Custom SMTP: Contact your hosting provider
 */
?>
