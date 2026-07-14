const mongoose = require('mongoose');

const admittedPatientSchema = new mongoose.Schema({
  patientName: {
    type: String,
    required: true
  },
  phone: {
    type: String,
    default: null
  },
  email: {
    type: String,
    default: null
  },
  bloodGroup: {
    type: String,
    default: null
  },
  disease: {
    type: String,
    required: true
  },
  address: {
    type: String,
    default: null
  },
  bed: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Bed',
    default: null
  },
  admissionDate: {
    type: Date,
    default: Date.now
  },
  dischargeDate: {
    type: Date,
    default: null
  },
  status: {
    type: String,
    enum: ['stable', 'moderate', 'critical'],
    default: 'stable'
  },
  priority: {
    type: String,
    enum: ['stable', 'moderate', 'critical'],
    default: 'stable'
  },
  assignedStaff: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    default: null
  },
  assignmentNotes: {
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

admittedPatientSchema.index({ dischargeDate: 1 });
admittedPatientSchema.index({ bed: 1 });
admittedPatientSchema.index({ status: 1 });

module.exports = mongoose.model('AdmittedPatient', admittedPatientSchema);
