-- HospiLink QR Code Patient Management System - Additional Tables
-- Add to existing hospilink database

USE hospilink;

-- Patient admissions with QR codes
CREATE TABLE IF NOT EXISTS patient_admissions (
    admission_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    bed_id INT,
    qr_code_token VARCHAR(255) UNIQUE NOT NULL,
    admission_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    discharge_date DATETIME,
    admission_reason TEXT,
    assigned_doctor_id INT,
    status ENUM('active', 'discharged', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (bed_id) REFERENCES beds(bed_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_qr_token (qr_code_token),
    INDEX idx_patient_status (patient_id, status)
);

-- Medicines prescribed and administered
CREATE TABLE IF NOT EXISTS patient_medicines (
    medicine_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    medicine_name VARCHAR(200) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL, -- e.g., "Every 6 hours", "Twice daily"
    route VARCHAR(50), -- e.g., "Oral", "IV", "IM"
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    prescribed_by INT NOT NULL, -- doctor_id
    status ENUM('active', 'completed', 'discontinued') DEFAULT 'active',
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE CASCADE,
    FOREIGN KEY (prescribed_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_admission_active (admission_id, status)
);

-- Medicine administration log
CREATE TABLE IF NOT EXISTS medicine_administration (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    administered_by INT NOT NULL, -- nurse/doctor user_id
    administered_at DATETIME NOT NULL,
    dose_given VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES patient_medicines(medicine_id) ON DELETE CASCADE,
    FOREIGN KEY (administered_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_medicine_time (medicine_id, administered_at)
);

-- IV/Drip details
CREATE TABLE IF NOT EXISTS patient_ivs (
    iv_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    fluid_type VARCHAR(200) NOT NULL,
    volume_ml INT NOT NULL,
    flow_rate VARCHAR(100), -- e.g., "125 ml/hour"
    started_at DATETIME NOT NULL,
    expected_end_at DATETIME,
    actual_end_at DATETIME,
    started_by INT NOT NULL, -- nurse/doctor user_id
    stopped_by INT,
    status ENUM('running', 'completed', 'discontinued') DEFAULT 'running',
    site_location VARCHAR(100), -- e.g., "Left hand", "Right arm"
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE CASCADE,
    FOREIGN KEY (started_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (stopped_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_admission_active (admission_id, status)
);

-- Test reports (X-ray, blood tests, scans, etc.)
CREATE TABLE IF NOT EXISTS patient_test_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    test_type VARCHAR(100) NOT NULL, -- e.g., "Blood Test", "X-Ray", "CT Scan", "MRI"
    test_name VARCHAR(200) NOT NULL,
    ordered_by INT NOT NULL, -- doctor_id
    ordered_at DATETIME NOT NULL,
    performed_at DATETIME,
    report_file VARCHAR(255), -- Path to uploaded report file
    results TEXT,
    findings TEXT,
    normal_range VARCHAR(100),
    status ENUM('ordered', 'in_progress', 'completed', 'cancelled') DEFAULT 'ordered',
    priority ENUM('routine', 'urgent', 'stat') DEFAULT 'routine',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE CASCADE,
    FOREIGN KEY (ordered_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_admission_status (admission_id, status)
);

-- Doctor notes and checkup history
CREATE TABLE IF NOT EXISTS doctor_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    doctor_id INT NOT NULL,
    note_type ENUM('checkup', 'progress', 'diagnosis', 'consultation', 'discharge') DEFAULT 'checkup',
    chief_complaint TEXT,
    examination_findings TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    follow_up_instructions TEXT,
    vitals_bp VARCHAR(20), -- Blood pressure
    vitals_pulse VARCHAR(20),
    vitals_temp VARCHAR(20),
    vitals_spo2 VARCHAR(20),
    vitals_respiratory_rate VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_admission_time (admission_id, created_at DESC)
);

-- Treatment schedule / What needs to be done next
CREATE TABLE IF NOT EXISTS treatment_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    task_type ENUM('medicine', 'procedure', 'test', 'checkup', 'monitoring', 'other') NOT NULL,
    task_description TEXT NOT NULL,
    scheduled_time DATETIME NOT NULL,
    assigned_to INT, -- nurse/doctor user_id
    created_by INT NOT NULL, -- doctor who created the task
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    completed_at DATETIME,
    completed_by INT,
    notes TEXT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (completed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_admission_schedule (admission_id, scheduled_time),
    INDEX idx_status_time (status, scheduled_time)
);

-- QR code scan audit trail
CREATE TABLE IF NOT EXISTS qr_scan_logs (
    scan_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    scanned_by INT NOT NULL, -- user_id of doctor/nurse
    scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    action_taken VARCHAR(100), -- 'view', 'update_medicine', 'add_note', etc.
    FOREIGN KEY (admission_id) REFERENCES patient_admissions(admission_id) ON DELETE CASCADE,
    FOREIGN KEY (scanned_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_admission_time (admission_id, scanned_at DESC),
    INDEX idx_user_time (scanned_by, scanned_at DESC)
);

-- Update users table to add 'nurse' role if not exists
ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'nurse', 'admin') NOT NULL DEFAULT 'patient';

-- Sample nurse users (password: nurse123)
INSERT INTO users (first_name, last_name, email, password, role, phone, department, status) VALUES
('Priya', 'Sharma', 'nurse.sharma@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', '9876543213', 'General Ward', 'active'),
('Anjali', 'Patel', 'nurse.patel@hospilink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', '9876543214', 'ICU', 'active');

