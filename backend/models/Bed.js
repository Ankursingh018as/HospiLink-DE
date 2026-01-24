const mongoose = require('mongoose');

const bedSchema = new mongoose.Schema({
  wardName: {
    type: String,
    required: true
  },
  bedNumber: {
    type: String,
    required: true
  },
  bedType: {
    type: String,
    enum: ['ICU', 'General', 'Private', 'Emergency', 'Semi-Private'],
    required: true
  },
  status: {
    type: String,
    enum: ['available', 'occupied', 'maintenance'],
    default: 'available'
  },
  patient: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    default: null
  },
  admittedDate: {
    type: Date,
    default: null
  }
}, {
  timestamps: true
});

// Create compound index for unique bed identification
bedSchema.index({ wardName: 1, bedNumber: 1 }, { unique: true });
bedSchema.index({ status: 1 });

module.exports = mongoose.model('Bed', bedSchema);
