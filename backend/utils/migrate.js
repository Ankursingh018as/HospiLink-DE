const mongoose = require('mongoose');
const mysql = require('mysql2/promise');

// MySQL connection
const mysqlConfig = {
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'hospilink'
};

// MongoDB models
const User = require('../models/User');
const Appointment = require('../models/Appointment');
const PatientAdmission = require('../models/PatientAdmission');
const Bed = require('../models/Bed');
const MedicalHistory = require('../models/MedicalHistory');
const SymptomKeyword = require('../models/SymptomKeyword');
const ChatbotLog = require('../models/ChatbotLog');
const QRScan = require('../models/QRScan');
const ActivityLog = require('../models/ActivityLog');

// ID mapping storage
const idMapping = {
  users: {},
  appointments: {},
  beds: {},
  admissions: {}
};

async function migrateData() {
  try {
    console.log('[LAUNCH] Starting MySQL to MongoDB migration...\n');

    // Connect to MongoDB
    await mongoose.connect(process.env.MONGODB_URI || 'mongodb://localhost:27017/hospilink');
    console.log('[SUCCESS] Connected to MongoDB\n');

    // Connect to MySQL
    const mysqlConnection = await mysql.createConnection(mysqlConfig);
    console.log('[SUCCESS] Connected to MySQL\n');

    // Clear existing MongoDB data (optional - comment out if you want to keep existing data)
    console.log('[CLEARED]  Clearing existing MongoDB data...');
    await User.deleteMany({});
    await Appointment.deleteMany({});
    await PatientAdmission.deleteMany({});
    await Bed.deleteMany({});
    await MedicalHistory.deleteMany({});
    await SymptomKeyword.deleteMany({});
    await ChatbotLog.deleteMany({});
    await QRScan.deleteMany({});
    await ActivityLog.deleteMany({});
    console.log('[SUCCESS] Cleared existing data\n');

    // Migrate Users
    console.log('[USERS] Migrating users...');
    const [users] = await mysqlConnection.query('SELECT * FROM users');
    for (const user of users) {
      const newUser = await User.create({
        firstName: user.first_name,
        lastName: user.last_name,
        email: user.email,
        password: user.password, // Already hashed from PHP
        phone: user.phone,
        role: user.role,
        specialization: user.specialization,
        staffId: user.staff_id,
        dateOfBirth: user.date_of_birth,
        gender: user.gender,
        address: user.address,
        profileImage: user.profile_image,
        isActive: user.is_active !== 0,
        createdAt: user.created_at,
        updatedAt: user.updated_at
      });
      idMapping.users[user.user_id] = newUser._id;
    }
    console.log(`[SUCCESS] Migrated ${users.length} users\n`);

    // Migrate Beds
    console.log('[BED]  Migrating beds...');
    const [beds] = await mysqlConnection.query('SELECT * FROM beds');
    for (const bed of beds) {
      const newBed = await Bed.create({
        bedNumber: bed.bed_number,
        wardName: bed.ward_name,
        bedType: bed.bed_type,
        isAvailable: bed.is_available !== 0,
        status: bed.status,
        createdAt: bed.created_at
      });
      idMapping.beds[bed.bed_id] = newBed._id;
    }
    console.log(`[SUCCESS] Migrated ${beds.length} beds\n`);

    // Migrate Patient Admissions
    console.log('[HOSPITAL] Migrating patient admissions...');
    const [admissions] = await mysqlConnection.query('SELECT * FROM patient_admissions');
    for (const admission of admissions) {
      const newAdmission = await PatientAdmission.create({
        patient: idMapping.users[admission.patient_id],
        bed: admission.bed_id ? idMapping.beds[admission.bed_id] : null,
        qrCodeToken: admission.qr_code_token,
        admissionDate: admission.admission_date,
        dischargeDate: admission.discharge_date,
        admissionReason: admission.admission_reason,
        assignedDoctor: admission.assigned_doctor_id ? idMapping.users[admission.assigned_doctor_id] : null,
        status: admission.status,
        vitalSigns: admission.vital_signs ? JSON.parse(admission.vital_signs) : {},
        medications: admission.medications ? JSON.parse(admission.medications) : [],
        notes: admission.notes,
        dischargeSummary: admission.discharge_summary,
        createdAt: admission.created_at,
        updatedAt: admission.updated_at
      });
      idMapping.admissions[admission.admission_id] = newAdmission._id;

      // Update bed assignment
      if (admission.bed_id && admission.status === 'active') {
        await Bed.findByIdAndUpdate(idMapping.beds[admission.bed_id], {
          assignedTo: newAdmission._id,
          isAvailable: false,
          status: 'occupied'
        });
      }
    }
    console.log(`[SUCCESS] Migrated ${admissions.length} patient admissions\n`);

    // Migrate Appointments
    console.log('[DATE] Migrating appointments...');
    const [appointments] = await mysqlConnection.query('SELECT * FROM appointments');
    for (const appointment of appointments) {
      const newAppointment = await Appointment.create({
        patient: idMapping.users[appointment.patient_id],
        doctor: appointment.doctor_id ? idMapping.users[appointment.doctor_id] : null,
        appointmentDate: appointment.appointment_date,
        symptoms: appointment.symptoms,
        priorityLevel: appointment.priority_level,
        priorityScore: appointment.priority_score,
        status: appointment.status,
        notes: appointment.notes,
        diagnosis: appointment.diagnosis,
        treatment: appointment.treatment,
        createdAt: appointment.created_at,
        updatedAt: appointment.updated_at
      });
      idMapping.appointments[appointment.appointment_id] = newAppointment._id;
    }
    console.log(`[SUCCESS] Migrated ${appointments.length} appointments\n`);

    // Migrate Medical History
    console.log('[INFO] Migrating medical history...');
    const [medicalHistory] = await mysqlConnection.query('SELECT * FROM medical_history');
    for (const record of medicalHistory) {
      await MedicalHistory.create({
        patient: idMapping.users[record.patient_id],
        appointment: record.appointment_id ? idMapping.appointments[record.appointment_id] : null,
        diagnosis: record.diagnosis,
        treatment: record.treatment,
        prescription: record.prescription,
        notes: record.notes,
        createdByDoctor: record.created_by_doctor_id ? idMapping.users[record.created_by_doctor_id] : null,
        visitDate: record.visit_date,
        followUpDate: record.follow_up_date,
        testResults: record.test_results ? JSON.parse(record.test_results) : [],
        createdAt: record.created_at
      });
    }
    console.log(`[SUCCESS] Migrated ${medicalHistory.length} medical history records\n`);

    // Migrate Symptom Keywords
    console.log('[TEXT] Migrating symptom keywords...');
    const [keywords] = await mysqlConnection.query('SELECT * FROM symptom_keywords');
    for (const keyword of keywords) {
      await SymptomKeyword.create({
        keyword: keyword.keyword,
        priorityLevel: keyword.priority_level,
        category: keyword.category,
        description: keyword.description,
        createdAt: keyword.created_at
      });
    }
    console.log(`[SUCCESS] Migrated ${keywords.length} symptom keywords\n`);

    // Migrate Chatbot Logs
    console.log('[CHAT] Migrating chatbot logs...');
    const [chatLogs] = await mysqlConnection.query('SELECT * FROM chatbot_logs');
    for (const log of chatLogs) {
      await ChatbotLog.create({
        userMessage: log.user_message,
        botResponse: log.bot_response,
        isEmergency: log.is_emergency !== 0,
        ipAddress: log.ip_address,
        userId: log.user_id ? idMapping.users[log.user_id] : null,
        sessionId: log.session_id,
        createdAt: log.created_at
      });
    }
    console.log(`[SUCCESS] Migrated ${chatLogs.length} chatbot logs\n`);

    // Migrate Activity Logs
    console.log('[STATS] Migrating activity logs...');
    const [activityLogs] = await mysqlConnection.query('SELECT * FROM activity_logs');
    for (const log of activityLogs) {
      await ActivityLog.create({
        user: log.user_id ? idMapping.users[log.user_id] : null,
        action: log.action,
        targetModel: log.target_model,
        targetId: log.target_id,
        description: log.description,
        ipAddress: log.ip_address,
        userAgent: log.user_agent,
        createdAt: log.created_at
      });
    }
    console.log(`[SUCCESS] Migrated ${activityLogs.length} activity logs\n`);

    // Close connections
    await mysqlConnection.end();
    await mongoose.connection.close();

    console.log('[SUCCESS] Migration completed successfully!');
    console.log('\n[STATS] Migration Summary:');
    console.log(`   Users: ${users.length}`);
    console.log(`   Beds: ${beds.length}`);
    console.log(`   Admissions: ${admissions.length}`);
    console.log(`   Appointments: ${appointments.length}`);
    console.log(`   Medical History: ${medicalHistory.length}`);
    console.log(`   Symptom Keywords: ${keywords.length}`);
    console.log(`   Chatbot Logs: ${chatLogs.length}`);
    console.log(`   Activity Logs: ${activityLogs.length}`);

  } catch (error) {
    console.error('[ERROR] Migration error:', error);
    process.exit(1);
  }
}

// Run migration if called directly
if (require.main === module) {
  migrateData();
}

module.exports = migrateData;
