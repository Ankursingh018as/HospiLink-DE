const Notification = require('../models/Notification');
const emailService = require('./emailService');
const { sendPushToUser } = require('./webPushService');
const User = require('../models/User');

// ─────────────────────────────────────────────────────────────────
//  CORE: Create & Send a notification (DB + Email + Push)
// ─────────────────────────────────────────────────────────────────

/**
 * Create a notification record in the database
 */
const createNotification = async (data) => {
  try {
    // Deduplicate: don't create the same notification twice in 1 hour
    if (data.deduplicationKey) {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      const existing = await Notification.findOne({
        deduplicationKey: data.deduplicationKey,
        createdAt: { $gte: oneHourAgo }
      });
      if (existing) return existing;
    }

    const notification = await Notification.create(data);
    return notification;
  } catch (error) {
    console.error('Create notification error:', error);
    return null;
  }
};

/**
 * Dispatch notification via email and/or web push
 */
const dispatchNotification = async (notification, emailFn, emailData, pushPayload) => {
  // Send email
  if (emailFn && emailData) {
    const user = await User.findById(notification.recipient).select('email');
    if (user?.email) {
      const result = await emailFn(user.email, emailData);
      if (result.success) {
        await Notification.findByIdAndUpdate(notification._id, {
          sentViaEmail: true,
          emailSentAt: new Date()
        });
      }
    }
  }

  // Send web push
  if (pushPayload) {
    const result = await sendPushToUser(notification.recipient, pushPayload);
    if (result.success && result.sent > 0) {
      await Notification.findByIdAndUpdate(notification._id, {
        sentViaPush: true,
        pushSentAt: new Date()
      });
    }
  }
};

// ─────────────────────────────────────────────────────────────────
//  DRIP REMINDER — Nurse/Staff
// ─────────────────────────────────────────────────────────────────
const sendDripReminder = async (ivRecord, nurse) => {
  try {
    const minutesRemaining = Math.round(
      (new Date(ivRecord.expectedEndAt) - Date.now()) / 60000
    );
    const patientAdmission = ivRecord.admission;
    const patient = patientAdmission?.patient;

    const priority = minutesRemaining <= 10 ? 'urgent' : minutesRemaining <= 20 ? 'high' : 'medium';
    const title = `[DRIP] IV Drip Alert — ${minutesRemaining} min remaining`;
    const message = `Patient ${patient?.firstName || ''} ${patient?.lastName || ''}'s ${ivRecord.fluidType} drip (${ivRecord.volumeMl}mL) ends in ${minutesRemaining} minutes. Please check immediately.`;

    const notification = await createNotification({
      recipient: nurse._id,
      recipientRole: 'staff',
      type: 'drip_reminder',
      title,
      message,
      priority,
      relatedEntity: { model: 'PatientIV', id: ivRecord._id },
      iconType: '[DRIP]',
      actionUrl: `/dashboards/staff_dashboard.php`,
      deduplicationKey: `drip-${ivRecord._id}-${nurse._id}-${Math.floor(Date.now() / (30 * 60 * 1000))}`
    });

    if (!notification) return;

    await dispatchNotification(
      notification,
      emailService.sendDripReminderEmail,
      {
        nurseName: `${nurse.firstName} ${nurse.lastName}`,
        patientName: `${patient?.firstName || ''} ${patient?.lastName || ''}`,
        patientBed: patientAdmission?.bed?.bedNumber,
        fluidType: ivRecord.fluidType,
        volumeMl: ivRecord.volumeMl,
        expectedEndAt: ivRecord.expectedEndAt,
        minutesRemaining
      },
      {
        title,
        body: message,
        tag: `drip-${ivRecord._id}`,
        type: 'drip_reminder',
        priority,
        actionUrl: '/dashboards/staff_dashboard.php',
        notificationId: notification._id.toString(),
        actions: [{ action: 'view', title: 'View Patient' }]
      }
    );

    console.log(`[SUCCESS] Drip reminder sent to nurse ${nurse.email}`);
  } catch (error) {
    console.error('Send drip reminder error:', error);
  }
};

