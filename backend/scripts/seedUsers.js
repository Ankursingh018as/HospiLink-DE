const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../.env') });
const mongoose = require('mongoose');
const User = require('../models/User');

const seedUsers = async () => {
  try {
    // Connect to MongoDB
    const mongoUri = process.env.MONGODB_URI || process.env.MONGO_URI;
    await mongoose.connect(mongoUri, {
      useNewUrlParser: true,
      useUnifiedTopology: true
    });
    console.log('[SUCCESS] MongoDB Connected');

    // Clear existing users
    await User.deleteMany({});
    console.log('[CLEARED]  Cleared existing users');

    // Create test users matching PHP system
    const users = [
      {
        firstName: 'John',
        lastName: 'Doe',
        email: 'patient@test.com',
        password: 'password123',
        phone: '1234567890',
        role: 'patient',
        dateOfBirth: new Date('1990-01-15'),
        gender: 'male',
        address: '123 Main St, City, State',
        isActive: true
      },
      {
        firstName: 'Dr. Sarah',
        lastName: 'Smith',
        email: 'doctor@test.com',
        password: 'password123',
        phone: '9876543210',
        role: 'doctor',
        specialization: 'Cardiology',
        dateOfBirth: new Date('1985-05-20'),
        gender: 'female',
        isActive: true
      },
      {
        firstName: 'Admin',
        lastName: 'User',
        email: 'admin@test.com',
        password: 'password123',
        phone: '5555555555',
        role: 'admin',
        dateOfBirth: new Date('1980-03-10'),
        gender: 'male',
        isActive: true
      },
      {
        firstName: 'Staff',
        lastName: 'Member',
        email: 'staff@test.com',
        password: 'password123',
        phone: '4444444444',
        role: 'staff',
        staffId: 'STAFF001',
        dateOfBirth: new Date('1992-07-25'),
        gender: 'female',
        isActive: true
      },
      {
        firstName: 'Nurse',
        lastName: 'Joy',
        email: 'nurse@test.com',
        password: 'password123',
        phone: '3333333333',
        role: 'nurse',
        staffId: 'NURSE001',
        dateOfBirth: new Date('1988-11-30'),
        gender: 'female',
        isActive: true
      }
    ];

    // Insert users
    for (const userData of users) {
      const user = await User.create(userData);
      console.log(`[SUCCESS] Created ${user.role}: ${user.email}`);
    }

    console.log('\n[SUCCESS] All test users created successfully!\n');
    console.log('[INFO] Login Credentials:');
    console.log('-------------------');
    users.forEach(u => {
      console.log(`${u.role.toUpperCase()}: ${u.email} / password123`);
    });

    process.exit(0);
  } catch (error) {
    console.error('[ERROR] Error seeding users:', error);
    process.exit(1);
  }
};

seedUsers();
