const axios = require('axios');

const API_URL = 'http://localhost:5000/api';
let adminToken = '';
let doctorToken = '';
let patientToken = '';

const colors = {
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[36m',
  reset: '\x1b[0m'
};

const log = {
  success: (msg) => console.log(`${colors.green}✓${colors.reset} ${msg}`),
  error: (msg) => console.log(`${colors.red}✗${colors.reset} ${msg}`),
  info: (msg) => console.log(`${colors.blue}ℹ${colors.reset} ${msg}`),
  warn: (msg) => console.log(`${colors.yellow}⚠${colors.reset} ${msg}`)
};

async function testHealthCheck() {
  try {
    const response = await axios.get(`${API_URL}/health`);
    if (response.data.success) {
      log.success('Health check passed');
      return true;
    }
  } catch (error) {
    log.error(`Health check failed: ${error.message}`);
    return false;
  }
}

async function testLogin(email, password, role) {
  try {
    const response = await axios.post(`${API_URL}/auth/login`, {
      email,
      password
    });
    
    if (response.data.success && response.data.token) {
      log.success(`Login successful: ${role} (${email})`);
      log.info(`  User: ${response.data.user.firstName} ${response.data.user.lastName}`);
      log.info(`  Role: ${response.data.user.role}`);
      return response.data.token;
    }
  } catch (error) {
    log.error(`Login failed for ${role}: ${error.response?.data?.message || error.message}`);
    return null;
  }
}

async function testGetProfile(token, role) {
  try {
    const response = await axios.get(`${API_URL}/auth/me`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.data.success) {
      log.success(`Get profile successful: ${role}`);
      return true;
    }
  } catch (error) {
    log.error(`Get profile failed for ${role}: ${error.response?.data?.message || error.message}`);
    return false;
  }
}

async function testRegister() {
  try {
    const testUser = {
      firstName: 'Test',
      lastName: 'Patient',
      email: `test.patient.${Date.now()}@hospilink.com`,
      password: 'password123',
      phone: '9876543220',
      role: 'patient',
      gender: 'Male'
    };
    
    const response = await axios.post(`${API_URL}/auth/register`, testUser);
    
    if (response.data.success && response.data.token) {
      log.success('Registration successful');
      log.info(`  Email: ${testUser.email}`);
      return true;
    }
  } catch (error) {
    log.error(`Registration failed: ${error.response?.data?.message || error.message}`);
    return false;
  }
}

async function testGetAppointments(token) {
  try {
    const response = await axios.get(`${API_URL}/appointments`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.data.success) {
      log.success(`Get appointments successful (${response.data.appointments?.length || 0} found)`);
      return true;
    }
  } catch (error) {
    log.error(`Get appointments failed: ${error.response?.data?.message || error.message}`);
    return false;
  }
}

async function testCreateAppointment(token) {
  try {
    const appointment = {
      fullName: 'John Test',
      email: 'john.test@email.com',
      gender: 'Male',
      phone: '9999888877',
      appointmentDate: new Date(Date.now() + 86400000).toISOString(),
      symptoms: 'Fever and cough for 3 days',
      priorityLevel: 'medium'
    };
    
    const response = await axios.post(`${API_URL}/appointments`, appointment, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.data.success) {
      log.success('Create appointment successful');
      log.info(`  Appointment ID: ${response.data.appointment._id}`);
      log.info(`  Priority: ${response.data.appointment.priorityLevel}`);
      return response.data.appointment._id;
    }
  } catch (error) {
    log.error(`Create appointment failed: ${error.response?.data?.message || error.message}`);
    return null;
  }
}

async function testGetBeds(token) {
  try {
    const response = await axios.get(`${API_URL}/beds`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.data.success) {
      const available = response.data.beds?.filter(b => b.status === 'available').length || 0;
      log.success(`Get beds successful (${available} available)`);
      return response.data.beds;
    }
  } catch (error) {
    log.error(`Get beds failed: ${error.response?.data?.message || error.message}`);
    return null;
  }
}

async function testGetDoctors(token) {
  try {
    const response = await axios.get(`${API_URL}/doctors`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.data.success) {
      log.success(`Get doctors successful (${response.data.doctors?.length || 0} found)`);
      return true;
    }
  } catch (error) {
    log.error(`Get doctors failed: ${error.response?.data?.message || error.message}`);
    return false;
  }
}

async function testAdminStats(token) {
  try {
    const response = await axios.get(`${API_URL}/admin/stats`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.data.success) {
      log.success('Admin stats successful');
      log.info(`  Total Users: ${response.data.totalUsers}`);
      log.info(`  Total Appointments: ${response.data.totalAppointments}`);
      return true;
    }
  } catch (error) {
    log.error(`Admin stats failed: ${error.response?.data?.message || error.message}`);
    return false;
  }
}

async function runTests() {
  console.log('\n' + '='.repeat(60));
  console.log('🧪 HospiLink MERN API Test Suite');
  console.log('='.repeat(60) + '\n');

  let passed = 0;
  let failed = 0;

  // Test 1: Health Check
  log.info('Test 1: Health Check');
  if (await testHealthCheck()) passed++; else failed++;
  console.log();

  // Test 2: Login - Admin
  log.info('Test 2: Login - Admin');
  adminToken = await testLogin('admin@hospilink.com', 'password123', 'Admin');
  if (adminToken) passed++; else failed++;
  console.log();

  // Test 3: Login - Doctor
  log.info('Test 3: Login - Doctor');
  doctorToken = await testLogin('dr.patel@hospilink.com', 'password123', 'Doctor');
  if (doctorToken) passed++; else failed++;
  console.log();

  // Test 4: Login - Patient
  log.info('Test 4: Login - Patient');
  patientToken = await testLogin('patient@hospilink.com', 'password123', 'Patient');
  if (patientToken) passed++; else failed++;
  console.log();

  // Test 5: Register New Patient
  log.info('Test 5: Register New Patient');
  if (await testRegister()) passed++; else failed++;
  console.log();

  if (patientToken) {
    // Test 6: Get Profile - Patient
    log.info('Test 6: Get Profile - Patient');
    if (await testGetProfile(patientToken, 'Patient')) passed++; else failed++;
    console.log();

    // Test 7: Get Appointments
    log.info('Test 7: Get Appointments - Patient');
    if (await testGetAppointments(patientToken)) passed++; else failed++;
    console.log();

    // Test 8: Create Appointment
    log.info('Test 8: Create Appointment');
    if (await testCreateAppointment(patientToken)) passed++; else failed++;
    console.log();

    // Test 9: Get Doctors
    log.info('Test 9: Get Doctors');
    if (await testGetDoctors(patientToken)) passed++; else failed++;
    console.log();
  }

  if (adminToken) {
    // Test 10: Get Beds - Admin
    log.info('Test 10: Get Beds - Admin');
    if (await testGetBeds(adminToken)) passed++; else failed++;
    console.log();

    // Test 11: Admin Stats
    log.info('Test 11: Admin Stats');
    if (await testAdminStats(adminToken)) passed++; else failed++;
    console.log();
  }

  // Results Summary
  console.log('='.repeat(60));
  console.log('📊 Test Results Summary');
  console.log('='.repeat(60));
  console.log(`${colors.green}Passed: ${passed}${colors.reset}`);
  console.log(`${colors.red}Failed: ${failed}${colors.reset}`);
  console.log(`Total: ${passed + failed}`);
  console.log('='.repeat(60) + '\n');

  if (failed === 0) {
    log.success('All tests passed! ✨');
  } else {
    log.warn(`${failed} test(s) failed. Please check the errors above.`);
  }
}

// Run tests
runTests().catch(error => {
  log.error(`Test suite failed: ${error.message}`);
  process.exit(1);
});
