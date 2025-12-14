-- Quick Fix: Create Working Staff Account
-- Run this in phpMyAdmin SQL tab

USE hospilink;

-- First ensure the role enum includes 'staff' and 'nurse'
ALTER TABLE users 
MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'staff', 'nurse') NOT NULL DEFAULT 'patient';

-- Add staff_id column if missing
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS staff_id VARCHAR(50);

-- Delete existing test accounts if any
DELETE FROM users WHERE email IN ('staff@hospital.com', 'teststaff@hospilink.com', 'nurse.patel@hospilink.com');

-- Create fresh staff account with working password
-- Email: staff@hospital.com
-- Password: 12345
INSERT INTO users (first_name, last_name, email, password, role, phone, department, staff_id, status) 
VALUES (
    'Hospital',
    'Staff',
    'staff@hospital.com',
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    'staff',
    '9876543210',
    'General Ward',
    'STF-001',
    'active'
);

-- Verify account created
SELECT user_id, first_name, last_name, email, role, department, staff_id, status 
FROM users 
WHERE email = 'staff@hospital.com';
