-- Staff Dashboard Database Schema Updates
-- Run this script to add/update tables for the hospital staff dashboard

-- Update users table to support staff role
-- Add staff-specific columns if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS department VARCHAR(100),
ADD COLUMN IF NOT EXISTS staff_id VARCHAR(50),
ADD COLUMN IF NOT EXISTS specialization VARCHAR(100),
ADD COLUMN IF NOT EXISTS license_number VARCHAR(50);

-- Create admitted_patients table if it doesn't exist
CREATE TABLE IF NOT EXISTS admitted_patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    blood_group VARCHAR(10),
    disease VARCHAR(255) NOT NULL,
    address TEXT,
    bed_id INT NULL,
    admission_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    discharge_date DATETIME NULL,
    status ENUM('stable', 'moderate', 'critical') DEFAULT 'stable',
    priority ENUM('stable', 'moderate', 'critical') DEFAULT 'stable',
    assigned_staff_id INT NULL,
    assignment_notes TEXT,
    discharge_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bed_id) REFERENCES beds(bed_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_staff_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Create beds table if it doesn't exist
CREATE TABLE IF NOT EXISTS beds (
    bed_id INT PRIMARY KEY AUTO_INCREMENT,
    ward_name VARCHAR(100) NOT NULL,
    bed_number VARCHAR(20) NOT NULL,
    bed_type ENUM('General', 'ICU', 'Private', 'Semi-Private') DEFAULT 'General',
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bed (ward_name, bed_number)
);

-- Insert sample beds if table is empty
INSERT INTO beds (ward_name, bed_number, bed_type, status)
SELECT * FROM (
    SELECT 'General Ward' as ward_name, 'G-101' as bed_number, 'General' as bed_type, 'available' as status UNION ALL
    SELECT 'General Ward', 'G-102', 'General', 'available' UNION ALL
    SELECT 'General Ward', 'G-103', 'General', 'available' UNION ALL
    SELECT 'General Ward', 'G-104', 'General', 'available' UNION ALL
    SELECT 'General Ward', 'G-105', 'General', 'available' UNION ALL
    SELECT 'ICU', 'ICU-01', 'ICU', 'available' UNION ALL
    SELECT 'ICU', 'ICU-02', 'ICU', 'available' UNION ALL
    SELECT 'ICU', 'ICU-03', 'ICU', 'available' UNION ALL
    SELECT 'Private Ward', 'P-201', 'Private', 'available' UNION ALL
    SELECT 'Private Ward', 'P-202', 'Private', 'available' UNION ALL
    SELECT 'Private Ward', 'P-203', 'Private', 'available' UNION ALL
    SELECT 'Semi-Private Ward', 'SP-301', 'Semi-Private', 'available' UNION ALL
    SELECT 'Semi-Private Ward', 'SP-302', 'Semi-Private', 'available' UNION ALL
    SELECT 'Emergency', 'E-401', 'General', 'available' UNION ALL
    SELECT 'Emergency', 'E-402', 'General', 'available'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM beds LIMIT 1);

-- Insert a sample staff user (password: staff123)
INSERT INTO users (name, first_name, last_name, email, password, user_role, department, staff_id, phone)
SELECT * FROM (
    SELECT 
        'John Staff' as name, 
        'John' as first_name, 
        'Staff' as last_name, 
        'staff@hospilink.com' as email, 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
        'staff' as user_role,
        'General Ward' as department,
        'STF-001' as staff_id,
        '555-1234' as phone
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'staff@hospilink.com');

-- Create index for better query performance
CREATE INDEX IF NOT EXISTS idx_admitted_patients_discharge ON admitted_patients(discharge_date);
CREATE INDEX IF NOT EXISTS idx_admitted_patients_bed ON admitted_patients(bed_id);
CREATE INDEX IF NOT EXISTS idx_admitted_patients_status ON admitted_patients(status);
CREATE INDEX IF NOT EXISTS idx_beds_status ON beds(status);

-- Insert sample admitted patients for testing
INSERT INTO admitted_patients (patient_name, phone, email, blood_group, disease, address, status, priority)
SELECT * FROM (
    SELECT 'Sarah Johnson' as patient_name, '555-0101' as phone, 'sarah.j@email.com' as email, 'A+' as blood_group, 'Pneumonia' as disease, '123 Main St' as address, 'moderate' as status, 'moderate' as priority UNION ALL
    SELECT 'Michael Chen', '555-0102', 'michael.c@email.com', 'O-', 'Appendicitis', '456 Oak Ave', 'critical', 'critical' UNION ALL
    SELECT 'Emily Williams', '555-0103', 'emily.w@email.com', 'B+', 'Fractured Leg', '789 Pine Rd', 'stable', 'stable' UNION ALL
    SELECT 'David Brown', '555-0104', 'david.b@email.com', 'AB+', 'Heart Attack', '321 Elm St', 'critical', 'critical' UNION ALL
    SELECT 'Lisa Anderson', '555-0105', 'lisa.a@email.com', 'A-', 'Diabetes Complications', '654 Maple Dr', 'moderate', 'moderate'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM admitted_patients LIMIT 1);

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON hospilink.admitted_patients TO 'hospilink_user'@'localhost';
-- GRANT SELECT, UPDATE ON hospilink.beds TO 'hospilink_user'@'localhost';

COMMIT;
