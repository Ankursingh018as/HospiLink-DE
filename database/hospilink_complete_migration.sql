-- =====================================================
-- HospiLink Complete Database Migration
-- For deployment on new systems
-- Date: December 27, 2025
-- =====================================================

-- Drop existing database if it exists and create new one
DROP DATABASE IF EXISTS hospilink;
CREATE DATABASE hospilink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospilink;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Users table with role-based access (includes staff role)
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

-- OTP verification table
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

-- Symptoms priority keywords table
CREATE TABLE symptom_keywords (
    keyword_id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    priority_level ENUM('high', 'medium', 'low') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_priority (priority_level)
) ENGINE=InnoDB;

-- Appointments table with AI priority
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
    -- AI-powered priority system
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

-- Patient admissions table (replaces old admitted_patients)
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
    -- QR code integration
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
    INDEX idx_qr_token (qr_code_token)
) ENGINE=InnoDB;

-- Bed availability table
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
    INDEX idx_type (bed_type)
) ENGINE=InnoDB;

-- Patient medical history
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
    INDEX idx_date (visit_date)
) ENGINE=InnoDB;

-- QR code scans table
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
    INDEX idx_admission (admission_id)
) ENGINE=InnoDB;

-- Activity logs for audit trail
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
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert symptom keywords for AI prioritization
INSERT INTO symptom_keywords (keyword, priority_level, description) VALUES
-- High priority symptoms (urgent medical attention required)
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

-- Medium priority symptoms
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

-- Low priority symptoms
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

-- Insert sample admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password, role, status) VALUES
('Admin', 'User', 'admin@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample doctors (password: doctor123)
INSERT INTO users (first_name, last_name, email, password, role, phone, specialization, department, license_number, status) VALUES
('Ramesh', 'Patel', 'dr.patel@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543210', 'Cardiology', 'Cardiology', 'DOC001', 'active'),
('Harsh', 'Shah', 'dr.shah@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543211', 'General Medicine', 'General Medicine', 'DOC002', 'active'),
('Mehul', 'Poonawala', 'dr.poonawala@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543212', 'Pediatrics', 'Pediatrics', 'DOC003', 'active'),
('Priya', 'Desai', 'dr.desai@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543213', 'Neurology', 'Neurology', 'DOC004', 'active'),
('Amit', 'Kumar', 'dr.kumar@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543214', 'Orthopedics', 'Orthopedics', 'DOC005', 'active');

-- Insert sample staff (password: staff123)
INSERT INTO users (first_name, last_name, email, password, role, phone, department, status) VALUES
('Nursing', 'Staff', 'staff@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543220', 'General Ward', 'active'),
('Medical', 'Assistant', 'nurse@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', '9876543221', 'ICU', 'active');

-- Insert sample patient (password: patient123)
INSERT INTO users (first_name, last_name, email, password, role, phone, gender, status) VALUES
('John', 'Doe', 'patient@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '9999999999', 'Male', 'active');

-- Insert sample bed data
INSERT INTO beds (ward_name, bed_number, bed_type, status) VALUES
-- ICU Beds
('ICU Ward', 'ICU-101', 'ICU', 'available'),
('ICU Ward', 'ICU-102', 'ICU', 'available'),
('ICU Ward', 'ICU-103', 'ICU', 'available'),
('ICU Ward', 'ICU-104', 'ICU', 'available'),
-- General Ward Beds
('General Ward A', 'GEN-201', 'General', 'available'),
('General Ward A', 'GEN-202', 'General', 'available'),
('General Ward A', 'GEN-203', 'General', 'available'),
('General Ward A', 'GEN-204', 'General', 'available'),
('General Ward B', 'GEN-301', 'General', 'available'),
('General Ward B', 'GEN-302', 'General', 'available'),
('General Ward B', 'GEN-303', 'General', 'available'),
('General Ward B', 'GEN-304', 'General', 'available'),
-- Private Rooms
('Private Ward', 'PVT-401', 'Private', 'available'),
('Private Ward', 'PVT-402', 'Private', 'available'),
('Private Ward', 'PVT-403', 'Private', 'available'),
-- Emergency Beds
('Emergency Ward', 'EMR-501', 'Emergency', 'available'),
('Emergency Ward', 'EMR-502', 'Emergency', 'available'),
('Emergency Ward', 'EMR-503', 'Emergency', 'available');

-- =====================================================
-- DEFAULT CREDENTIALS
-- =====================================================
-- Admin: admin@hospilink.com / admin123
-- Doctors: dr.patel@hospilink.com / doctor123
--          dr.shah@hospilink.com / doctor123
--          dr.poonawala@hospilink.com / doctor123
--          dr.desai@hospilink.com / doctor123
--          dr.kumar@hospilink.com / doctor123
-- Staff: staff@hospilink.com / staff123
--        nurse@hospilink.com / staff123
-- Patient: patient@hospilink.com / patient123
-- =====================================================

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
SELECT 'Database migration completed successfully!' as Status;
SELECT COUNT(*) as 'Total Users' FROM users;
SELECT COUNT(*) as 'Total Beds' FROM beds;
SELECT COUNT(*) as 'Symptom Keywords' FROM symptom_keywords;
