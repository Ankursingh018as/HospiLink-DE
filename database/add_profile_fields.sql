-- Add additional profile fields to users table
-- Run this migration to add new columns for patient profile

USE hospilink;

-- Add age column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS age INT NULL AFTER phone;

-- Add gender column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other') NULL AFTER age;

-- Add address column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER gender;

-- Update statement to show migration completed
SELECT 'User profile columns added successfully' AS status;
