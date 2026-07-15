const nodemailer = require('nodemailer');

// Create reusable transporter
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST,
  port: process.env.SMTP_PORT,
  secure: false,
  auth: {
    user: process.env.SMTP_USERNAME,
    pass: process.env.SMTP_PASSWORD
  }
});

// Verify SMTP connection
transporter.verify((error, success) => {
  if (error) {
    console.log('[ERROR] SMTP connection error:', error.message);
  } else {
    console.log('[SUCCESS] SMTP Server is ready to send emails');
  }
});

// ═══════════════════════════════════════════
//  BASE HTML TEMPLATE (Clean & Minimalistic)
// ═══════════════════════════════════════════
const baseTemplate = (headerTitle, content) => `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #f8fafc;
      color: #334155;
      margin: 0;
      padding: 0;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper {
      max-width: 580px;
      margin: 40px auto;
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .header {
      padding: 32px 40px 20px 40px;
      border-bottom: 1px solid #f1f5f9;
    }
    .header-logo {
      font-size: 18px;
      font-weight: 700;
      color: #0d9488;
      letter-spacing: -0.5px;
    }
    .header-title {
      font-size: 20px;
      font-weight: 600;
      color: #0f172a;
      margin-top: 12px;
      margin-bottom: 0;
    }
    .body {
      padding: 32px 40px;
    }
    .body h2 {
      font-size: 18px;
      font-weight: 600;
      color: #0f172a;
      margin-top: 0;
      margin-bottom: 12px;
    }
    .body p {
      font-size: 15px;
      line-height: 1.6;
      color: #475569;
      margin-top: 0;
      margin-bottom: 16px;
    }
    .info-card {
      background-color: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 20px;
      margin: 24px 0;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      font-size: 14px;
    }
    .info-row:last-child {
      margin-bottom: 0;
    }
    .info-label {
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
    }
    .info-value {
      color: #0f172a;
      font-weight: 500;
      text-align: right;
    }
    .badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .badge-urgent { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
    .badge-high { background: #fff7ed; color: #9a3412; border: 1px solid #ffedd5; }
    .badge-medium { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
    .badge-low { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
    .footer {
      background-color: #f8fafc;
      padding: 24px 40px;
      border-top: 1px solid #e2e8f0;
      text-align: center;
    }
    .footer p {
      font-size: 12px;
      color: #64748b;
      line-height: 1.5;
      margin: 0;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <div class="header-logo">HospiLink</div>
      <div class="header-title">${headerTitle}</div>
    </div>
    <div class="body">
      ${content}
    </div>
    <div class="footer">
      <p>© 2026 HospiLink. Automated notification. Please do not reply.<br>Support: support@hospilink.com</p>
    </div>
  </div>
</body>
</html>`;

// ═══════════════════════════════════════════
//  ORIGINAL EMAIL FUNCTIONS (preserved)
// ═══════════════════════════════════════════

