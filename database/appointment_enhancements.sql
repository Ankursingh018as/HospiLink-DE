-- ================================================================
-- HospiLink Appointment System Enhancements
-- Date: December 17, 2025
-- Purpose: Add QR token for appointments and link appointments to admissions
-- ================================================================

USE hospilink;

-- Add qr_token column to appointments table for check-in QR codes
ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS qr_token VARCHAR(255) NULL 
COMMENT 'QR code token for quick check-in at reception';

-- Add index on qr_token for fast lookups
CREATE INDEX IF NOT EXISTS idx_qr_token ON appointments(qr_token);

-- Add appointment_id column to patient_admissions to link admissions to appointments
ALTER TABLE patient_admissions 
ADD COLUMN IF NOT EXISTS appointment_id INT NULL
COMMENT 'Links admission to the originating appointment';

-- Add foreign key constraint
ALTER TABLE patient_admissions
ADD CONSTRAINT IF NOT EXISTS fk_admission_appointment
FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Add index on appointment_id for efficient joins
CREATE INDEX IF NOT EXISTS idx_appointment_id ON patient_admissions(appointment_id);

-- Add admitted_at timestamp to track when appointment was converted to admission
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS admitted_at TIMESTAMP NULL
COMMENT 'Timestamp when appointment was converted to admission';

-- Update status enum to include 'admitted' status
ALTER TABLE appointments 
MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'admitted') DEFAULT 'pending';

-- Add check-in tracking
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS checked_in_at TIMESTAMP NULL
COMMENT 'Timestamp when patient checked in using QR code';

-- ================================================================
-- Data Quality: Update existing appointments without QR tokens
-- ================================================================

-- This query can be run to generate QR tokens for existing appointments
-- Uncomment and run if needed:
-- UPDATE appointments 
-- SET qr_token = CONCAT('APT-', UNIX_TIMESTAMP(), '-', FLOOR(RAND() * 10000), '-', MD5(CONCAT(appointment_id, email, RAND())))
-- WHERE qr_token IS NULL OR qr_token = '';

-- ================================================================
-- Verification Queries
-- ================================================================

-- Verify columns were added
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_KEY,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'hospilink' 
AND TABLE_NAME = 'appointments'
AND COLUMN_NAME IN ('qr_token', 'admitted_at', 'checked_in_at');

SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_KEY,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'hospilink' 
AND TABLE_NAME = 'patient_admissions'
AND COLUMN_NAME = 'appointment_id';

-- Verify foreign key constraint
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'hospilink'
AND CONSTRAINT_NAME = 'fk_admission_appointment';

-- ================================================================
-- Sample Queries for Testing
-- ================================================================

-- Find appointments with QR tokens
-- SELECT appointment_id, full_name, qr_token, appointment_date, appointment_time
-- FROM appointments
-- WHERE qr_token IS NOT NULL
-- ORDER BY created_at DESC
-- LIMIT 10;

-- Find admissions linked to appointments
-- SELECT 
--     pa.admission_id,
--     pa.patient_id,
--     a.appointment_id,
--     a.full_name,
--     a.appointment_date,
--     pa.admission_date
-- FROM patient_admissions pa
-- JOIN appointments a ON pa.appointment_id = a.appointment_id
-- WHERE pa.appointment_id IS NOT NULL;

-- Find appointments that have been admitted
-- SELECT 
--     a.appointment_id,
--     a.full_name,
--     a.appointment_date,
--     a.status,
--     a.admitted_at,
--     pa.admission_id
-- FROM appointments a
-- LEFT JOIN patient_admissions pa ON a.appointment_id = pa.appointment_id
-- WHERE a.status = 'admitted';

COMMIT;
