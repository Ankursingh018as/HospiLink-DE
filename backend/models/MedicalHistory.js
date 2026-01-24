const mongoose = require('mongoose');

const medicalHistorySchema = new mongoose.Schema({
  patient: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  appointment: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Appointment',
    default: null
  },
  diagnosis: {
    type: String,
    required: true
  },
  treatment: {
    type: String,
    required: true
  },
  prescription: {
    type: String,
    default: null
  },
  notes: {
    type: String,
    default: null
  },
  createdByDoctor: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  visitDate: {
    type: Date,
    default: Date.now
  },
  followUpDate: {
    type: Date,
    default: null
  },
  testResults: [{
    testName: String,
    result: String,
    date: Date,
    notes: String
  }]
}, {
  timestamps: true
});

medicalHistorySchema.index({ patient: 1, visitDate: -1 });

module.exports = mongoose.model('MedicalHistory', medicalHistorySchema);
