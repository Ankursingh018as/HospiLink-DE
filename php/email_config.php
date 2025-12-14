<?php
// Email Configuration for HospiLink
// This file contains email settings for sending appointment confirmations

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Email settings - Gmail SMTP Configuration (from .env file)
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_SECURITY', env('SMTP_SECURITY', 'tls'));
define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', ''));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'HospiLink Hospital'));

// Enable/disable email notifications
define('EMAIL_ENABLED', filter_var(env('EMAIL_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN));

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
