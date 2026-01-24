require('dotenv').config();
const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

// Import models
const User = require('./models/User');
const SymptomKeyword = require('./models/SymptomKeyword');
const Bed = require('./models/Bed');
const AdmittedPatient = require('./models/AdmittedPatient');
const Appointment = require('./models/Appointment');
const ActivityLog = require('./models/ActivityLog');

// Connect to MongoDB
const connectDB = async () => {
  try {
    await mongoose.connect(process.env.MONGODB_URI);
    console.log('✅ MongoDB Connected');
  } catch (error) {
    console.error('❌ MongoDB Connection Error:', error.message);
    process.exit(1);
  }
};

// Hash password function (matches PHP bcrypt)
const hashPassword = async (password) => {
  const salt = await bcrypt.genSalt(10);
  return await bcrypt.hash(password, salt);
};

// Seed Users (matching MySQL schema)
const seedUsers = async () => {
  console.log('🌱 Seeding Users...');
  
  const password = await hashPassword('password123'); // Common password for all test users
  
  const users = [
    {
      firstName: 'Admin',
      lastName: 'User',
      email: 'admin@hospilink.com',
      password: password,
      role: 'admin',
      phone: '9999999990',
      status: 'active'
    },
    {
      firstName: 'Dr. Ramesh',
      lastName: 'Patel',
      email: 'dr.patel@hospilink.com',
      password: password,
      role: 'doctor',
      phone: '9876543210',
      specialization: 'Cardiology',
      department: 'Cardiology',
      licenseNumber: 'DOC001',
      status: 'active'
    },
    {
      firstName: 'Dr. Harsh',
      lastName: 'Shah',
      email: 'dr.shah@hospilink.com',
      password: password,
      role: 'doctor',
      phone: '9876543211',
      specialization: 'General Medicine',
      department: 'General',
      licenseNumber: 'DOC002',
      status: 'active'
    },
    {
      firstName: 'Dr. Mehul',
      lastName: 'Poonawala',
      email: 'dr.poonawala@hospilink.com',
      password: password,
      role: 'doctor',
      phone: '9876543212',
      specialization: 'Pediatrics',
      department: 'Pediatrics',
      licenseNumber: 'DOC003',
      status: 'active'
    },
    {
      firstName: 'John',
      lastName: 'Doe',
      email: 'patient@hospilink.com',
      password: password,
      role: 'patient',
      phone: '9999999999',
      gender: 'Male',
      bloodGroup: 'O+',
      status: 'active'
    },
    {
      firstName: 'Sarah',
      lastName: 'Johnson',
      email: 'sarah.j@hospilink.com',
      password: password,
      role: 'patient',
      phone: '9999999991',
      gender: 'Female',
      bloodGroup: 'A+',
      status: 'active'
    },
    {
      firstName: 'John',
      lastName: 'Staff',
      email: 'staff@hospilink.com',
      password: password,
      role: 'staff',
      phone: '9999999992',
      department: 'General Ward',
      staffId: 'STF-001',
      status: 'active'
    },
    {
      firstName: 'Nurse',
      lastName: 'Mary',
      email: 'nurse@hospilink.com',
      password: password,
      role: 'nurse',
      phone: '9999999993',
      department: 'ICU',
      staffId: 'NUR-001',
      status: 'active'
    }
  ];

  await User.deleteMany({});
  const createdUsers = await User.insertMany(users);
  console.log(`✅ Created ${createdUsers.length} users`);
  return createdUsers;
};

