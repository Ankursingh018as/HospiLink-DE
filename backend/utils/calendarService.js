const { google } = require('googleapis');

// Google Calendar API setup
const SCOPES = ['https://www.googleapis.com/auth/calendar'];
const CALENDAR_ID = process.env.GOOGLE_CALENDAR_ID || 'primary';

// Create OAuth2 client
const oauth2Client = new google.auth.OAuth2(
  process.env.GOOGLE_CLIENT_ID,
  process.env.GOOGLE_CLIENT_SECRET,
  process.env.GOOGLE_REDIRECT_URI
);

// Set credentials if refresh token is available
if (process.env.GOOGLE_REFRESH_TOKEN) {
  oauth2Client.setCredentials({
    refresh_token: process.env.GOOGLE_REFRESH_TOKEN
  });
}

const calendar = google.calendar({ version: 'v3', auth: oauth2Client });

// @desc    Create calendar event for appointment
exports.createAppointmentEvent = async (appointmentData) => {
  try {
    const { patientName, doctorName, appointmentDate, symptoms, email } = appointmentData;

    const event = {
      summary: `Appointment: ${patientName}`,
      description: `Patient: ${patientName}\nDoctor: ${doctorName}\nSymptoms: ${symptoms}`,
      start: {
        dateTime: new Date(appointmentDate).toISOString(),
        timeZone: process.env.TIMEZONE || 'Asia/Kolkata'
      },
      end: {
        dateTime: new Date(new Date(appointmentDate).getTime() + 30 * 60000).toISOString(), // 30 minutes
        timeZone: process.env.TIMEZONE || 'Asia/Kolkata'
      },
      attendees: [
        { email: email }
      ],
      reminders: {
        useDefault: false,
        overrides: [
          { method: 'email', minutes: 24 * 60 }, // 1 day before
          { method: 'popup', minutes: 30 }
        ]
      }
    };

    const response = await calendar.events.insert({
      calendarId: CALENDAR_ID,
      resource: event,
      sendUpdates: 'all'
    });

    return {
      success: true,
      eventId: response.data.id,
      eventLink: response.data.htmlLink
    };
  } catch (error) {
    console.error('Google Calendar API error:', error);
    return {
      success: false,
      error: error.message
    };
  }
};

// @desc    Update calendar event
exports.updateAppointmentEvent = async (eventId, appointmentData) => {
  try {
    const { patientName, doctorName, appointmentDate, symptoms, email } = appointmentData;

    const event = {
      summary: `Appointment: ${patientName}`,
      description: `Patient: ${patientName}\nDoctor: ${doctorName}\nSymptoms: ${symptoms}`,
      start: {
        dateTime: new Date(appointmentDate).toISOString(),
        timeZone: process.env.TIMEZONE || 'Asia/Kolkata'
      },
      end: {
        dateTime: new Date(new Date(appointmentDate).getTime() + 30 * 60000).toISOString(),
        timeZone: process.env.TIMEZONE || 'Asia/Kolkata'
      },
      attendees: [
        { email: email }
      ]
    };

    await calendar.events.update({
      calendarId: CALENDAR_ID,
      eventId: eventId,
      resource: event,
      sendUpdates: 'all'
    });

    return { success: true };
  } catch (error) {
    console.error('Google Calendar update error:', error);
    return { success: false, error: error.message };
  }
};

// @desc    Delete calendar event
exports.deleteAppointmentEvent = async (eventId) => {
  try {
    await calendar.events.delete({
      calendarId: CALENDAR_ID,
      eventId: eventId,
      sendUpdates: 'all'
    });

    return { success: true };
  } catch (error) {
    console.error('Google Calendar delete error:', error);
    return { success: false, error: error.message };
  }
};

// @desc    Get authorization URL for OAuth
exports.getAuthUrl = () => {
  return oauth2Client.generateAuthUrl({
    access_type: 'offline',
    scope: SCOPES
  });
};

// @desc    Exchange authorization code for tokens
exports.getTokensFromCode = async (code) => {
  try {
    const { tokens } = await oauth2Client.getToken(code);
    oauth2Client.setCredentials(tokens);
    return {
      success: true,
      tokens: tokens
    };
  } catch (error) {
    return {
      success: false,
      error: error.message
    };
  }
};

module.exports = {
  createAppointmentEvent: exports.createAppointmentEvent,
  updateAppointmentEvent: exports.updateAppointmentEvent,
  deleteAppointmentEvent: exports.deleteAppointmentEvent,
  getAuthUrl: exports.getAuthUrl,
  getTokensFromCode: exports.getTokensFromCode
};
