const mongoose = require('mongoose');

const patientIVSchema = new mongoose.Schema({
  admission: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'PatientAdmission',
    required: true
  },
  fluidType: {
    type: String,
    required: true
  },
  volumeMl: {
    type: Number,
    required: true
  },
  flowRate: {
    type: String,
    default: null
  },
  startedAt: {
    type: Date,
    required: true
  },
  expectedEndAt: {
    type: Date,
    default: null
  },
  actualEndAt: {
    type: Date,
    default: null
  },
  startedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  stoppedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    default: null
  },
  status: {
    type: String,
    enum: ['running', 'completed', 'discontinued'],
    default: 'running'
  },
  siteLocation: {
    type: String,
    default: null
  },
  notes: {
    type: String,
    default: null
  }
}, {
  timestamps: true
});

patientIVSchema.index({ admission: 1, status: 1 });

module.exports = mongoose.model('PatientIV', patientIVSchema);
