# Environment Configuration Setup

## Overview
HospiLink now uses environment variables to store sensitive credentials and configuration settings. This keeps your secrets secure and separate from your codebase.

## Quick Setup

### 1. Create your .env file
```bash
cp .env.example .env
```

### 2. Edit .env with your credentials
Open `.env` and fill in your actual values:

```env
# Database Configuration
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=your_actual_database_password
DB_NAME=hospilink

# Email Configuration (Gmail SMTP)
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_gmail_app_password
SMTP_FROM_EMAIL=your_email@gmail.com

# Google Gemini AI API
GEMINI_API_KEY=your_actual_gemini_api_key
```

### 3. Verify .env is gitignored
```bash
git status .env
```
If the file is being ignored correctly, you'll see no output.

## Getting Your Credentials

### Gmail App Password
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable **2-Step Verification**
3. Go to [App Passwords](https://myaccount.google.com/apppasswords)
4. Generate an "App Password" for "Mail"
5. Copy the 16-character password to `SMTP_PASSWORD`

### Google Gemini API Key
1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Click "Create API Key"
3. Copy the key to `GEMINI_API_KEY`

## File Structure

```
HospiLink-DE/
├── .env                 # Your actual credentials (NEVER commit!)
├── .env.example         # Template file (safe to commit)
├── .gitignore          # Ensures .env is ignored
└── php/
    ├── env_loader.php  # Loads environment variables
    ├── db.php          # Uses env() function
    ├── email_config.php # Uses env() function
    └── ai_prioritizer.php # Uses env() function
```

## Security Best Practices

✅ **DO:**
- Keep `.env` file locally only
- Use `.env.example` as a template for others
- Use strong, unique passwords
- Rotate API keys periodically

❌ **DON'T:**
- Commit `.env` to Git
- Share `.env` file publicly
- Use production credentials in development
- Hardcode credentials in PHP files

## Troubleshooting

### "Cannot connect to database"
- Check `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` in `.env`
- Ensure XAMPP MySQL is running

### "Email not sending"
- Verify `SMTP_USERNAME` and `SMTP_PASSWORD`
- Ensure you're using Gmail App Password, not regular password
- Check `EMAIL_ENABLED=true` in `.env`

### "AI prioritizer not working"
- Verify `GEMINI_API_KEY` is correct
- Check API quota at [Google Cloud Console](https://console.cloud.google.com/)

## Migration Notes

The following files have been updated to use environment variables:
- `php/db.php` - Database credentials
- `php/email_config.php` - Email SMTP settings
- `php/ai_prioritizer.php` - Gemini API key

All hardcoded credentials have been removed from these files for security.

## For Developers

### Using Environment Variables in Code
```php
<?php
require_once 'php/env_loader.php';

// Get environment variable with default value
$apiKey = env('GEMINI_API_KEY', 'default_value');

// Check if variable exists
if (env('EMAIL_ENABLED') === 'true') {
    // Send email
}
```

### Adding New Environment Variables
1. Add to `.env` file: `NEW_VAR=value`
2. Add to `.env.example` file: `NEW_VAR=your_value_here`
3. Use in code: `env('NEW_VAR')`

---

**Last Updated:** December 14, 2025
**Version:** 1.0.0