// ─────────────────────────────────────────────────────────────────
//  MEDICINE REMINDER — Patient
// ─────────────────────────────────────────────────────────────────
const sendMedicineReminder = async (medicineRecord, patient) => {
  try {
    const title = `[MEDICINE] Medicine Reminder: ${medicineRecord.medicineName}`;
    const message = `Time to take ${medicineRecord.medicineName} (${medicineRecord.dosage}) — ${medicineRecord.frequency}`;

    const notification = await createNotification({
      recipient: patient._id,
      recipientRole: patient.role,
      type: 'medicine_reminder',
      title,
      message,
      priority: 'high',
      relatedEntity: { model: 'PatientMedicine', id: medicineRecord._id },
      iconType: '[MEDICINE]',
      actionUrl: '/dashboards/patient_dashboard.php',
      deduplicationKey: `med-${medicineRecord._id}-${Math.floor(Date.now() / (60 * 60 * 1000))}`
    });

    if (!notification) return;

    await dispatchNotification(
      notification,
      emailService.sendMedicineReminderEmail,
      {
        patientName: `${patient.firstName} ${patient.lastName}`,
        medicineName: medicineRecord.medicineName,
        dosage: medicineRecord.dosage,
        frequency: medicineRecord.frequency,
        route: medicineRecord.route,
        specialInstructions: medicineRecord.specialInstructions
      },
      {
        title,
        body: message,
        tag: `medicine-${medicineRecord._id}`,
        type: 'medicine_reminder',
        priority: 'high',
        actionUrl: '/dashboards/patient_dashboard.php',
        notificationId: notification._id.toString()
      }
    );

    console.log(`[SUCCESS] Medicine reminder sent to patient ${patient.email}`);
  } catch (error) {
    console.error('Send medicine reminder error:', error);
  }
};

// ─────────────────────────────────────────────────────────────────
//  ROUTINE CHECK REMINDER — Doctor
// ─────────────────────────────────────────────────────────────────
const sendRoutineCheckReminder = async (doctor, patients) => {
  try {
    if (!patients.length) return;

    const title = `[CHECK] Routine Check Required — ${patients.length} patient(s)`;
    const message = `${patients.length} patient(s) under your care have not had vitals checked in over 6 hours. Please review at your earliest.`;

    const notification = await createNotification({
      recipient: doctor._id,
      recipientRole: 'doctor',
      type: 'routine_check',
      title,
      message,
      priority: 'high',
      iconType: '[CHECK]',
      actionUrl: '/dashboards/doctor_dashboard.php',
      deduplicationKey: `routine-${doctor._id}-${new Date().toISOString().slice(0, 13)}` // once per hour
    });

    if (!notification) return;

    await dispatchNotification(
      notification,
      emailService.sendRoutineCheckEmail,
      {
        doctorName: `${doctor.firstName} ${doctor.lastName}`,
        patients
      },
      {
        title,
        body: message,
        tag: `routine-${doctor._id}`,
        type: 'routine_check',
        priority: 'high',
        actionUrl: '/dashboards/doctor_dashboard.php',
        notificationId: notification._id.toString(),
        actions: [{ action: 'view', title: 'View Patients' }]
      }
    );

    console.log(`[SUCCESS] Routine check reminder sent to Dr. ${doctor.email}`);
  } catch (error) {
    console.error('Send routine check error:', error);
  }
};

// ─────────────────────────────────────────────────────────────────
//  FOLLOW-UP REMINDER — Doctor + Patient
// ─────────────────────────────────────────────────────────────────
const sendFollowUpReminder = async (appointment) => {
  try {
    const patient = appointment.patient;
    const doctor = appointment.doctor;
    const daysSinceVisit = Math.round((Date.now() - new Date(appointment.updatedAt)) / (1000 * 60 * 60 * 24));

    // Notify doctor
    if (doctor) {
      const title = `[DATE] Follow-up: ${patient?.firstName} ${patient?.lastName}`;
      const message = `It's been ${daysSinceVisit} days since ${patient?.firstName}'s appointment. Consider scheduling a follow-up.`;

      const notification = await createNotification({
        recipient: doctor._id,
        recipientRole: 'doctor',
        type: 'followup_doctor',
        title,
        message,
        priority: 'medium',
        relatedEntity: { model: 'Appointment', id: appointment._id },
        iconType: '[DATE]',
        actionUrl: '/dashboards/doctor_dashboard.php',
        deduplicationKey: `followup-doc-${appointment._id}-${new Date().toISOString().slice(0, 10)}`
      });

      if (notification) {
        await dispatchNotification(
          notification,
          emailService.sendFollowUpDoctorEmail,
          {
            doctorName: `${doctor.firstName} ${doctor.lastName}`,
            patientName: `${patient?.firstName} ${patient?.lastName}`,
            appointmentDate: appointment.updatedAt,
            daysSinceVisit,
            patientEmail: patient?.email,
            patientPhone: patient?.phone
          },
          {
            title,
            body: message,
            tag: `followup-doc-${appointment._id}`,
            type: 'followup_doctor',
            priority: 'medium',
            actionUrl: '/dashboards/doctor_dashboard.php',
            notificationId: notification._id.toString()
          }
        );
      }
    }

    // Notify patient
    if (patient) {
      const title = `[DATE] Time for your follow-up with Dr. ${doctor?.firstName}`;
      const message = `It's been ${daysSinceVisit} days since your last visit. Please schedule a follow-up appointment.`;

      const notification = await createNotification({
        recipient: patient._id,
        recipientRole: patient.role,
        type: 'followup_patient',
        title,
        message,
        priority: 'medium',
        relatedEntity: { model: 'Appointment', id: appointment._id },
        iconType: '[DATE]',
        actionUrl: '/dashboards/patient_dashboard.php',
        deduplicationKey: `followup-pat-${appointment._id}-${new Date().toISOString().slice(0, 10)}`
      });

      if (notification) {
        await dispatchNotification(
          notification,
          emailService.sendFollowUpPatientEmail,
          {
            patientName: `${patient.firstName} ${patient.lastName}`,
            doctorName: `${doctor?.firstName} ${doctor?.lastName}`,
            lastVisitDate: appointment.updatedAt
          },
          {
            title,
            body: message,
            tag: `followup-pat-${appointment._id}`,
            type: 'followup_patient',
            priority: 'medium',
            actionUrl: '/dashboards/patient_dashboard.php',
            notificationId: notification._id.toString()
          }
        );
      }
    }

    console.log(`[SUCCESS] Follow-up reminders sent for appointment ${appointment._id}`);
  } catch (error) {
    console.error('Send follow-up reminder error:', error);
  }
};

