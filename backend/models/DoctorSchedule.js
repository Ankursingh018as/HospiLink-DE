const mongoose = require('mongoose');

const doctorScheduleSchema = new mongoose.Schema({
  doctor: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },

  dayOfWeek: {
    type: String,
    enum: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
    required: true
  },

  startTime: {
    type: String, // "09:00"
    required: true
  },

  endTime: {
    type: String, // "17:00"
    required: true
  },

  slotDuration: {
    type: Number, // in minutes (e.g. 15, 30)
    default: 15
  },

  isActive: {
    type: Boolean,
    default: true
  }

}, { timestamps: true });

doctorScheduleSchema.index({ doctor: 1, dayOfWeek: 1 });

module.exports = mongoose.model('DoctorSchedule', doctorScheduleSchema);
