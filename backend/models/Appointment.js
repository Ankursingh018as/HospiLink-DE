const mongoose = require('mongoose');

const appointmentSchema = new mongoose.Schema({
  patient: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },

  doctor: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },

  slot: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'DoctorSlot',
    required: true
  },

  symptoms: {
    type: String,
    required: true
  },

  priorityLevel: {
    type: String,
    enum: ['high', 'medium', 'low'],
    default: 'medium'
  },

  priorityScore: {
    type: Number,
    default: 0
  },

  status: {
    type: String,
    enum: ['pending', 'confirmed', 'completed', 'cancelled'],
    default: 'pending'
  },

  doctorNotes: String

}, { timestamps: true });

appointmentSchema.index({ doctor: 1, status: 1 });
appointmentSchema.index({ priorityScore: -1 });

module.exports = mongoose.model('Appointment', appointmentSchema);