// Seed Symptom Keywords (matching MySQL data)
const seedSymptomKeywords = async () => {
  console.log('🌱 Seeding Symptom Keywords...');
  
  const symptoms = [
    // High priority symptoms
    { keyword: 'chest pain', priorityLevel: 'high', description: 'Potential heart attack or serious cardiac issue' },
    { keyword: 'heart attack', priorityLevel: 'high', description: 'Immediate medical emergency' },
    { keyword: 'stroke', priorityLevel: 'high', description: 'Immediate medical emergency' },
    { keyword: 'unconscious', priorityLevel: 'high', description: 'Loss of consciousness' },
    { keyword: 'seizure', priorityLevel: 'high', description: 'Neurological emergency' },
    { keyword: 'severe bleeding', priorityLevel: 'high', description: 'Major blood loss' },
    { keyword: 'difficulty breathing', priorityLevel: 'high', description: 'Respiratory distress' },
    { keyword: 'cannot breathe', priorityLevel: 'high', description: 'Severe respiratory emergency' },
    { keyword: 'choking', priorityLevel: 'high', description: 'Airway obstruction' },
    { keyword: 'severe head injury', priorityLevel: 'high', description: 'Potential traumatic brain injury' },
    { keyword: 'poisoning', priorityLevel: 'high', description: 'Toxic ingestion' },
    { keyword: 'suicide', priorityLevel: 'high', description: 'Mental health emergency' },
    { keyword: 'overdose', priorityLevel: 'high', description: 'Drug overdose' },
    { keyword: 'anaphylaxis', priorityLevel: 'high', description: 'Severe allergic reaction' },
    { keyword: 'cardiac arrest', priorityLevel: 'high', description: 'Heart stopped' },
    { keyword: 'high fever', priorityLevel: 'high', description: 'Fever above 103°F' },
    { keyword: 'severe pain', priorityLevel: 'high', description: 'Intense pain requiring urgent attention' },
    { keyword: 'broken bone', priorityLevel: 'high', description: 'Fracture requiring treatment' },
    { keyword: 'severe burn', priorityLevel: 'high', description: 'Major burn injury' },
    { keyword: 'deep cut', priorityLevel: 'high', description: 'Wound requiring stitches' },
    { keyword: 'vomiting blood', priorityLevel: 'high', description: 'Internal bleeding indicator' },
    { keyword: 'blood in stool', priorityLevel: 'high', description: 'Gastrointestinal bleeding' },
    { keyword: 'severe abdominal pain', priorityLevel: 'high', description: 'Potential surgical emergency' },
    { keyword: 'pregnancy complications', priorityLevel: 'high', description: 'Maternal/fetal health risk' },
    { keyword: 'diabetic emergency', priorityLevel: 'high', description: 'Blood sugar crisis' },
    { keyword: 'asthma attack', priorityLevel: 'high', description: 'Respiratory distress' },
    { keyword: 'allergic reaction', priorityLevel: 'high', description: 'Significant allergic response' },
    { keyword: 'severe headache', priorityLevel: 'high', description: 'Potential serious condition' },
    { keyword: 'confusion', priorityLevel: 'high', description: 'Altered mental status' },
    { keyword: 'slurred speech', priorityLevel: 'high', description: 'Potential stroke symptom' },
    
    // Medium priority symptoms
    { keyword: 'fever', priorityLevel: 'medium', description: 'Elevated temperature' },
    { keyword: 'cough', priorityLevel: 'medium', description: 'Persistent cough' },
    { keyword: 'cold', priorityLevel: 'medium', description: 'Common cold symptoms' },
    { keyword: 'flu', priorityLevel: 'medium', description: 'Influenza symptoms' },
    { keyword: 'sore throat', priorityLevel: 'medium', description: 'Throat pain' },
    { keyword: 'ear pain', priorityLevel: 'medium', description: 'Ear infection possible' },
    { keyword: 'stomach ache', priorityLevel: 'medium', description: 'Abdominal discomfort' },
    { keyword: 'diarrhea', priorityLevel: 'medium', description: 'Digestive issue' },
    { keyword: 'vomiting', priorityLevel: 'medium', description: 'Nausea and vomiting' },
    { keyword: 'rash', priorityLevel: 'medium', description: 'Skin condition' },
    { keyword: 'joint pain', priorityLevel: 'medium', description: 'Arthralgia' },
    { keyword: 'back pain', priorityLevel: 'medium', description: 'Musculoskeletal pain' },
    { keyword: 'urinary problems', priorityLevel: 'medium', description: 'Urinary tract concerns' },
    { keyword: 'dizziness', priorityLevel: 'medium', description: 'Vertigo or lightheadedness' },
    { keyword: 'fatigue', priorityLevel: 'medium', description: 'Extreme tiredness' },
    
    // Low priority symptoms
    { keyword: 'routine checkup', priorityLevel: 'low', description: 'Regular health screening' },
    { keyword: 'physical exam', priorityLevel: 'low', description: 'General examination' },
    { keyword: 'vaccination', priorityLevel: 'low', description: 'Immunization' },
    { keyword: 'follow-up', priorityLevel: 'low', description: 'Post-treatment follow-up' },
    { keyword: 'prescription refill', priorityLevel: 'low', description: 'Medication renewal' },
    { keyword: 'health certificate', priorityLevel: 'low', description: 'Medical documentation' },
    { keyword: 'minor bruise', priorityLevel: 'low', description: 'Small contusion' },
    { keyword: 'minor scrape', priorityLevel: 'low', description: 'Superficial wound' },
    { keyword: 'mild headache', priorityLevel: 'low', description: 'Minor headache' },
    { keyword: 'common cold', priorityLevel: 'low', description: 'Mild cold symptoms' },
    { keyword: 'seasonal allergies', priorityLevel: 'low', description: 'Hay fever' },
    { keyword: 'consultation', priorityLevel: 'low', description: 'General medical advice' },
    { keyword: 'wellness visit', priorityLevel: 'low', description: 'Preventive care' },
    { keyword: 'screening', priorityLevel: 'low', description: 'Health screening test' }
  ];

  await SymptomKeyword.deleteMany({});
  const createdSymptoms = await SymptomKeyword.insertMany(symptoms);
  console.log(`✅ Created ${createdSymptoms.length} symptom keywords`);
};

