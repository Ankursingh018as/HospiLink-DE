-- HospiLink Database Schema
-- Drop existing database if it exists and create new one
DROP DATABASE IF EXISTS hospilink;
CREATE DATABASE hospilink;
USE hospilink;

-- Users table with role-based access
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    phone VARCHAR(15),
    specialization VARCHAR(100), -- For doctors
    department VARCHAR(100), -- For doctors
    license_number VARCHAR(50), -- For doctors
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Symptoms priority keywords table
CREATE TABLE symptom_keywords (
    keyword_id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    priority_level ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
    priority_level ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    priority_score INT DEFAULT 0,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    doctor_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Patient medical history
CREATE TABLE medical_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT,
    diagnosis TEXT,
    treatment TEXT,
    medications TEXT,
    allergies TEXT,
    visit_date DATE NOT NULL,
    doctor_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Bed availability (existing feature)
CREATE TABLE beds (
    bed_id INT AUTO_INCREMENT PRIMARY KEY,
    ward_name VARCHAR(100) NOT NULL,
    bed_number VARCHAR(20) NOT NULL,
    bed_type ENUM('ICU', 'General', 'Private', 'Emergency') NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    patient_id INT,
    admitted_date TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Activity logs for admin
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Insert critical symptom keywords for AI prioritization
INSERT INTO symptom_keywords (keyword, priority_level, description) VALUES
-- Critical symptoms
('chest pain', 'critical', 'Potential heart attack or serious cardiac issue'),
('heart attack', 'critical', 'Immediate medical emergency'),
('stroke', 'critical', 'Immediate medical emergency'),
('unconscious', 'critical', 'Loss of consciousness'),
('seizure', 'critical', 'Neurological emergency'),
('severe bleeding', 'critical', 'Major blood loss'),
('difficulty breathing', 'critical', 'Respiratory distress'),
('cannot breathe', 'critical', 'Severe respiratory emergency'),
('choking', 'critical', 'Airway obstruction'),
('severe head injury', 'critical', 'Potential traumatic brain injury'),
('poisoning', 'critical', 'Toxic ingestion'),
('suicide', 'critical', 'Mental health emergency'),
('overdose', 'critical', 'Drug overdose'),
('anaphylaxis', 'critical', 'Severe allergic reaction'),
('cardiac arrest', 'critical', 'Heart stopped'),

-- High priority symptoms
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
('Harsh', 'Shah', 'dr.shah@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543211', 'General Medicine', 'General', 'DOC002', 'active'),
('Mehul', 'Poonawala', 'dr.poonawala@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '9876543212', 'Pediatrics', 'Pediatrics', 'DOC003', 'active');

-- Insert sample patient (password: patient123)
INSERT INTO users (first_name, last_name, email, password, role, phone, status) VALUES
('John', 'Doe', 'patient@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '9999999999', 'active');

-- Insert sample bed data
INSERT INTO beds (ward_name, bed_number, bed_type, status) VALUES
('ICU Ward', 'ICU-101', 'ICU', 'available'),
('ICU Ward', 'ICU-102', 'ICU', 'occupied'),
('General Ward A', 'GEN-201', 'General', 'available'),
('General Ward A', 'GEN-202', 'General', 'available'),
('General Ward B', 'GEN-301', 'General', 'occupied'),
('Private Room', 'PVT-401', 'Private', 'available'),
('Emergency', 'EMR-501', 'Emergency', 'available');
