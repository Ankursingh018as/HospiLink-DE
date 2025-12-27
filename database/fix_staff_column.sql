-- Fix staff_id column for staff registration
USE hospilink;

-- Add staff_id column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS staff_id VARCHAR(50) AFTER license_number;

-- Verify column was added
SHOW COLUMNS FROM users LIKE 'staff_id';

SELECT 'staff_id column added successfully!' as Status;
