const nodemailer = require('nodemailer');

// Create reusable transporter
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST,
  port: process.env.SMTP_PORT,
  secure: false, // true for 465, false for other ports
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASSWORD
  }
});

// Verify SMTP connection
transporter.verify((error, success) => {
  if (error) {
    console.log('❌ SMTP connection error:', error.message);
  } else {
    console.log('✅ SMTP Server is ready to send emails');
  }
});

// Send appointment confirmation email
exports.sendAppointmentConfirmation = async (toEmail, data) => {
  try {
    const { patientName, doctorName, appointmentDate, symptoms, priorityLevel } = data;

    const mailOptions = {
      from: `"HospiLink" <${process.env.SMTP_USER}>`,
      to: toEmail,
      subject: 'Appointment Confirmation - HospiLink',
      html: `
        <!DOCTYPE html>
        <html>
        <head>
          <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .priority { padding: 10px; margin: 10px 0; border-radius: 5px; font-weight: bold; }
            .priority-high { background: #fee; color: #c00; }
            .priority-medium { background: #ffc; color: #880; }
            .priority-low { background: #efe; color: #080; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>🏥 Appointment Confirmed</h1>
            </div>
            <div class="content">
              <h2>Hello ${patientName},</h2>
              <p>Your appointment has been successfully booked at HospiLink.</p>
              <p><strong>Appointment Details:</strong></p>
              <ul>
                <li><strong>Date & Time:</strong> ${new Date(appointmentDate).toLocaleString()}</li>
                <li><strong>Doctor:</strong> ${doctorName || 'Will be assigned soon'}</li>
                <li><strong>Symptoms:</strong> ${symptoms}</li>
              </ul>
              <div class="priority priority-${priorityLevel}">
                Priority Level: ${priorityLevel.toUpperCase()}
              </div>
              <p>Please arrive 15 minutes before your scheduled time.</p>
              <p>If you need to reschedule or cancel, please contact us at least 24 hours in advance.</p>
            </div>
            <div class="footer">
              <p>© 2026 HospiLink. All rights reserved.</p>
              <p>This is an automated email. Please do not reply.</p>
            </div>
          </div>
        </body>
        </html>
      `
    };

    await transporter.sendMail(mailOptions);
    return { success: true };
  } catch (error) {
    console.error('Email send error:', error);
    return { success: false, error: error.message };
  }
};

// Send discharge notification
exports.sendDischargeNotification = async (toEmail, data) => {
  try {
    const { patientName, dischargeDate, dischargeSummary } = data;

    const mailOptions = {
      from: `"HospiLink" <${process.env.SMTP_USER}>`,
      to: toEmail,
      subject: 'Discharge Summary - HospiLink',
      html: `
        <!DOCTYPE html>
        <html>
        <head>
          <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .summary { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>🏥 Discharge Summary</h1>
            </div>
            <div class="content">
              <h2>Hello ${patientName},</h2>
              <p>You have been successfully discharged from HospiLink.</p>
              <p><strong>Discharge Date:</strong> ${new Date(dischargeDate).toLocaleString()}</p>
              <div class="summary">
                <h3>Discharge Summary:</h3>
                <p>${dischargeSummary}</p>
              </div>
              <p><strong>Important Instructions:</strong></p>
              <ul>
                <li>Follow all prescribed medications as directed</li>
                <li>Attend all follow-up appointments</li>
                <li>Contact us immediately if you experience any complications</li>
                <li>Keep your discharge papers for future reference</li>
              </ul>
              <p>We wish you a speedy recovery!</p>
            </div>
            <div class="footer">
              <p>© 2026 HospiLink. All rights reserved.</p>
              <p>For any questions, please contact us at support@hospilink.com</p>
            </div>
          </div>
        </body>
        </html>
      `
    };

    await transporter.sendMail(mailOptions);
    return { success: true };
  } catch (error) {
    console.error('Email send error:', error);
    return { success: false, error: error.message };
  }
};

// Send OTP email
exports.sendOTPEmail = async (toEmail, otp, userName) => {
  try {
    const mailOptions = {
      from: `"HospiLink" <${process.env.SMTP_USER}>`,
      to: toEmail,
      subject: 'Your OTP for HospiLink',
      html: `
        <!DOCTYPE html>
        <html>
        <head>
          <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; text-align: center; }
            .otp { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 10px; padding: 20px; background: white; border-radius: 10px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>🔐 HospiLink OTP</h1>
            </div>
            <div class="content">
              <h2>Hello ${userName},</h2>
              <p>Your One-Time Password (OTP) for verification is:</p>
              <div class="otp">${otp}</div>
              <p><strong>This OTP is valid for 10 minutes.</strong></p>
              <p>If you didn't request this OTP, please ignore this email.</p>
            </div>
            <div class="footer">
              <p>© 2026 HospiLink. All rights reserved.</p>
              <p>Never share your OTP with anyone.</p>
            </div>
          </div>
        </body>
        </html>
      `
    };

    await transporter.sendMail(mailOptions);
    return { success: true };
  } catch (error) {
    console.error('Email send error:', error);
    return { success: false, error: error.message };
  }
};

module.exports = transporter;
