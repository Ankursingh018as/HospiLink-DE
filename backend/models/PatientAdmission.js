const mongoose = require('mongoose');
const crypto = require('crypto');

const patientAdmissionSchema = new mongoose.Schema({
  patient: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  bed: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Bed',
    default: null
  },
  qrCodeToken: {
    type: String,
    unique: true,
    required: true
  },
  admissionDate: {
    type: Date,
    required: true,
    default: Date.now
  },
  dischargeDate: {
    type: Date,
    default: null
  },
  admissionReason: {
    type: String,
    required: [true, 'Admission reason is required']
  },
  assignedDoctor: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    default: null
  },
  status: {
    type: String,
    enum: ['active', 'discharged', 'transferred'],
    default: 'active'
  },
  vitalSigns: {
    bloodPressure: String,
    heartRate: Number,
    temperature: Number,
    oxygenSaturation: Number
  },
  medications: [{
    name: String,
    dosage: String,
    frequency: String,
    startDate: Date,
    endDate: Date
  }],
  notes: {
    type: String,
    default: null
  },
  dischargeSummary: {
    type: String,
    default: null
  }
}, {
  timestamps: true
});

// Generate unique QR token before saving
patientAdmissionSchema.pre('save', function(next) {
  if (!this.qrCodeToken) {
    this.qrCodeToken = crypto.randomBytes(16).toString('hex');
  }
  next();
});

patientAdmissionSchema.index({ patient: 1, status: 1 });
patientAdmissionSchema.index({ admissionDate: -1 });

module.exports = mongoose.model('PatientAdmission', patientAdmissionSchema);