// ─────────────────────────────────────────────────────────────────
//  APPOINTMENT REMINDER — Patient (day before)
// ─────────────────────────────────────────────────────────────────
const sendAppointmentReminder = async (appointment) => {
  try {
    const patient = appointment.patient;
    const doctor = appointment.doctor;
    const slot = appointment.slot;

    const title = `[DATE] Appointment Tomorrow: Dr. ${doctor?.firstName} ${doctor?.lastName}`;
    const message = `Reminder: Your appointment is tomorrow. Please arrive 15 minutes early with your patient ID.`;

    const notification = await createNotification({
      recipient: patient._id,
      recipientRole: patient.role,
      type: 'appointment_reminder',
      title,
      message,
      priority: 'medium',
      relatedEntity: { model: 'Appointment', id: appointment._id },
      iconType: '[DATE]',
      actionUrl: '/dashboards/patient_dashboard.php',
      deduplicationKey: `apt-reminder-${appointment._id}-${new Date().toISOString().slice(0, 10)}`
    });

    if (!notification) return;

    await dispatchNotification(
      notification,
      emailService.sendAppointmentReminderEmail,
      {
        patientName: `${patient.firstName} ${patient.lastName}`,
        doctorName: `${doctor?.firstName} ${doctor?.lastName}`,
        appointmentDate: slot?.date || appointment.createdAt,
        department: doctor?.doctorProfile?.department
      },
      {
        title,
        body: message,
        tag: `apt-${appointment._id}`,
        type: 'appointment_reminder',
        priority: 'medium',
        actionUrl: '/dashboards/patient_dashboard.php',
        notificationId: notification._id.toString()
      }
    );

    console.log(`[SUCCESS] Appointment reminder sent to ${patient.email}`);
  } catch (error) {
    console.error('Send appointment reminder error:', error);
  }
};

// ─────────────────────────────────────────────────────────────────
//  ADMIN DAILY DIGEST
// ─────────────────────────────────────────────────────────────────
const sendAdminDailyDigest = async (admin, stats) => {
  try {
    const title = `[HOSPITAL] Daily Digest — ${new Date().toLocaleDateString('en-IN')}`;
    const message = `System summary: ${stats.activeAdmissions} admissions, ${stats.todayAppointments} appointments today, ${stats.availableBeds} beds available.`;

    const notification = await createNotification({
      recipient: admin._id,
      recipientRole: 'admin',
      type: 'daily_digest',
      title,
      message,
      priority: 'low',
      iconType: '[HOSPITAL]',
      deduplicationKey: `digest-${admin._id}-${new Date().toISOString().slice(0, 10)}`
    });

    if (!notification) return;

    await dispatchNotification(
      notification,
      emailService.sendAdminDailyDigestEmail,
      {
        adminName: `${admin.firstName} ${admin.lastName}`,
        stats
      },
      null // No push for daily digest
    );

    console.log(`[SUCCESS] Daily digest sent to admin ${admin.email}`);
  } catch (error) {
    console.error('Send admin digest error:', error);
  }
};

module.exports = {
  createNotification,
  dispatchNotification,
  sendDripReminder,
  sendMedicineReminder,
  sendRoutineCheckReminder,
  sendFollowUpReminder,
  sendAppointmentReminder,
  sendAdminDailyDigest
};
