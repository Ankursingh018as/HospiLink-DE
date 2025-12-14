-- Allow public/bedside QR code access by making scanned_by nullable
-- This allows patients, family members, or anyone with QR code to view patient status

USE hospilink;

-- Drop the foreign key constraint
ALTER TABLE qr_scan_logs DROP FOREIGN KEY qr_scan_logs_ibfk_2;

-- Modify scanned_by to allow NULL
ALTER TABLE qr_scan_logs MODIFY COLUMN scanned_by INT NULL;

-- Add back the foreign key with ON DELETE SET NULL
ALTER TABLE qr_scan_logs 
ADD CONSTRAINT qr_scan_logs_ibfk_2 
FOREIGN KEY (scanned_by) REFERENCES users(user_id) ON DELETE SET NULL;
