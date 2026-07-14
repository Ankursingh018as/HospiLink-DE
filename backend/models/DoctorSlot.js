const mongoose = require('mongoose');

const doctorSlotSchema = new mongoose.Schema({
  doctor: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },

  date: {
    type: Date,
    required: true
  },

  startTime: String,
  endTime: String,

  status: {
    type: String,
    enum: ['available', 'booked', 'blocked'],
    default: 'available'
  },

  appointment: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Appointment',
    default: null
  }

}, { timestamps: true });

doctorSlotSchema.index({ doctor: 1, date: 1, startTime: 1 });

module.exports = mongoose.model('DoctorSlot', doctorSlotSchema);
