const mongoose = require('mongoose');

const notificationSchema = new mongoose.Schema({
  recipient: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true,
    index: true
  },
  recipientRole: {
    type: String,
    enum: ['patient', 'doctor', 'staff', 'admin'],
    required: true
  },
  type: {
    type: String,
    enum: [
      'drip_reminder',       // IV drip ending soon → nurse
      'medicine_reminder',   // Medicine due → patient / nurse
      'routine_check',       // Vitals overdue → doctor
      'followup_doctor',     // Post-discharge follow-up → doctor
      'followup_patient',    // Follow-up appointment → patient
      'appointment_reminder',// Upcoming appointment → patient
      'appointment_alert',   // Appointment confirmed → doctor
      'daily_digest',        // Admin daily summary
      'system'               // General system alert
    ],
    required: true
  },
  title: {
    type: String,
    required: true,
    maxlength: 200
  },
  message: {
    type: String,
    required: true,
    maxlength: 1000
  },
  // Link to the related entity (admission, appointment, medicine, etc.)
  relatedEntity: {
    model: {
      type: String,
      enum: ['PatientAdmission', 'Appointment', 'PatientMedicine', 'PatientIV', 'User']
    },
    id: {
      type: mongoose.Schema.Types.ObjectId
    }
  },
  priority: {
    type: String,
    enum: ['urgent', 'high', 'medium', 'low'],
    default: 'medium'
  },
  isRead: {
    type: Boolean,
    default: false,
    index: true
  },
  readAt: {
    type: Date,
    default: null
  },
  sentViaEmail: {
    type: Boolean,
    default: false
  },
  sentViaPush: {
    type: Boolean,
    default: false
  },
  emailSentAt: {
    type: Date,
    default: null
  },
  pushSentAt: {
    type: Date,
    default: null
  },
  scheduledFor: {
    type: Date,
    default: null
  },
  // Prevent duplicate scheduler notifications
  deduplicationKey: {
    type: String,
    default: null,
    index: true,
    sparse: true
  },
  actionUrl: {
    type: String,
    default: null
  },
  iconType: {
    type: String,
    enum: ['💉', '💊', '🩺', '📅', '👤', '📆', '🏥', '🔔'],
    default: '🔔'
  }
}, {
  timestamps: true
});

// Compound index for efficient querying
notificationSchema.index({ recipient: 1, isRead: 1, createdAt: -1 });
notificationSchema.index({ createdAt: 1 }, { expireAfterSeconds: 30 * 24 * 60 * 60 }); // Auto-delete after 30 days

module.exports = mongoose.model('Notification', notificationSchema);
