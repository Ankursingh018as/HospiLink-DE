const mongoose = require('mongoose');

const qrScanSchema = new mongoose.Schema({
  admission: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'PatientAdmission',
    required: true
  },
  scannedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  location: {
    type: String,
    default: null
  },
  scanTimestamp: {
    type: Date,
    default: Date.now
  }
}, {
  timestamps: true
});

qrScanSchema.index({ admission: 1, scanTimestamp: -1 });

module.exports = mongoose.model('QRScan', qrScanSchema);
