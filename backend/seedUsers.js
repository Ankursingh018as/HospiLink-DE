const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
require('dotenv').config();

// Connect to MongoDB
mongoose.connect(process.env.MONGODB_URI)
  .then(() => console.log('✅ Connected to MongoDB'))
  .catch(err => {
    console.error('❌ MongoDB connection error:', err);
    process.exit(1);
  });

// User Schema (inline for seeding)
const userSchema = new mongoose.Schema({
  firstName: String,
  lastName: String,
  email: { type: String, unique: true },
  password: String,
  phone: String,
  role: String,
  specialization: String,
  department: String,
  licenseNumber: String,
  staffId: String,
  dateOfBirth: Date,
  gender: String,
  isActive: { type: Boolean, default: true },
  status: { type: String, default: 'active' }
}, { timestamps: true });

const User = mongoose.models.User || mongoose.model('User', userSchema);

// Test users matching PHP system structure
const testUsers = [
  {
    firstName: 'John',
    lastName: 'Patient',
    email: 'patient@test.com',
    password: 'password123',
    phone: '1234567890',
    role: 'patient',
    gender: 'Male',
    dateOfBirth: new Date('1990-01-01')
  },
  {
    firstName: 'Dr. Sarah',
    lastName: 'Doctor',
    email: 'doctor@test.com',
    password: 'password123',
    phone: '1234567891',
    role: 'doctor',
    specialization: 'General Medicine',
    department: 'General',
    licenseNumber: 'DOC12345',
    gender: 'Female',
    dateOfBirth: new Date('1985-05-15')
  },
  {
    firstName: 'Admin',
    lastName: 'User',
    email: 'admin@test.com',
    password: 'password123',
    phone: '1234567892',
    role: 'admin',
    gender: 'Male',
    dateOfBirth: new Date('1980-03-20')
  },
  {
    firstName: 'Nurse',
    lastName: 'Staff',
    email: 'staff@test.com',
    password: 'password123',
    phone: '1234567893',
    role: 'staff',
    department: 'General',
    staffId: 'STAFF001',
    gender: 'Female',
    dateOfBirth: new Date('1992-07-10')
  }
];

async function seedUsers() {
  try {
    console.log('🌱 Starting user seeding...');
    
    // Clear existing users (optional - comment out if you want to keep existing users)
    // await User.deleteMany({});
    // console.log('🗑️  Cleared existing users');

    for (const userData of testUsers) {
      // Check if user already exists
      const existing = await User.findOne({ email: userData.email });
      
      if (existing) {
        console.log(`⚠️  User ${userData.email} already exists, skipping...`);
        continue;
      }

      // Hash password
      const salt = await bcrypt.genSalt(10);
      userData.password = await bcrypt.hash(userData.password, salt);

      // Create user
      const user = await User.create(userData);
      console.log(`✅ Created user: ${user.email} (${user.role})`);
    }

    console.log('\n✨ Seeding completed successfully!');
    console.log('\n📝 Test Credentials:');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('Patient:  patient@test.com / password123');
    console.log('Doctor:   doctor@test.com  / password123');
    console.log('Admin:    admin@test.com   / password123');
    console.log('Staff:    staff@test.com   / password123');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

    process.exit(0);
  } catch (error) {
    console.error('❌ Seeding error:', error);
    process.exit(1);
  }
}

seedUsers();