// Seed Beds (matching MySQL data)
const seedBeds = async () => {
  console.log('🌱 Seeding Beds...');
  
  const beds = [
    { wardName: 'ICU Ward', bedNumber: 'ICU-101', bedType: 'ICU', status: 'available' },
    { wardName: 'ICU Ward', bedNumber: 'ICU-102', bedType: 'ICU', status: 'available' },
    { wardName: 'ICU Ward', bedNumber: 'ICU-103', bedType: 'ICU', status: 'available' },
    { wardName: 'General Ward A', bedNumber: 'GEN-201', bedType: 'General', status: 'available' },
    { wardName: 'General Ward A', bedNumber: 'GEN-202', bedType: 'General', status: 'available' },
    { wardName: 'General Ward A', bedNumber: 'GEN-203', bedType: 'General', status: 'available' },
    { wardName: 'General Ward A', bedNumber: 'GEN-204', bedType: 'General', status: 'available' },
    { wardName: 'General Ward B', bedNumber: 'GEN-301', bedType: 'General', status: 'available' },
    { wardName: 'General Ward B', bedNumber: 'GEN-302', bedType: 'General', status: 'available' },
    { wardName: 'Private Room', bedNumber: 'PVT-401', bedType: 'Private', status: 'available' },
    { wardName: 'Private Room', bedNumber: 'PVT-402', bedType: 'Private', status: 'available' },
    { wardName: 'Private Room', bedNumber: 'PVT-403', bedType: 'Private', status: 'available' },
    { wardName: 'Emergency', bedNumber: 'EMR-501', bedType: 'Emergency', status: 'available' },
    { wardName: 'Emergency', bedNumber: 'EMR-502', bedType: 'Emergency', status: 'available' },
    { wardName: 'Semi-Private Ward', bedNumber: 'SP-301', bedType: 'Semi-Private', status: 'available' },
    { wardName: 'Semi-Private Ward', bedNumber: 'SP-302', bedType: 'Semi-Private', status: 'available' }
  ];

  await Bed.deleteMany({});
  const createdBeds = await Bed.insertMany(beds);
  console.log(`✅ Created ${createdBeds.length} beds`);
  return createdBeds;
};

// Seed Admitted Patients (matching MySQL data)
const seedAdmittedPatients = async (beds) => {
  console.log('🌱 Seeding Admitted Patients...');
  
  const patients = [
    {
      patientName: 'Sarah Johnson',
      phone: '555-0101',
      email: 'sarah.j@email.com',
      bloodGroup: 'A+',
      disease: 'Pneumonia',
      address: '123 Main St',
      status: 'moderate',
      priority: 'moderate',
      bed: beds[3]._id, // GEN-201
      admissionDate: new Date()
    },
    {
      patientName: 'Michael Chen',
      phone: '555-0102',
      email: 'michael.c@email.com',
      bloodGroup: 'O-',
      disease: 'Appendicitis',
      address: '456 Oak Ave',
      status: 'critical',
      priority: 'critical',
      bed: beds[0]._id, // ICU-101
      admissionDate: new Date()
    },
    {
      patientName: 'Emily Williams',
      phone: '555-0103',
      email: 'emily.w@email.com',
      bloodGroup: 'B+',
      disease: 'Fractured Leg',
      address: '789 Pine Rd',
      status: 'stable',
      priority: 'stable',
      bed: beds[4]._id, // GEN-202
      admissionDate: new Date()
    },
    {
      patientName: 'David Brown',
      phone: '555-0104',
      email: 'david.b@email.com',
      bloodGroup: 'AB+',
      disease: 'Heart Attack',
      address: '321 Elm St',
      status: 'critical',
      priority: 'critical',
      bed: beds[1]._id, // ICU-102
      admissionDate: new Date()
    },
    {
      patientName: 'Lisa Anderson',
      phone: '555-0105',
      email: 'lisa.a@email.com',
      bloodGroup: 'A-',
      disease: 'Diabetes Complications',
      address: '654 Maple Dr',
      status: 'moderate',
      priority: 'moderate',
      bed: beds[5]._id, // GEN-203
      admissionDate: new Date()
    }
  ];

  await AdmittedPatient.deleteMany({});
  const createdPatients = await AdmittedPatient.insertMany(patients);
  console.log(`✅ Created ${createdPatients.length} admitted patients`);
  
  // Update bed status
  for (const patient of createdPatients) {
    if (patient.bed) {
      await Bed.findByIdAndUpdate(patient.bed, {
        status: 'occupied',
        admittedDate: patient.admissionDate
      });
    }
  }
  console.log('✅ Updated bed statuses');
};

// Main seed function
const seedDatabase = async () => {
  try {
    await connectDB();
    
    console.log('\n🌱 Starting database seeding...\n');
    
    const users = await seedUsers();
    await seedSymptomKeywords();
    const beds = await seedBeds();
    await seedAdmittedPatients(beds);
    
    console.log('\n✅ Database seeding completed successfully!\n');
    console.log('📝 Login Credentials:');
    console.log('   Admin: admin@hospilink.com / password123');
    console.log('   Doctor: dr.patel@hospilink.com / password123');
    console.log('   Patient: patient@hospilink.com / password123');
    console.log('   Staff: staff@hospilink.com / password123');
    console.log('   Nurse: nurse@hospilink.com / password123\n');
    
    process.exit(0);
  } catch (error) {
    console.error('❌ Seeding error:', error);
    process.exit(1);
  }
};

// Run seeding
seedDatabase();
