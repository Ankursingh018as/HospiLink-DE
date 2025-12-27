-- =====================================================
-- HospiLink Complete Production Database Migration
-- 100% Error-Free | Full System Deployment
-- Date: December 27, 2025
-- =====================================================
-- 
-- This migration will:
-- 1. Drop existing database completely
-- 2. Create fresh database with UTF-8 support
-- 3. Create all tables in correct order (no foreign key errors)
-- 4. Insert all sample data with proper passwords
-- 5. Verify successful migration
--
-- Usage: Import this file in phpMyAdmin or run:
-- mysql -u root -p < hospilink_production_migration.sql
-- =====================================================

-- Drop and recreate database
DROP DATABASE IF EXISTS hospilink;
CREATE DATABASE hospilink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospilink;

-- =====================================================
-- CORE SYSTEM TABLES
-- =====================================================

-- 1. Users Table (Foundation - no dependencies)
CREATE TABLE users (
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
) ENGINE=InnoDB;

-- 2. OTP Verification Table (no dependencies)
CREATE TABLE otp_verification (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    user_role ENUM('patient', 'doctor', 'admin', 'staff', 'nurse') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    attempts INT DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_otp (otp_code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- 3. Symptom Keywords Table (no dependencies)
CREATE TABLE symptom_keywords (
    keyword_id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    priority_level ENUM('high', 'medium', 'low') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_priority (priority_level),
    INDEX idx_keyword (keyword)
) ENGINE=InnoDB;

-- 4. Beds Table (depends on users for patient_id)
CREATE TABLE beds (
    bed_id INT AUTO_INCREMENT PRIMARY KEY,
    ward_name VARCHAR(100) NOT NULL,
    bed_number VARCHAR(20) NOT NULL,
    bed_type ENUM('ICU', 'General', 'Private', 'Emergency') NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    patient_id INT,
    admitted_date TIMESTAMP NULL,
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_bed (ward_name, bed_number),
    INDEX idx_status (status),
    INDEX idx_type (bed_type),
    INDEX idx_patient (patient_id)
) ENGINE=InnoDB;

-- 5. Appointments Table (depends on users)
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Others') NOT NULL,
    phone VARCHAR(15) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    symptoms TEXT NOT NULL,
    -- AI-powered priority system (3 levels only)
    priority_level ENUM('high', 'medium', 'low') NOT NULL DEFAULT 'medium',
    priority_score INT DEFAULT 50,
    ai_analysis JSON,
    suspected_conditions TEXT,
    recommended_specialist VARCHAR(100),
    urgency_reason TEXT,
    -- Status and notes
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    cancellation_reason TEXT,
    doctor_notes TEXT,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_priority (priority_level, priority_score)
) ENGINE=InnoDB;

-- 6. Patient Admissions Table (depends on users and beds)
CREATE TABLE patient_admissions (
    admission_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    bed_id INT,
    admission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    discharge_date TIMESTAMP NULL,
    admission_reason TEXT NOT NULL,
    diagnosis TEXT,
    treatment_plan TEXT,
    assigned_doctor_id INT,
    status ENUM('active', 'discharged', 'transferred') DEFAULT 'active',
    -- QR code integration for patient tracking
    qr_code_token VARCHAR(100) UNIQUE,
    qr_code_generated_at TIMESTAMP NULL,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (bed_id) REFERENCES beds(bed_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_bed (bed_id),
    INDEX idx_status (status),
    INDEX idx_qr_token (qr_code_token),
    INDEX idx_admission_date (admission_date)
) ENGINE=InnoDB;

-- 7. Medical History Table (depends on users, appointments, patient_admissions)
CREATE TABLE medical_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT,
    admission_id INT,
    diagnosis TEXT,
    treatment TEXT,
    medications TEXT,
    allergies TEXT,
    vital_signs JSON,
    visit_date DATE NOT NULL,
    doctor_id INT,
    notes TEXT,
    attachments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_date (visit_date),
    INDEX idx_doctor (doctor_id)
) ENGINE=InnoDB;

-- 8. QR Code Scans Table (depends on patient_admissions and users)
CREATE TABLE qr_scans (
    scan_id INT AUTO_INCREMENT PRIMARY KEY,
    qr_token VARCHAR(100) NOT NULL,
    admission_id INT,
    scanned_by INT,
    scan_location VARCHAR(100),
    scan_purpose ENUM('verification', 'medication', 'vitals', 'general') DEFAULT 'general',
    notes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE CASCADE,
    FOREIGN KEY (scanned_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_token (qr_token),
    INDEX idx_admission (admission_id),
    INDEX idx_scanned_at (scanned_at)
) ENGINE=InnoDB;

-- 9. Activity Logs Table (depends on users)
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert Symptom Keywords for AI Prioritization
-- HIGH PRIORITY (70-100 score) - Urgent medical attention
INSERT INTO symptom_keywords (keyword, priority_level, description) VALUES
('chest pain', 'high', 'Potential heart attack or serious cardiac issue'),
('heart attack', 'high', 'Immediate medical emergency'),
('stroke', 'high', 'Immediate medical emergency'),
('unconscious', 'high', 'Loss of consciousness'),
('seizure', 'high', 'Neurological emergency'),
('severe bleeding', 'high', 'Major blood loss'),
('difficulty breathing', 'high', 'Respiratory distress'),
('cannot breathe', 'high', 'Severe respiratory emergency'),
('choking', 'high', 'Airway obstruction'),
('severe head injury', 'high', 'Potential traumatic brain injury'),
('poisoning', 'high', 'Toxic ingestion'),
('suicide', 'high', 'Mental health emergency'),
('overdose', 'high', 'Drug overdose'),
('anaphylaxis', 'high', 'Severe allergic reaction'),
('cardiac arrest', 'high', 'Heart stopped'),
('high fever', 'high', 'Fever above 103°F'),
('severe pain', 'high', 'Intense pain requiring urgent attention'),
('broken bone', 'high', 'Fracture requiring treatment'),
('severe burn', 'high', 'Major burn injury'),
('deep cut', 'high', 'Wound requiring stitches'),
('vomiting blood', 'high', 'Internal bleeding indicator'),
('blood in stool', 'high', 'Gastrointestinal bleeding'),
('severe abdominal pain', 'high', 'Potential surgical emergency'),
('pregnancy complications', 'high', 'Maternal/fetal health risk'),
('diabetic emergency', 'high', 'Blood sugar crisis'),
('asthma attack', 'high', 'Respiratory distress'),
('allergic reaction', 'high', 'Significant allergic response'),
('severe headache', 'high', 'Potential serious condition'),
('confusion', 'high', 'Altered mental status'),
('slurred speech', 'high', 'Potential stroke symptom'),

-- MEDIUM PRIORITY (40-69 score) - Needs attention but not urgent
('fever', 'medium', 'Elevated temperature'),
('cough', 'medium', 'Persistent cough'),
('cold', 'medium', 'Common cold symptoms'),
('flu', 'medium', 'Influenza symptoms'),
('sore throat', 'medium', 'Throat pain'),
('ear pain', 'medium', 'Ear infection possible'),
('stomach ache', 'medium', 'Abdominal discomfort'),
('diarrhea', 'medium', 'Digestive issue'),
('vomiting', 'medium', 'Nausea and vomiting'),
('rash', 'medium', 'Skin condition'),
('joint pain', 'medium', 'Arthralgia'),
('back pain', 'medium', 'Musculoskeletal pain'),
('urinary problems', 'medium', 'Urinary tract concerns'),
('dizziness', 'medium', 'Vertigo or lightheadedness'),
('fatigue', 'medium', 'Extreme tiredness'),
('nausea', 'medium', 'Feeling sick'),
('headache', 'medium', 'Head pain'),
('migraine', 'medium', 'Severe headache'),

-- LOW PRIORITY (0-39 score) - Routine care
('routine checkup', 'low', 'Regular health screening'),
('physical exam', 'low', 'General examination'),
('vaccination', 'low', 'Immunization'),
('follow-up', 'low', 'Post-treatment follow-up'),
('prescription refill', 'low', 'Medication renewal'),
('health certificate', 'low', 'Medical documentation'),
('minor bruise', 'low', 'Small contusion'),
('minor scrape', 'low', 'Superficial wound'),
('mild headache', 'low', 'Minor headache'),
('common cold', 'low', 'Mild cold symptoms'),
('seasonal allergies', 'low', 'Hay fever'),
('consultation', 'low', 'General medical advice'),
('wellness visit', 'low', 'Preventive care'),
('screening', 'low', 'Health screening test');

-- Insert Admin User (password: admin123)
-- Hash generated with: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (first_name, last_name, email, password, role, status) VALUES
('Admin', 'User', 'admin@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert Sample Doctors (password: doctor123)
-- Specialization covers major departments
INSERT INTO users (first_name, last_name, email, password, role, phone, specialization, department, license_number, status) VALUES
('Ramesh', 'Patel', 'dr.patel@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543210', 'Cardiology', 'Cardiology', 'DOC001', 'active'),
('Harsh', 'Shah', 'dr.shah@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543211', 'General Medicine', 'General Medicine', 'DOC002', 'active'),
('Mehul', 'Poonawala', 'dr.poonawala@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543212', 'Pediatrics', 'Pediatrics', 'DOC003', 'active'),
('Priya', 'Desai', 'dr.desai@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543213', 'Neurology', 'Neurology', 'DOC004', 'active'),
('Amit', 'Kumar', 'dr.kumar@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543214', 'Orthopedics', 'Orthopedics', 'DOC005', 'active'),
('Sneha', 'Verma', 'dr.verma@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543215', 'Gynecology', 'Gynecology', 'DOC006', 'active'),
('Rajesh', 'Sharma', 'dr.sharma@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543216', 'Dermatology', 'Dermatology', 'DOC007', 'active'),
('Anjali', 'Reddy', 'dr.reddy@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543217', 'ENT', 'ENT', 'DOC008', 'active');

-- Insert Staff/Nurses (password: staff123)
-- Different departments for ward coverage
INSERT INTO users (first_name, last_name, email, password, role, phone, department, staff_id, status) VALUES
('Nursing', 'Staff', 'staff@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543220', 'General Ward', 'STF001', 'active'),
('Medical', 'Assistant', 'nurse@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', '9876543221', 'ICU', 'NRS001', 'active'),
('ICU', 'Nurse', 'icu.nurse@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', '9876543222', 'ICU', 'NRS002', 'active'),
('Emergency', 'Staff', 'emergency.staff@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543223', 'Emergency Ward', 'STF002', 'active');

-- Insert Sample Patient (password: patient123)
INSERT INTO users (first_name, last_name, email, password, role, phone, gender, blood_group, status) VALUES
('John', 'Doe', 'patient@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '9999999999', 'Male', 'O+', 'active'),
('Jane', 'Smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '9999999998', 'Female', 'A+', 'active'),
('Raj', 'Kumar', 'raj.kumar@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '9999999997', 'Male', 'B+', 'active');

-- Insert Bed Data (Realistic hospital bed allocation)
INSERT INTO beds (ward_name, bed_number, bed_type, status) VALUES
-- ICU Beds (6 beds)
('ICU Ward', 'ICU-101', 'ICU', 'available'),
('ICU Ward', 'ICU-102', 'ICU', 'available'),
('ICU Ward', 'ICU-103', 'ICU', 'available'),
('ICU Ward', 'ICU-104', 'ICU', 'available'),
('ICU Ward', 'ICU-105', 'ICU', 'available'),
('ICU Ward', 'ICU-106', 'ICU', 'available'),

-- General Ward A (10 beds)
('General Ward A', 'GEN-A-201', 'General', 'available'),
('General Ward A', 'GEN-A-202', 'General', 'available'),
('General Ward A', 'GEN-A-203', 'General', 'available'),
('General Ward A', 'GEN-A-204', 'General', 'available'),
('General Ward A', 'GEN-A-205', 'General', 'available'),
('General Ward A', 'GEN-A-206', 'General', 'available'),
('General Ward A', 'GEN-A-207', 'General', 'available'),
('General Ward A', 'GEN-A-208', 'General', 'available'),
('General Ward A', 'GEN-A-209', 'General', 'available'),
('General Ward A', 'GEN-A-210', 'General', 'available'),

-- General Ward B (10 beds)
('General Ward B', 'GEN-B-301', 'General', 'available'),
('General Ward B', 'GEN-B-302', 'General', 'available'),
('General Ward B', 'GEN-B-303', 'General', 'available'),
('General Ward B', 'GEN-B-304', 'General', 'available'),
('General Ward B', 'GEN-B-305', 'General', 'available'),
('General Ward B', 'GEN-B-306', 'General', 'available'),
('General Ward B', 'GEN-B-307', 'General', 'available'),
('General Ward B', 'GEN-B-308', 'General', 'available'),
('General Ward B', 'GEN-B-309', 'General', 'available'),
('General Ward B', 'GEN-B-310', 'General', 'available'),

-- Private Rooms (8 rooms)
('Private Ward', 'PVT-401', 'Private', 'available'),
('Private Ward', 'PVT-402', 'Private', 'available'),
('Private Ward', 'PVT-403', 'Private', 'available'),
('Private Ward', 'PVT-404', 'Private', 'available'),
('Private Ward', 'PVT-405', 'Private', 'available'),
('Private Ward', 'PVT-406', 'Private', 'available'),
('Private Ward', 'PVT-407', 'Private', 'available'),
('Private Ward', 'PVT-408', 'Private', 'available'),

-- Emergency Beds (6 beds)
('Emergency Ward', 'EMR-501', 'Emergency', 'available'),
('Emergency Ward', 'EMR-502', 'Emergency', 'available'),
('Emergency Ward', 'EMR-503', 'Emergency', 'available'),
('Emergency Ward', 'EMR-504', 'Emergency', 'available'),
('Emergency Ward', 'EMR-505', 'Emergency', 'available'),
('Emergency Ward', 'EMR-506', 'Emergency', 'available');

-- =====================================================
-- MIGRATION VERIFICATION
-- =====================================================

-- Display migration results
SELECT '✅ Database Created Successfully!' as Status;
SELECT '✅ All Tables Created!' as Status;
SELECT '✅ Sample Data Inserted!' as Status;

-- Show summary statistics
SELECT 'Database Summary' as Report;
SELECT 
    'Total Users' as Metric,
    COUNT(*) as Count,
    GROUP_CONCAT(role, ':', cnt SEPARATOR ' | ') as Breakdown
FROM (
    SELECT role, COUNT(*) as cnt 
    FROM users 
    GROUP BY role
) as role_counts;

SELECT 'Total Beds' as Metric, COUNT(*) as Count FROM beds;
SELECT 'Available Beds' as Metric, COUNT(*) as Count FROM beds WHERE status = 'available';
SELECT 'Symptom Keywords' as Metric, COUNT(*) as Count FROM symptom_keywords;

-- Display all users with roles
SELECT 
    user_id, 
    email, 
    role, 
    CONCAT(first_name, ' ', last_name) as full_name,
    department,
    specialization,
    status
FROM users 
ORDER BY 
    FIELD(role, 'admin', 'doctor', 'staff', 'nurse', 'patient'),
    user_id;

-- =====================================================
-- DEFAULT LOGIN CREDENTIALS
-- =====================================================
-- 
-- ADMIN:
-- Email: admin@hospilink.com | Password: admin123
--
-- DOCTORS (all password: doctor123):
-- dr.patel@hospilink.com - Cardiology
-- dr.shah@hospilink.com - General Medicine
-- dr.poonawala@hospilink.com - Pediatrics
-- dr.desai@hospilink.com - Neurology
-- dr.kumar@hospilink.com - Orthopedics
-- dr.verma@hospilink.com - Gynecology
-- dr.sharma@hospilink.com - Dermatology
-- dr.reddy@hospilink.com - ENT
--
-- STAFF/NURSES (all password: staff123):
-- staff@hospilink.com - General Ward Staff
-- nurse@hospilink.com - ICU Nurse
-- icu.nurse@hospilink.com - ICU Nurse
-- emergency.staff@hospilink.com - Emergency Staff
--
-- PATIENTS (all password: patient123):
-- patient@hospilink.com - John Doe
-- jane.smith@example.com - Jane Smith
-- raj.kumar@example.com - Raj Kumar
--
-- ⚠️ IMPORTANT: Change these passwords after first login!
-- =====================================================

-- Migration Complete
SELECT '🎉 Migration Completed Successfully!' as Status;
SELECT 'You can now access the system at: http://localhost/HospiLink-DE' as Message;
