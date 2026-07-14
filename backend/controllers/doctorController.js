const User = require('../models/User');
const Appointment = require('../models/Appointment');

// @desc    Get all doctors
// @route   GET /api/doctors
// @access  Private
exports.getAllDoctors = async (req, res) => {
  try {
    const { specialization, isActive } = req.query;

    let query = { role: 'doctor' };
    if (specialization) query.specialization = specialization;
    if (isActive !== undefined) query.isActive = isActive === 'true';

    const doctors = await User.find(query).select('-password');

    res.status(200).json({
      success: true,
      count: doctors.length,
      doctors
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get doctor profile
// @route   GET /api/doctors/:id
// @access  Private
exports.getDoctorProfile = async (req, res) => {
  try {
    const doctor = await User.findById(req.params.id).select('-password');

    if (!doctor || doctor.role !== 'doctor') {
      return res.status(404).json({
        success: false,
        message: 'Doctor not found'
      });
    }

    // Get doctor's statistics
    const appointmentStats = await Appointment.aggregate([
      { $match: { doctor: doctor._id } },
      {
        $group: {
          _id: null,
          total: { $sum: 1 },
          completed: {
            $sum: { $cond: [{ $eq: ['$status', 'completed'] }, 1, 0] }
          },
          pending: {
            $sum: { $cond: [{ $eq: ['$status', 'pending'] }, 1, 0] }
          }
        }
      }
    ]);

    res.status(200).json({
      success: true,
      doctor,
      stats: appointmentStats[0] || { total: 0, completed: 0, pending: 0 }
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get doctor's appointments
// @route   GET /api/doctors/:id/appointments
// @access  Private
exports.getDoctorAppointments = async (req, res) => {
  try {
    const { status, startDate, endDate } = req.query;

    let query = { doctor: req.params.id };
    if (status) query.status = status;
    if (startDate || endDate) {
      query.appointmentDate = {};
      if (startDate) query.appointmentDate.$gte = new Date(startDate);
      if (endDate) query.appointmentDate.$lte = new Date(endDate);
    }

    const appointments = await Appointment.find(query)
      .populate('patient', 'firstName lastName email phone')
      .sort({ appointmentDate: -1 });

    res.status(200).json({
      success: true,
      count: appointments.length,
      appointments
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
