const mongoose = require('mongoose');

const patientMedicineSchema = new mongoose.Schema({
  admission: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'PatientAdmission',
    required: true
  },
  medicineName: {
    type: String,
    required: true
  },
  dosage: {
    type: String,
    required: true
  },
  frequency: {
    type: String,
    required: true
  },
  route: {
    type: String,
    default: null
  },
  startDate: {
    type: Date,
    required: true
  },
  endDate: {
    type: Date,
    default: null
  },
  prescribedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  status: {
    type: String,
    enum: ['active', 'completed', 'discontinued'],
    default: 'active'
  },
  specialInstructions: {
    type: String,
    default: null
  }
}, {
  timestamps: true
});

patientMedicineSchema.index({ admission: 1, status: 1 });

module.exports = mongoose.model('PatientMedicine', patientMedicineSchema);
