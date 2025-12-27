-- =====================================================
-- HospiLink Users Table Migration
-- Complete user management with all roles
-- =====================================================

USE hospilink;

-- Drop existing users table if exists (CAREFUL!)
-- DROP TABLE IF EXISTS users;

-- Create users table with all roles and fields
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin', 'staff', 'nurse') NOT NULL DEFAULT 'patient',
    phone VARCHAR(15),
    gender ENUM('Male', 'Female', 'Others'),
    date_of_birth DATE,
    address TEXT,
    emergency_contact VARCHAR(15),
    blood_group VARCHAR(5),
    -- Doctor-specific fields
    specialization VARCHAR(100),
    department VARCHAR(100),
    license_number VARCHAR(50),
    -- Staff-specific fields
    staff_id VARCHAR(50),
    -- Profile fields
    profile_picture VARCHAR(255),
    bio TEXT,
    -- Status and timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Insert sample admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password, role, status) VALUES
('Admin', 'User', 'admin@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON DUPLICATE KEY UPDATE status='active';

-- Insert sample doctors (password: doctor123)
INSERT INTO users (first_name, last_name, email, password, role, phone, specialization, department, license_number, status) VALUES
('Ramesh', 'Patel', 'dr.patel@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543210', 'Cardiology', 'Cardiology', 'DOC001', 'active'),
('Harsh', 'Shah', 'dr.shah@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543211', 'General Medicine', 'General Medicine', 'DOC002', 'active'),
('Mehul', 'Poonawala', 'dr.poonawala@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543212', 'Pediatrics', 'Pediatrics', 'DOC003', 'active'),
('Priya', 'Desai', 'dr.desai@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543213', 'Neurology', 'Neurology', 'DOC004', 'active'),
('Amit', 'Kumar', 'dr.kumar@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543214', 'Orthopedics', 'Orthopedics', 'DOC005', 'active')
ON DUPLICATE KEY UPDATE status='active';

-- Insert sample staff (password: staff123)
INSERT INTO users (first_name, last_name, email, password, role, phone, department, staff_id, status) VALUES
('Nursing', 'Staff', 'staff@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543220', 'General Ward', 'STF001', 'active'),
('Medical', 'Assistant', 'nurse@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', '9876543221', 'ICU', 'NRS001', 'active')
ON DUPLICATE KEY UPDATE status='active';

-- Insert sample patient (password: patient123)
INSERT INTO users (first_name, last_name, email, password, role, phone, gender, status) VALUES
('John', 'Doe', 'patient@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '9999999999', 'Male', 'active')
ON DUPLICATE KEY UPDATE status='active';

-- =====================================================
-- DEFAULT CREDENTIALS
-- =====================================================
-- Admin: admin@hospilink.com / admin123
-- Doctors: 
--   dr.patel@hospilink.com / doctor123
--   dr.shah@hospilink.com / doctor123
--   dr.poonawala@hospilink.com / doctor123
--   dr.desai@hospilink.com / doctor123
--   dr.kumar@hospilink.com / doctor123
-- Staff: 
--   staff@hospilink.com / staff123
--   nurse@hospilink.com / staff123
-- Patient: 
--   patient@hospilink.com / patient123
-- =====================================================

-- Verification queries
SELECT 'Users table migration completed!' as Status;
SELECT role, COUNT(*) as count FROM users GROUP BY role;
SELECT user_id, email, role, status FROM users WHERE role IN ('staff', 'nurse');
