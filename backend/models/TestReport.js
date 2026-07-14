const mongoose = require('mongoose');

const testReportSchema = new mongoose.Schema({
  admission: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'PatientAdmission',
    required: true
  },
  testType: {
    type: String,
    required: true
  },
  testName: {
    type: String,
    required: true
  },
  orderedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  orderedAt: {
    type: Date,
    required: true
  },
  performedAt: {
    type: Date,
    default: null
  },
  reportFile: {
    type: String,
    default: null
  },
  results: {
    type: String,
    default: null
  },
  findings: {
    type: String,
    default: null
  },
  normalRange: {
    type: String,
    default: null
  },
  status: {
    type: String,
    enum: ['ordered', 'in_progress', 'completed', 'cancelled'],
    default: 'ordered'
  },
  priority: {
    type: String,
    enum: ['routine', 'urgent', 'stat'],
    default: 'routine'
  }
}, {
  timestamps: true
});

testReportSchema.index({ admission: 1, status: 1 });

module.exports = mongoose.model('TestReport', testReportSchema);
