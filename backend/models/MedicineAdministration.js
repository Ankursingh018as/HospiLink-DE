const mongoose = require('mongoose');

const medicineAdministrationSchema = new mongoose.Schema({
  medicine: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'PatientMedicine',
    required: true
  },
  administeredBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  administeredAt: {
    type: Date,
    required: true
  },
  doseGiven: {
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

medicineAdministrationSchema.index({ medicine: 1, administeredAt: 1 });

module.exports = mongoose.model('MedicineAdministration', medicineAdministrationSchema);
