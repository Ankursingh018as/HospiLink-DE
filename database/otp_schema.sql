-- OTP Verification System Schema for HospiLink
-- Add this to your existing hospilink database

USE hospilink;

-- OTP verification table
CREATE TABLE IF NOT EXISTS otp_verification (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    user_data TEXT NOT NULL, -- JSON encoded registration data
    attempts INT DEFAULT 0,
    resend_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    ip_address VARCHAR(45),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at),
    INDEX idx_verified (verified)
);

-- Clean up expired OTPs automatically (MySQL Event Scheduler)
-- Enable event scheduler if not already enabled: SET GLOBAL event_scheduler = ON;
CREATE EVENT IF NOT EXISTS cleanup_expired_otps
ON SCHEDULE EVERY 1 HOUR
DO
    DELETE FROM otp_verification 
    WHERE expires_at < NOW() OR (verified = TRUE AND verified_at < DATE_SUB(NOW(), INTERVAL 24 HOUR));