exports.sendAppointmentConfirmation = async (toEmail, data) => {
  try {
    const { patientName, doctorName, appointmentDate, symptoms, priorityLevel } = data;
    const content = `
      <h2>Hello ${patientName},</h2>
      <p>Your appointment has been successfully booked at HospiLink.</p>
      <div class="info-card ${priorityLevel}">
        <div class="info-row"><span class="info-label">Date & Time</span><span class="info-value">${new Date(appointmentDate).toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}</span></div>
        <div class="info-row"><span class="info-label">Doctor</span><span class="info-value">${doctorName || 'Will be assigned soon'}</span></div>
        <div class="info-row"><span class="info-label">Symptoms</span><span class="info-value">${symptoms}</span></div>
        <div class="info-row"><span class="info-label">Priority</span><span class="info-value"><span class="badge badge-${priorityLevel}">${priorityLevel}</span></span></div>
      </div>
      <p>Please arrive 15 minutes before your scheduled time. If you need to reschedule, contact us at least 24 hours in advance.</p>`;
    await transporter.sendMail({
      from: `"HospiLink" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: 'Appointment Confirmed — HospiLink',
      html: baseTemplate('Appointment Confirmed', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Email send error:', error);
    return { success: false, error: error.message };
  }
};

exports.sendDischargeNotification = async (toEmail, data) => {
  try {
    const { patientName, dischargeDate, dischargeSummary } = data;
    const content = `
      <h2>Hello ${patientName},</h2>
      <p>You have been successfully discharged from HospiLink. We hope you have a speedy recovery!</p>
      <div class="info-card medium">
        <div class="info-row"><span class="info-label">Discharge Date</span><span class="info-value">${new Date(dischargeDate).toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}</span></div>
      </div>
      <div class="info-card low">
        <p style="color:#2d3748;font-weight:600;margin-bottom:8px">Discharge Summary</p>
        <p style="margin:0">${dischargeSummary}</p>
      </div>
      <p><strong>Important:</strong> Follow all prescribed medications, attend follow-up appointments, and contact us immediately for any complications.</p>`;
    await transporter.sendMail({
      from: `"HospiLink" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: 'Discharge Summary — HospiLink',
      html: baseTemplate('Discharge Summary', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Email send error:', error);
    return { success: false, error: error.message };
  }
};

exports.sendOTPEmail = async (toEmail, otp, userName) => {
  try {
    const content = `
      <h2>Hello ${userName},</h2>
      <p>Your One-Time Password (OTP) for HospiLink verification is:</p>
      <div style="text-align:center;margin:30px 0">
        <div style="display:inline-block;background:#0d9488;color:white;font-size:36px;font-weight:800;letter-spacing:16px;padding:20px 40px;border-radius:12px">${otp}</div>
      </div>
      <p style="text-align:center"><strong>This OTP is valid for 10 minutes.</strong></p>
      <p style="text-align:center;color:#e53e3e">Never share your OTP with anyone.</p>`;
    await transporter.sendMail({
      from: `"HospiLink" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: 'Your OTP Verification Code — HospiLink',
      html: baseTemplate('Verify Your Identity', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Email send error:', error);
    return { success: false, error: error.message };
  }
};

// ═══════════════════════════════════════════
//  NEW NOTIFICATION EMAIL FUNCTIONS
// ═══════════════════════════════════════════

/**
 * IV DRIP REMINDER — for Nurses/Staff
 */
exports.sendDripReminderEmail = async (toEmail, data) => {
  try {
    const { nurseName, patientName, patientBed, fluidType, volumeMl, expectedEndAt, minutesRemaining } = data;
    const urgencyClass = minutesRemaining <= 10 ? 'urgent' : minutesRemaining <= 20 ? 'high' : 'medium';
    const content = `
      <h2>Hello ${nurseName},</h2>
      <p>A patient's IV drip is ending soon and requires your attention.</p>
      <div class="info-card ${urgencyClass}">
        <div class="info-row"><span class="info-label">Patient</span><span class="info-value">${patientName}</span></div>
        <div class="info-row"><span class="info-label">Bed</span><span class="info-value">${patientBed || 'N/A'}</span></div>
        <div class="info-row"><span class="info-label">Fluid Type</span><span class="info-value">${fluidType}</span></div>
        <div class="info-row"><span class="info-label">Volume</span><span class="info-value">${volumeMl} mL</span></div>
        <div class="info-row"><span class="info-label">Ends At</span><span class="info-value">${new Date(expectedEndAt).toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}</span></div>
        <div class="info-row"><span class="info-label">Time Remaining</span><span class="info-value"><span class="badge badge-${urgencyClass}">${minutesRemaining} min remaining</span></span></div>
      </div>
      <p>Please visit the patient to check the IV line and prepare for the next bag or discontinuation as per doctor's orders.</p>`;
    await transporter.sendMail({
      from: `"HospiLink Alerts" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: `IV Drip Alert: ${patientName} — ${minutesRemaining} min remaining`,
      html: baseTemplate('IV Drip Reminder', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Drip email error:', error);
    return { success: false, error: error.message };
  }
};

/**
 * MEDICINE REMINDER — for Patients
 */
exports.sendMedicineReminderEmail = async (toEmail, data) => {
  try {
    const { patientName, medicineName, dosage, frequency, route, specialInstructions } = data;
    const content = `
      <h2>Hello ${patientName},</h2>
      <p>It's time to take your scheduled medication. Please take it as prescribed by your doctor.</p>
      <div class="info-card medium">
        <div class="info-row"><span class="info-label">Medicine</span><span class="info-value"><strong>${medicineName}</strong></span></div>
        <div class="info-row"><span class="info-label">Dosage</span><span class="info-value">${dosage}</span></div>
        <div class="info-row"><span class="info-label">Frequency</span><span class="info-value">${frequency}</span></div>
        ${route ? `<div class="info-row"><span class="info-label">Route</span><span class="info-value">${route}</span></div>` : ''}
        <div class="info-row"><span class="info-label">Time</span><span class="info-value">${new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}</span></div>
      </div>
      ${specialInstructions ? `<div class="info-card low"><p style="color:#276749;font-weight:600;margin-bottom:6px">Special Instructions</p><p style="margin:0">${specialInstructions}</p></div>` : ''}
      <p>If you experience any side effects, please contact your doctor or nursing staff immediately.</p>`;
    await transporter.sendMail({
      from: `"HospiLink" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: `Medicine Reminder: ${medicineName} — HospiLink`,
      html: baseTemplate('Medicine Reminder', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Medicine email error:', error);
    return { success: false, error: error.message };
  }
};

/**
 * ROUTINE CHECK REMINDER — for Doctors
 */
exports.sendRoutineCheckEmail = async (toEmail, data) => {
  try {
    const { doctorName, patients } = data;
    const patientRows = patients.map(p => `
      <div class="info-card ${p.urgency || 'medium'}" style="margin-bottom:12px">
        <div class="info-row"><span class="info-label">Patient</span><span class="info-value"><strong>${p.patientName}</strong></span></div>
        <div class="info-row"><span class="info-label">Bed</span><span class="info-value">${p.bedNumber || 'N/A'}</span></div>
        <div class="info-row"><span class="info-label">Admitted</span><span class="info-value">${p.admissionReason}</span></div>
        <div class="info-row"><span class="info-label">Last Check</span><span class="info-value"><span class="badge badge-high">${p.hoursWithoutCheck}h ago</span></span></div>
      </div>`).join('');
    const content = `
      <h2>Hello Dr. ${doctorName},</h2>
      <p>The following patients under your care have not had their vitals checked in over 6 hours. Please review them at your earliest convenience.</p>
      ${patientRows}
      <p style="color:#718096;font-size:13px">Regular vitals monitoring is critical for early detection of complications. Thank you for your dedication.</p>`;
    await transporter.sendMail({
      from: `"HospiLink Clinical" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: `Routine Check Required — ${patients.length} patient(s) need attention`,
      html: baseTemplate('Routine Check Reminder', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Routine check email error:', error);
    return { success: false, error: error.message };
  }
};

/**
 * FOLLOW-UP REMINDER — for Doctors
 */
exports.sendFollowUpDoctorEmail = async (toEmail, data) => {
  try {
    const { doctorName, patientName, appointmentDate, daysSinceVisit, patientEmail, patientPhone } = data;
    const content = `
      <h2>Hello Dr. ${doctorName},</h2>
      <p>A follow-up reminder for a patient you recently treated. It has been <strong>${daysSinceVisit} days</strong> since their last appointment.</p>
      <div class="info-card medium">
        <div class="info-row"><span class="info-label">Patient</span><span class="info-value">${patientName}</span></div>
        <div class="info-row"><span class="info-label">Last Visit</span><span class="info-value">${new Date(appointmentDate).toLocaleDateString('en-IN')}</span></div>
        <div class="info-row"><span class="info-label">Days Elapsed</span><span class="info-value"><span class="badge badge-high">${daysSinceVisit} days</span></span></div>
        ${patientEmail ? `<div class="info-row"><span class="info-label">Contact Email</span><span class="info-value">${patientEmail}</span></div>` : ''}
        ${patientPhone ? `<div class="info-row"><span class="info-label">Contact Phone</span><span class="info-value">${patientPhone}</span></div>` : ''}
      </div>
      <p>Consider scheduling a follow-up appointment or reaching out to check on the patient's recovery progress.</p>`;
    await transporter.sendMail({
      from: `"HospiLink" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: `Follow-up Reminder: ${patientName} — ${daysSinceVisit} days since last visit`,
      html: baseTemplate('Patient Follow-up Reminder', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Follow-up doctor email error:', error);
    return { success: false, error: error.message };
  }
};

/**
 * FOLLOW-UP REMINDER — for Patients
 */
exports.sendFollowUpPatientEmail = async (toEmail, data) => {
  try {
    const { patientName, doctorName, lastVisitDate, suggestedFollowUpDate } = data;
    const content = `
      <h2>Hello ${patientName},</h2>
      <p>It's time for your follow-up appointment at HospiLink. Regular follow-ups help ensure your complete recovery.</p>
      <div class="info-card medium">
        <div class="info-row"><span class="info-label">Your Doctor</span><span class="info-value">Dr. ${doctorName}</span></div>
        <div class="info-row"><span class="info-label">Last Visit</span><span class="info-value">${new Date(lastVisitDate).toLocaleDateString('en-IN')}</span></div>
        ${suggestedFollowUpDate ? `<div class="info-row"><span class="info-label">Suggested Follow-up</span><span class="info-value">${new Date(suggestedFollowUpDate).toLocaleDateString('en-IN')}</span></div>` : ''}
      </div>
      <p>Please call us or visit the hospital to schedule your follow-up appointment. Early follow-up can prevent complications and speed up your recovery.</p>`;
    await transporter.sendMail({
      from: `"HospiLink" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: `Follow-up Appointment Reminder — HospiLink`,
      html: baseTemplate('Time for Your Follow-up!', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Follow-up patient email error:', error);
    return { success: false, error: error.message };
  }
};

/**
 * APPOINTMENT REMINDER — for Patients (day before)
 */
exports.sendAppointmentReminderEmail = async (toEmail, data) => {
  try {
    const { patientName, doctorName, appointmentDate, department } = data;
    const content = `
      <h2>Hello ${patientName},</h2>
      <p>This is a reminder that you have an appointment at HospiLink <strong>tomorrow</strong>.</p>
      <div class="info-card high">
        <div class="info-row"><span class="info-label">Date & Time</span><span class="info-value"><strong>${new Date(appointmentDate).toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}</strong></span></div>
        <div class="info-row"><span class="info-label">Doctor</span><span class="info-value">Dr. ${doctorName}</span></div>
        ${department ? `<div class="info-row"><span class="info-label">Department</span><span class="info-value">${department}</span></div>` : ''}
      </div>
      <p><strong>Please remember to:</strong></p>
      <ul style="padding-left:20px;margin:12px 0;color:#475569;line-height:2">
        <li>Arrive 15 minutes early for registration</li>
        <li>Bring your HospiLink patient ID / QR code</li>
        <li>Carry any previous medical reports</li>
        <li>List any current medications you are taking</li>
      </ul>`;
    await transporter.sendMail({
      from: `"HospiLink" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: `Appointment Tomorrow: Dr. ${doctorName} — HospiLink`,
      html: baseTemplate('Appointment Reminder', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Appointment reminder email error:', error);
    return { success: false, error: error.message };
  }
};

/**
 * H4. DAILY ADMIN DIGEST
 */
exports.sendAdminDailyDigestEmail = async (toEmail, data) => {
  try {
    const { adminName, stats } = data;
    const content = `
      <h2>Good Morning, ${adminName}!</h2>
      <p>Here's your daily HospiLink system summary for <strong>${new Date().toLocaleDateString('en-IN', { dateStyle: 'full' })}</strong>.</p>
      <div class="info-card medium">
        <div class="info-row"><span class="info-label">Active Admissions</span><span class="info-value">${stats.activeAdmissions || 0}</span></div>
        <div class="info-row"><span class="info-label">Today's Appointments</span><span class="info-value">${stats.todayAppointments || 0}</span></div>
        <div class="info-row"><span class="info-label">Available Beds</span><span class="info-value">${stats.availableBeds || 0}</span></div>
        <div class="info-row"><span class="info-label">Active Doctors</span><span class="info-value">${stats.activeDoctors || 0}</span></div>
        <div class="info-row"><span class="info-label">Active Staff</span><span class="info-value">${stats.activeStaff || 0}</span></div>
        <div class="info-row"><span class="info-label">Running IV Drips</span><span class="info-value">${stats.runningDrips || 0}</span></div>
        <div class="info-row"><span class="info-label">Active Medicines</span><span class="info-value">${stats.activeMedicines || 0}</span></div>
      </div>
      <p style="color:#718096;font-size:13px">This report is generated automatically every day at 8:00 AM IST.</p>`;
    await transporter.sendMail({
      from: `"HospiLink System" <${process.env.SMTP_USERNAME}>`,
      to: toEmail,
      subject: `Daily Digest — HospiLink ${new Date().toLocaleDateString('en-IN')}`,
      html: baseTemplate('Daily System Digest', content)
    });
    return { success: true };
  } catch (error) {
    console.error('Admin digest email error:', error);
    return { success: false, error: error.message };
  }
};

module.exports = transporter;
