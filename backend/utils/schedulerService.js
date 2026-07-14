const cron = require('node-cron');
const PatientIV = require('../models/PatientIV');
const PatientMedicine = require('../models/PatientMedicine');
const PatientAdmission = require('../models/PatientAdmission');
const Appointment = require('../models/Appointment');
const User = require('../models/User');
const notificationService = require('./notificationService');

let schedulerInitialized = false;

const initScheduler = () => {
  if (schedulerInitialized) return;
  schedulerInitialized = true;

  console.log('⏰ HospiLink Notification Scheduler starting...');

  // ─────────────────────────────────────────────────────────────
  //  JOB 1: IV DRIP REMINDERS (every 15 minutes)
  //  Notify nurses when a drip will end within 30 minutes
  // ─────────────────────────────────────────────────────────────
  cron.schedule('*/15 * * * *', async () => {
    console.log('🔄 [Scheduler] Checking IV drips...');
    try {
      const now = new Date();
      const thirtyMinutesLater = new Date(now.getTime() + 30 * 60 * 1000);

      const expiringDrips = await PatientIV.find({
        status: 'running',
        expectedEndAt: { $gte: now, $lte: thirtyMinutesLater }
      }).populate({
        path: 'admission',
        populate: [
          { path: 'patient', select: 'firstName lastName' },
          { path: 'bed', select: 'bedNumber ward' }
        ]
      }).populate('startedBy', 'firstName lastName email role staffProfile');

      for (const drip of expiringDrips) {
        // Find nurses/staff on current shift to notify
        const nurses = await User.find({
          role: 'staff',
          status: 'active'
        }).select('firstName lastName email staffProfile');

        for (const nurse of nurses) {
          await notificationService.sendDripReminder(drip, nurse);
        }
      }

      if (expiringDrips.length > 0) {
        console.log(`✅ [Scheduler] Processed ${expiringDrips.length} expiring drip(s)`);
      }
    } catch (error) {
      console.error('❌ [Scheduler] IV drip check error:', error.message);
    }
  });

  // ─────────────────────────────────────────────────────────────
  //  JOB 2: MEDICINE REMINDERS (every 30 minutes)
  //  Notify patients about their upcoming medicine doses
  // ─────────────────────────────────────────────────────────────
  cron.schedule('*/30 * * * *', async () => {
    console.log('🔄 [Scheduler] Checking medicine schedules...');
    try {
      const now = new Date();
      const hourLater = new Date(now.getTime() + 60 * 60 * 1000);

      // Get active medicines for admitted patients
      const activeMedicines = await PatientMedicine.find({
        status: 'active',
        startDate: { $lte: hourLater },
        $or: [
          { endDate: null },
          { endDate: { $gte: now } }
        ]
      }).populate({
        path: 'admission',
        populate: { path: 'patient', select: 'firstName lastName email role' }
      });

      for (const medicine of activeMedicines) {
        const patient = medicine.admission?.patient;
        if (!patient) continue;

        // Parse frequency to determine if dose is due
        const isDue = isMedicineDueNow(medicine.frequency, medicine.startDate);
        if (isDue) {
          await notificationService.sendMedicineReminder(medicine, patient);
        }
      }

      if (activeMedicines.length > 0) {
        console.log(`✅ [Scheduler] Processed ${activeMedicines.length} medicine record(s)`);
      }
    } catch (error) {
      console.error('❌ [Scheduler] Medicine check error:', error.message);
    }
  });

  // ─────────────────────────────────────────────────────────────
  //  JOB 3: ROUTINE CHECK REMINDERS (every 6 hours)
  //  Notify doctors about patients without recent vitals check
  // ─────────────────────────────────────────────────────────────
  cron.schedule('0 */6 * * *', async () => {
    console.log('🔄 [Scheduler] Checking routine vitals status...');
    try {
      const sixHoursAgo = new Date(Date.now() - 6 * 60 * 60 * 1000);

      const admissions = await PatientAdmission.find({
        status: 'active',
        $or: [
          { 'vitalSigns.heartRate': { $exists: false } },
          { updatedAt: { $lt: sixHoursAgo } }
        ]
      }).populate('patient', 'firstName lastName')
        .populate('assignedDoctor', 'firstName lastName email')
        .populate('bed', 'bedNumber ward');

      // Group by doctor
      const doctorPatientMap = {};
      for (const admission of admissions) {
        if (!admission.assignedDoctor) continue;
        const docId = admission.assignedDoctor._id.toString();
        if (!doctorPatientMap[docId]) {
          doctorPatientMap[docId] = {
            doctor: admission.assignedDoctor,
            patients: []
          };
        }
        const hoursWithoutCheck = Math.round((Date.now() - new Date(admission.updatedAt)) / (1000 * 60 * 60));
        doctorPatientMap[docId].patients.push({
          patientName: `${admission.patient?.firstName} ${admission.patient?.lastName}`,
          bedNumber: admission.bed?.bedNumber,
          admissionReason: admission.admissionReason,
          hoursWithoutCheck,
          urgency: hoursWithoutCheck >= 12 ? 'urgent' : 'high'
        });
      }

      for (const entry of Object.values(doctorPatientMap)) {
        await notificationService.sendRoutineCheckReminder(entry.doctor, entry.patients);
      }

      if (admissions.length > 0) {
        console.log(`✅ [Scheduler] Routine check: notified doctors for ${admissions.length} admission(s)`);
      }
    } catch (error) {
      console.error('❌ [Scheduler] Routine check error:', error.message);
    }
  });

  // ─────────────────────────────────────────────────────────────
  //  JOB 4: FOLLOW-UP REMINDERS (daily at 9 AM IST)
  //  Notify doctor + patient when appointment was completed 7 days ago
  // ─────────────────────────────────────────────────────────────
  cron.schedule('30 3 * * *', async () => { // 9:00 AM IST = 3:30 AM UTC
    console.log('🔄 [Scheduler] Checking follow-up reminders...');
    try {
      const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
      const eightDaysAgo = new Date(Date.now() - 8 * 24 * 60 * 60 * 1000);

      const completedAppointments = await Appointment.find({
        status: 'completed',
        updatedAt: { $gte: eightDaysAgo, $lte: sevenDaysAgo }
      }).populate('patient', 'firstName lastName email phone role')
        .populate('doctor', 'firstName lastName email doctorProfile');

      for (const appointment of completedAppointments) {
        await notificationService.sendFollowUpReminder(appointment);
      }

      if (completedAppointments.length > 0) {
        console.log(`✅ [Scheduler] Follow-up: processed ${completedAppointments.length} appointment(s)`);
      }
    } catch (error) {
      console.error('❌ [Scheduler] Follow-up check error:', error.message);
    }
  });

  // ─────────────────────────────────────────────────────────────
  //  JOB 5: APPOINTMENT REMINDERS (daily at 6 PM IST)
  //  Notify patients about tomorrow's appointments
  // ─────────────────────────────────────────────────────────────
  cron.schedule('30 12 * * *', async () => { // 6:00 PM IST = 12:30 PM UTC
    console.log('🔄 [Scheduler] Checking tomorrow\'s appointments...');
    try {
      const tomorrowStart = new Date();
      tomorrowStart.setDate(tomorrowStart.getDate() + 1);
      tomorrowStart.setHours(0, 0, 0, 0);

      const tomorrowEnd = new Date(tomorrowStart);
      tomorrowEnd.setHours(23, 59, 59, 999);

      const tomorrowAppointments = await Appointment.find({
        status: { $in: ['pending', 'confirmed'] },
        createdAt: { $gte: tomorrowStart, $lte: tomorrowEnd }
      }).populate('patient', 'firstName lastName email role')
        .populate('doctor', 'firstName lastName doctorProfile')
        .populate('slot');

      for (const appointment of tomorrowAppointments) {
        await notificationService.sendAppointmentReminder(appointment);
      }

      if (tomorrowAppointments.length > 0) {
        console.log(`✅ [Scheduler] Appointment reminders sent for ${tomorrowAppointments.length} appointment(s)`);
      }
    } catch (error) {
      console.error('❌ [Scheduler] Appointment reminder error:', error.message);
    }
  });

  // ─────────────────────────────────────────────────────────────
  //  JOB 6: ADMIN DAILY DIGEST (daily at 8 AM IST)
  // ─────────────────────────────────────────────────────────────
  cron.schedule('30 2 * * *', async () => { // 8:00 AM IST = 2:30 AM UTC
    console.log('🔄 [Scheduler] Sending admin daily digest...');
    try {
      const admins = await User.find({ role: 'admin', status: 'active' })
        .select('firstName lastName email');

      if (!admins.length) return;

      // Gather stats
      const [
        activeAdmissions,
        availableBeds,
        runningDrips,
        activeMedicines,
        todayAppointments,
        activeDoctors,
        activeStaff
      ] = await Promise.all([
        require('../models/PatientAdmission').countDocuments({ status: 'active' }),
        require('../models/Bed').countDocuments({ status: 'available' }),
        require('../models/PatientIV').countDocuments({ status: 'running' }),
        require('../models/PatientMedicine').countDocuments({ status: 'active' }),
        Appointment.countDocuments({
          createdAt: {
            $gte: new Date(new Date().setHours(0, 0, 0, 0)),
            $lte: new Date(new Date().setHours(23, 59, 59, 999))
          }
        }),
        User.countDocuments({ role: 'doctor', status: 'active' }),
        User.countDocuments({ role: 'staff', status: 'active' })
      ]);

      const stats = {
        activeAdmissions,
        availableBeds,
        runningDrips,
        activeMedicines,
        todayAppointments,
        activeDoctors,
        activeStaff
      };

      for (const admin of admins) {
        await notificationService.sendAdminDailyDigest(admin, stats);
      }

      console.log(`✅ [Scheduler] Daily digest sent to ${admins.length} admin(s)`);
    } catch (error) {
      console.error('❌ [Scheduler] Admin digest error:', error.message);
    }
  });

  console.log('✅ HospiLink Notification Scheduler initialized — 6 jobs running');
};

// ─────────────────────────────────────────────────────────────────
//  HELPER: Check if medicine is due now based on frequency
// ─────────────────────────────────────────────────────────────────
const isMedicineDueNow = (frequency, startDate) => {
  const now = new Date();
  const start = new Date(startDate);
  const hoursSinceStart = (now - start) / (1000 * 60 * 60);

  const freqMap = {
    'once daily': 24,
    'twice daily': 12,
    'three times daily': 8,
    'four times daily': 6,
    'every 4 hours': 4,
    'every 6 hours': 6,
    'every 8 hours': 8,
    'every 12 hours': 12,
    'as needed': null // Skip PRN medicines
  };

  const freqLower = (frequency || '').toLowerCase();
  let intervalHours = null;

  for (const [key, val] of Object.entries(freqMap)) {
    if (freqLower.includes(key)) {
      intervalHours = val;
      break;
    }
  }

  if (!intervalHours) return false;

  // Check if we're within 30 minutes of a dose time
  const remainder = hoursSinceStart % intervalHours;
  const minutesToDose = (intervalHours - remainder) * 60;
  return minutesToDose <= 30 && minutesToDose >= 0;
};

module.exports = { initScheduler };
