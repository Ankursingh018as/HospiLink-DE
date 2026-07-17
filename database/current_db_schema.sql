-- HospiLink Current Database Schema Dump
-- Generated on: 2026-07-16 18:37:58
-- Database: hospilink3

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for table `activity_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `admitted_patients`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admitted_patients`;
CREATE TABLE `admitted_patients` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `disease` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `bed_id` int(11) DEFAULT NULL,
  `admission_date` datetime NOT NULL DEFAULT current_timestamp(),
  `discharge_date` datetime DEFAULT NULL,
  `status` enum('stable','moderate','critical') DEFAULT 'stable',
  `priority` enum('stable','moderate','critical') DEFAULT 'stable',
  `assigned_staff_id` int(11) DEFAULT NULL,
  `assignment_notes` text DEFAULT NULL,
  `discharge_summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`patient_id`),
  KEY `bed_id` (`bed_id`),
  KEY `assigned_staff_id` (`assigned_staff_id`),
  CONSTRAINT `admitted_patients_ibfk_1` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`bed_id`) ON DELETE SET NULL,
  CONSTRAINT `admitted_patients_ibfk_2` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `appointments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Others') NOT NULL,
  `phone` varchar(15) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `symptoms` text NOT NULL,
  `priority_level` enum('critical','high','medium','low') NOT NULL,
  `priority_score` int(11) DEFAULT 0,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `doctor_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ai_analysis` text DEFAULT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `beds`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `beds`;
