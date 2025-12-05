<?php
// Email Configuration for HospiLink
// This file contains email settings for sending appointment confirmations

// Email settings - Configure these based on your email service
define('SMTP_HOST', 'smtp.gmail.com');  // Gmail SMTP server
define('SMTP_PORT', 587);                // TLS port
define('SMTP_USERNAME', 'ankursingh@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'ulvq taxq hrvs rtcq');    // Gmail App Password
define('SMTP_FROM_EMAIL', 'hospilink@gmail.com'); // Sender email
define('SMTP_FROM_NAME', 'HospiLink Hospital');   // Sender name

// Enable/disable email notifications
define('EMAIL_ENABLED', false); // Set to false to disable emails during testing

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
