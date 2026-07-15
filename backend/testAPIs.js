const axios = require('axios');

const API_BASE = 'http://localhost:5000/api';
let authToken = '';
let testPatientId = '';
let testAppointmentId = '';
let testBedId = '';

// Color codes for terminal output
const colors = {
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  reset: '\x1b[0m'
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

async function testHealthCheck() {
  log('\n[DEBUG] Testing Health Check...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/health`);
    log(`[SUCCESS] Health Check: ${response.data.message}`, 'green');
    return true;
  } catch (error) {
    log(`[ERROR] Health Check Failed: ${error.message}`, 'red');
    return false;
  }
}

async function testLogin() {
  log('\n[AUTH] Testing Login...', 'blue');
  try {
    const response = await axios.post(`${API_BASE}/auth/login`, {
      email: 'admin@hospilink.com',
      password: 'password123'
    });
    
    if (response.data.success && response.data.token) {
      authToken = response.data.token;
      log(`[SUCCESS] Login Successful: ${response.data.user.firstName} ${response.data.user.lastName} (${response.data.user.role})`, 'green');
      return true;
    } else {
      log('[ERROR] Login Failed: No token received', 'red');
      return false;
    }
  } catch (error) {
    log(`[ERROR] Login Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testPatientLogin() {
  log('\n[USER] Testing Patient Login...', 'blue');
  try {
    const response = await axios.post(`${API_BASE}/auth/login`, {
      email: 'patient@hospilink.com',
      password: 'password123'
    });
    
    if (response.data.success && response.data.token) {
      testPatientId = response.data.user.id;
      log(`[SUCCESS] Patient Login Successful: ${response.data.user.firstName} ${response.data.user.lastName}`, 'green');
      log(`   Patient ID: ${testPatientId}`, 'yellow');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Patient Login Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testRegister() {
  log('\n[INFO] Testing Registration...', 'blue');
  try {
    const randomEmail = `testuser${Date.now()}@test.com`;
    const response = await axios.post(`${API_BASE}/auth/register`, {
      firstName: 'Test',
      lastName: 'User',
      email: randomEmail,
      password: 'test123456',
      phone: '9876543210',
      role: 'patient',
      gender: 'Male'
    });
    
    if (response.data.success) {
      log(`[SUCCESS] Registration Successful: ${randomEmail}`, 'green');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Registration Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testGetProfile() {
  log('\n[USER] Testing Get Profile...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/auth/me`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success) {
      log(`[SUCCESS] Profile Retrieved: ${response.data.user.firstName} ${response.data.user.lastName}`, 'green');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Get Profile Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testGetBeds() {
  log('\n[BED]  Testing Get Available Beds...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/beds?status=available`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success && response.data.beds) {
      log(`[SUCCESS] Retrieved ${response.data.beds.length} available beds`, 'green');
      if (response.data.beds.length > 0) {
        testBedId = response.data.beds[0]._id;
        log(`   Sample Bed: ${response.data.beds[0].wardName} - ${response.data.beds[0].bedNumber}`, 'yellow');
      }
      return true;
    }
  } catch (error) {
    log(`[ERROR] Get Beds Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testGetDoctors() {
  log('\n👨‍⚕️  Testing Get Doctors...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/doctors`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success && response.data.doctors) {
      log(`[SUCCESS] Retrieved ${response.data.doctors.length} doctors`, 'green');
      if (response.data.doctors.length > 0) {
        log(`   Sample: Dr. ${response.data.doctors[0].firstName} ${response.data.doctors[0].lastName} - ${response.data.doctors[0].specialization}`, 'yellow');
      }
      return true;
    }
  } catch (error) {
    log(`[ERROR] Get Doctors Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testCreateAppointment() {
  log('\n[DATE] Testing Create Appointment...', 'blue');
  try {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const response = await axios.post(`${API_BASE}/appointments`, {
      fullName: 'Test Patient',
      email: 'test@patient.com',
      gender: 'Male',
      phone: '9999999999',
      appointmentDate: tomorrow.toISOString(),
      appointmentTime: '10:00 AM',
      symptoms: 'fever, headache, body ache'
    }, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success && response.data.appointment) {
      testAppointmentId = response.data.appointment._id;
      log(`[SUCCESS] Appointment Created: ID ${testAppointmentId}`, 'green');
      log(`   Priority: ${response.data.appointment.priorityLevel} (Score: ${response.data.appointment.priorityScore})`, 'yellow');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Create Appointment Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testGetAppointments() {
  log('\n[INFO] Testing Get Appointments...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/appointments`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success) {
      log(`[SUCCESS] Retrieved ${response.data.appointments?.length || 0} appointments`, 'green');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Get Appointments Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testUpdateAppointment() {
  if (!testAppointmentId) {
    log('[WARNING]  Skipping Update Appointment (no appointment ID)', 'yellow');
    return false;
  }
  
  log('\n✏️  Testing Update Appointment...', 'blue');
  try {
    const response = await axios.put(`${API_BASE}/appointments/${testAppointmentId}`, {
      status: 'confirmed',
      doctorNotes: 'Patient confirmed for appointment'
    }, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success) {
      log(`[SUCCESS] Appointment Updated: Status changed to confirmed`, 'green');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Update Appointment Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testGetAdmittedPatients() {
  log('\n[HOSPITAL] Testing Get Admitted Patients...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/patients?status=admitted`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success) {
      log(`[SUCCESS] Retrieved ${response.data.patients?.length || 0} admitted patients`, 'green');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Get Admitted Patients Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testGetStats() {
  log('\n[STATS] Testing Get Admin Stats...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/admin/stats`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success) {
      log(`[SUCCESS] Stats Retrieved Successfully`, 'green');
      const stats = response.data.stats;
      if (stats) {
        log(`   Total Users: ${stats.totalUsers || 0}`, 'yellow');
        log(`   Total Appointments: ${stats.totalAppointments || 0}`, 'yellow');
        log(`   Total Beds: ${stats.totalBeds || 0}`, 'yellow');
      }
      return true;
    }
  } catch (error) {
    log(`[ERROR] Get Stats Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function testActivityLogs() {
  log('\n📜 Testing Get Activity Logs...', 'blue');
  try {
    const response = await axios.get(`${API_BASE}/admin/logs`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    
    if (response.data.success) {
      log(`[SUCCESS] Retrieved ${response.data.logs?.length || 0} activity logs`, 'green');
      return true;
    }
  } catch (error) {
    log(`[ERROR] Get Activity Logs Failed: ${error.response?.data?.message || error.message}`, 'red');
    return false;
  }
}

async function runAllTests() {
  console.clear();
  log('╔══════════════════════════════════════════════════════════╗', 'blue');
  log('║       HospiLink MERN API - Comprehensive Test Suite     ║', 'blue');
  log('╚══════════════════════════════════════════════════════════╝', 'blue');
  
  const results = {
    passed: 0,
    failed: 0,
    total: 0
  };
  
  const tests = [
    { name: 'Health Check', fn: testHealthCheck },
    { name: 'Login (Admin)', fn: testLogin },
    { name: 'Patient Login', fn: testPatientLogin },
    { name: 'Register New User', fn: testRegister },
    { name: 'Get Profile', fn: testGetProfile },
    { name: 'Get Available Beds', fn: testGetBeds },
    { name: 'Get Doctors', fn: testGetDoctors },
    { name: 'Create Appointment', fn: testCreateAppointment },
    { name: 'Get Appointments', fn: testGetAppointments },
    { name: 'Update Appointment', fn: testUpdateAppointment },
    { name: 'Get Admitted Patients', fn: testGetAdmittedPatients },
    { name: 'Get Admin Stats', fn: testGetStats },
    { name: 'Get Activity Logs', fn: testActivityLogs }
  ];
  
  for (const test of tests) {
    results.total++;
    const passed = await test.fn();
    if (passed) {
      results.passed++;
    } else {
      results.failed++;
    }
    await new Promise(resolve => setTimeout(resolve, 500)); // Small delay between tests
  }
  
  // Print summary
  log('\n╔══════════════════════════════════════════════════════════╗', 'blue');
  log('║                     TEST SUMMARY                         ║', 'blue');
  log('╚══════════════════════════════════════════════════════════╝', 'blue');
  log(`\nTotal Tests: ${results.total}`, 'blue');
  log(`[SUCCESS] Passed: ${results.passed}`, 'green');
  log(`[ERROR] Failed: ${results.failed}`, results.failed > 0 ? 'red' : 'green');
  log(`Success Rate: ${((results.passed / results.total) * 100).toFixed(1)}%\n`, results.failed > 0 ? 'yellow' : 'green');
  
  if (results.failed === 0) {
    log('[SUCCESS] All tests passed! MERN backend is working correctly!', 'green');
  } else {
    log('[WARNING]  Some tests failed. Check the errors above for details.', 'yellow');
  }
}

// Run tests
runAllTests().catch(error => {
  log(`\n💥 Test suite crashed: ${error.message}`, 'red');
  process.exit(1);
});