CREATE TABLE `beds` (
  `bed_id` int(11) NOT NULL AUTO_INCREMENT,
  `ward_name` varchar(100) NOT NULL,
  `bed_number` varchar(20) NOT NULL,
  `bed_type` enum('ICU','General','Private','Emergency') NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `patient_id` int(11) DEFAULT NULL,
  `admitted_date` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`bed_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `beds_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `chatbot_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `chatbot_logs`;
CREATE TABLE `chatbot_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_message` text NOT NULL,
  `bot_response` text NOT NULL,
  `is_emergency` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_emergency` (`is_emergency`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `doctor_notes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `doctor_notes`;
CREATE TABLE `doctor_notes` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `note_type` enum('checkup','progress','diagnosis','consultation','discharge') DEFAULT 'checkup',
  `chief_complaint` text DEFAULT NULL,
  `examination_findings` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `follow_up_instructions` text DEFAULT NULL,
  `vitals_bp` varchar(20) DEFAULT NULL,
  `vitals_pulse` varchar(20) DEFAULT NULL,
  `vitals_temp` varchar(20) DEFAULT NULL,
  `vitals_spo2` varchar(20) DEFAULT NULL,
  `vitals_respiratory_rate` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`note_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_admission_time` (`admission_id`,`created_at`),
  CONSTRAINT `doctor_notes_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `patient_admissions` (`admission_id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_notes_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `medical_history`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `medical_history`;
CREATE TABLE `medical_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `visit_date` date NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `patient_id` (`patient_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `medical_history_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `medical_history_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `medicine_administration`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `medicine_administration`;
CREATE TABLE `medicine_administration` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` int(11) NOT NULL,
  `administered_by` int(11) NOT NULL,
  `administered_at` datetime NOT NULL,
  `dose_given` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  KEY `administered_by` (`administered_by`),
  KEY `idx_medicine_time` (`medicine_id`,`administered_at`),
  CONSTRAINT `medicine_administration_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `patient_medicines` (`medicine_id`) ON DELETE CASCADE,
  CONSTRAINT `medicine_administration_ibfk_2` FOREIGN KEY (`administered_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `otp_verification`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `otp_verification`;
CREATE TABLE `otp_verification` (
  `otp_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `user_data` text NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `resend_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`otp_id`),
  KEY `idx_email` (`email`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_verified` (`verified`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `patient_admissions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `patient_admissions`;
CREATE TABLE `patient_admissions` (
  `admission_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `bed_id` int(11) DEFAULT NULL,
  `qr_code_token` varchar(255) NOT NULL,
  `admission_date` datetime NOT NULL DEFAULT current_timestamp(),
  `discharge_date` datetime DEFAULT NULL,
  `admission_reason` text DEFAULT NULL,
  `assigned_doctor_id` int(11) DEFAULT NULL,
  `status` enum('active','discharged','transferred') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admission_id`),
  UNIQUE KEY `qr_code_token` (`qr_code_token`),
  KEY `bed_id` (`bed_id`),
  KEY `assigned_doctor_id` (`assigned_doctor_id`),
  KEY `idx_qr_token` (`qr_code_token`),
  KEY `idx_patient_status` (`patient_id`,`status`),
  CONSTRAINT `patient_admissions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `patient_admissions_ibfk_2` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`bed_id`) ON DELETE SET NULL,
  CONSTRAINT `patient_admissions_ibfk_3` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `patient_ivs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `patient_ivs`;
CREATE TABLE `patient_ivs` (
  `iv_id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `fluid_type` varchar(200) NOT NULL,
  `volume_ml` int(11) NOT NULL,
  `flow_rate` varchar(100) DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `expected_end_at` datetime DEFAULT NULL,
  `actual_end_at` datetime DEFAULT NULL,
  `started_by` int(11) NOT NULL,
  `stopped_by` int(11) DEFAULT NULL,
  `status` enum('running','completed','discontinued') DEFAULT 'running',
  `site_location` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`iv_id`),
  KEY `started_by` (`started_by`),
  KEY `stopped_by` (`stopped_by`),
  KEY `idx_admission_active` (`admission_id`,`status`),
  CONSTRAINT `patient_ivs_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `patient_admissions` (`admission_id`) ON DELETE CASCADE,
  CONSTRAINT `patient_ivs_ibfk_2` FOREIGN KEY (`started_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `patient_ivs_ibfk_3` FOREIGN KEY (`stopped_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `patient_medicines`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `patient_medicines`;
CREATE TABLE `patient_medicines` (
  `medicine_id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `route` varchar(50) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `prescribed_by` int(11) NOT NULL,
  `status` enum('active','completed','discontinued') DEFAULT 'active',
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`medicine_id`),
  KEY `prescribed_by` (`prescribed_by`),
  KEY `idx_admission_active` (`admission_id`,`status`),
  CONSTRAINT `patient_medicines_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `patient_admissions` (`admission_id`) ON DELETE CASCADE,
  CONSTRAINT `patient_medicines_ibfk_2` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `patient_test_reports`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `patient_test_reports`;
CREATE TABLE `patient_test_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `test_type` varchar(100) NOT NULL,
  `test_name` varchar(200) NOT NULL,
  `ordered_by` int(11) NOT NULL,
  `ordered_at` datetime NOT NULL,
  `performed_at` datetime DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `results` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `normal_range` varchar(100) DEFAULT NULL,
  `status` enum('ordered','in_progress','completed','cancelled') DEFAULT 'ordered',
  `priority` enum('routine','urgent','stat') DEFAULT 'routine',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `ordered_by` (`ordered_by`),
  KEY `idx_admission_status` (`admission_id`,`status`),
  CONSTRAINT `patient_test_reports_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `patient_admissions` (`admission_id`) ON DELETE CASCADE,
  CONSTRAINT `patient_test_reports_ibfk_2` FOREIGN KEY (`ordered_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `qr_patient_management`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `qr_patient_management`;
CREATE TABLE `qr_patient_management` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `age` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `disease` text DEFAULT NULL,
  `qr_token` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `idx_qr_token` (`qr_token`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `qr_scan_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `qr_scan_logs`;
CREATE TABLE `qr_scan_logs` (
  `scan_id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `scanned_by` int(11) DEFAULT NULL,
  `scanned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `action_taken` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`scan_id`),
  KEY `idx_admission_time` (`admission_id`,`scanned_at`),
  KEY `idx_user_time` (`scanned_by`,`scanned_at`),
  CONSTRAINT `qr_scan_logs_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `patient_admissions` (`admission_id`) ON DELETE CASCADE,
  CONSTRAINT `qr_scan_logs_ibfk_2` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `symptom_keywords`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `symptom_keywords`;
CREATE TABLE `symptom_keywords` (
  `keyword_id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `priority_level` enum('critical','high','medium','low') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`keyword_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `treatment_schedule`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `treatment_schedule`;
CREATE TABLE `treatment_schedule` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `task_type` enum('medicine','procedure','test','checkup','monitoring','other') NOT NULL,
  `task_description` text NOT NULL,
  `scheduled_time` datetime NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `completed_by` (`completed_by`),
  KEY `idx_admission_schedule` (`admission_id`,`scheduled_time`),
  KEY `idx_status_time` (`status`,`scheduled_time`),
  CONSTRAINT `treatment_schedule_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `patient_admissions` (`admission_id`) ON DELETE CASCADE,
  CONSTRAINT `treatment_schedule_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `treatment_schedule_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `treatment_schedule_ibfk_4` FOREIGN KEY (`completed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin','staff','nurse') NOT NULL DEFAULT 'patient',
  `phone` varchar(15) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `staff_id` varchar(50) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
