-- Migration script to update priority levels from 4 levels to 3 levels
-- Run this script to update your existing database

USE hospilink;

-- Step 1: Update existing 'critical' priority to 'high' priority in appointments table
UPDATE appointments 
SET priority_level = 'high' 
WHERE priority_level = 'critical';

-- Step 2: Update existing 'critical' priority to 'high' priority in symptom_keywords table
UPDATE symptom_keywords 
SET priority_level = 'high' 
WHERE priority_level = 'critical';

-- Step 3: Alter appointments table to use only three priority levels
ALTER TABLE appointments 
MODIFY COLUMN priority_level ENUM('high', 'medium', 'low') NOT NULL;

-- Step 4: Alter symptom_keywords table to use only three priority levels
ALTER TABLE symptom_keywords 
MODIFY COLUMN priority_level ENUM('high', 'medium', 'low') NOT NULL;

-- Verification queries (run these to check the migration)
SELECT 'Appointments by priority' as info_type;
SELECT priority_level, COUNT(*) as count 
FROM appointments 
GROUP BY priority_level;

SELECT 'Symptom keywords by priority' as info_type;
SELECT priority_level, COUNT(*) as count 
FROM symptom_keywords 
GROUP BY priority_level;

-- Success message
SELECT 'Migration completed successfully! All critical priority records have been converted to high priority.' as status;
