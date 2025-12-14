-- Create Test Staff User
-- Run this in phpMyAdmin to create a test staff account

USE hospilink;

-- First, update the role ENUM to include 'staff' if not already present
ALTER TABLE users 
MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'staff') NOT NULL DEFAULT 'patient';

-- Add staff_id column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS staff_id VARCHAR(50);

-- Insert test staff user
-- Email: teststaff@hospilink.com
-- Password: staff123
INSERT INTO users (first_name, last_name, email, password, role, phone, department, staff_id, status) 
VALUES (
    'Test',
    'Staff',
    'teststaff@hospilink.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'staff',
    '555-9999',
    'General Ward',
    'STF-TEST-001',
    'active'
);

-- Verify the user was created
SELECT user_id, first_name, last_name, email, role, department, staff_id 
FROM users 
WHERE email = 'teststaff@hospilink.com';
