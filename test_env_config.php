<?php
/**
 * Environment Configuration Test
 * Tests if .env file is loaded correctly and all variables are accessible
 */

require_once 'php/env_loader.php';

echo "=================================================\n";
echo "HospiLink Environment Configuration Test\n";
echo "=================================================\n\n";

// Test if .env file exists
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    echo "❌ ERROR: .env file not found!\n";
    echo "   Please copy .env.example to .env and configure it.\n";
    exit(1);
}

echo "✅ .env file found\n\n";

// Test database configuration
echo "DATABASE CONFIGURATION:\n";
echo "----------------------\n";
echo "Host: " . (env('DB_HOST') ?: '❌ NOT SET') . "\n";
echo "Username: " . (env('DB_USERNAME') ?: '❌ NOT SET') . "\n";
echo "Password: " . (env('DB_PASSWORD') ? '✅ SET (hidden)' : '❌ NOT SET') . "\n";
echo "Database: " . (env('DB_NAME') ?: '❌ NOT SET') . "\n\n";

// Test database connection
try {
    require_once 'php/db.php';
    echo "✅ Database connection successful!\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
}

// Test email configuration
echo "EMAIL CONFIGURATION:\n";
echo "--------------------\n";
echo "SMTP Host: " . (env('SMTP_HOST') ?: '❌ NOT SET') . "\n";
echo "SMTP Port: " . (env('SMTP_PORT') ?: '❌ NOT SET') . "\n";
echo "SMTP Security: " . (env('SMTP_SECURITY') ?: '❌ NOT SET') . "\n";
echo "SMTP Username: " . (env('SMTP_USERNAME') ?: '❌ NOT SET') . "\n";
echo "SMTP Password: " . (env('SMTP_PASSWORD') ? '✅ SET (hidden)' : '❌ NOT SET') . "\n";
echo "From Email: " . (env('SMTP_FROM_EMAIL') ?: '❌ NOT SET') . "\n";
echo "From Name: " . (env('SMTP_FROM_NAME') ?: '❌ NOT SET') . "\n";
echo "Email Enabled: " . (env('EMAIL_ENABLED') ?: '❌ NOT SET') . "\n\n";

// Test AI configuration
echo "AI CONFIGURATION:\n";
echo "-----------------\n";
echo "Gemini API Key: " . (env('GEMINI_API_KEY') ? '✅ SET (hidden)' : '❌ NOT SET') . "\n";
echo "API Endpoint: " . (env('GEMINI_API_ENDPOINT') ? '✅ SET' : '❌ NOT SET') . "\n\n";

// Test timezone configuration
echo "TIMEZONE CONFIGURATION:\n";
echo "-----------------------\n";
echo "Timezone: " . (env('TIMEZONE') ?: '❌ NOT SET') . "\n";
echo "Timezone Offset: " . (env('TIMEZONE_OFFSET') ?: '❌ NOT SET') . "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n\n";

// Security check
echo "SECURITY CHECK:\n";
echo "---------------\n";
$gitignoreContent = file_get_contents(__DIR__ . '/.gitignore');
if (strpos($gitignoreContent, '.env') !== false) {
    echo "✅ .env is in .gitignore\n";
} else {
    echo "❌ WARNING: .env is NOT in .gitignore!\n";
    echo "   Add '.env' to .gitignore immediately!\n";
}

// Check if .env is tracked by git
exec('git ls-files .env 2>&1', $output, $returnCode);
if (empty($output)) {
    echo "✅ .env is not tracked by Git\n";
} else {
    echo "❌ WARNING: .env is tracked by Git!\n";
    echo "   Run: git rm --cached .env\n";
}

echo "\n=================================================\n";
echo "Configuration test complete!\n";
echo "=================================================\n";
?>
