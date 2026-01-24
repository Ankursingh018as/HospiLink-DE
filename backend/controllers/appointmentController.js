const Appointment = require('../models/Appointment');
const SymptomKeyword = require('../models/SymptomKeyword');
const User = require('../models/User');
const ActivityLog = require('../models/ActivityLog');

// Calculate priority score based on symptoms
const calculatePriority = async (symptoms) => {
  const keywords = await SymptomKeyword.find();
  const symptomsLower = symptoms.toLowerCase();
  
  let score = 0;
  let level = 'low';
  
  for (const keyword of keywords) {
    if (symptomsLower.includes(keyword.keyword)) {
      if (keyword.priorityLevel === 'high') score += 30;
      else if (keyword.priorityLevel === 'medium') score += 15;
      else score += 5;
    }
  }
  
  if (score >= 70) level = 'high';
  else if (score >= 40) level = 'medium';
  
  return { score, level };
};

// @desc    Create new appointment
// @route   POST /api/appointments
// @access  Private (Patient)
exports.createAppointment = async (req, res) => {
  try {
    const { fullName, email, gender, phone, appointmentDate, appointmentTime, symptoms, doctorId } = req.body;

    // Calculate priority
    const priority = await calculatePriority(symptoms);

    const appointment = await Appointment.create({
      patient: req.user._id,
      doctor: doctorId || null,
      fullName: fullName || `${req.user.firstName} ${req.user.lastName}`,
      email: email || req.user.email,
      gender: gender || req.user.gender,
      phone: phone || req.user.phone,
      appointmentDate,
      appointmentTime: appointmentTime || null,
      symptoms,
      priorityLevel: priority.level,
      priorityScore: priority.score,
      status: 'pending'
    });

    await ActivityLog.create({
      user: req.user._id,
      action: 'appointment_created',
      targetModel: 'Appointment',
      targetId: appointment._id,
      description: `New appointment created with ${priority.level} priority`,
      ipAddress: req.ip
    });

    const populatedAppointment = await Appointment.findById(appointment._id)
      .populate('patient', 'firstName lastName email phone')
      .populate('doctor', 'firstName lastName specialization');

    res.status(201).json({
      success: true,
      message: 'Appointment created successfully',
      appointment: populatedAppointment
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get all appointments (with filtering)
// @route   GET /api/appointments
// @access  Private
exports.getAppointments = async (req, res) => {
  try {
    const { status, priorityLevel, doctorId, patientId, startDate, endDate } = req.query;
    
    let query = {};

    // Role-based filtering
    if (req.user.role === 'patient') {
      query.patient = req.user._id;
    } else if (req.user.role === 'doctor') {
      query.$or = [
        { doctor: req.user._id },
        { doctor: null } // Unassigned appointments
      ];
    }

    // Additional filters
    if (status) query.status = status;
    if (priorityLevel) query.priorityLevel = priorityLevel;
    if (doctorId) query.doctor = doctorId;
    if (patientId) query.patient = patientId;
    
    if (startDate || endDate) {
      query.appointmentDate = {};
      if (startDate) query.appointmentDate.$gte = new Date(startDate);
      if (endDate) query.appointmentDate.$lte = new Date(endDate);
    }

    const appointments = await Appointment.find(query)
      .populate('patient', 'firstName lastName email phone')
      .populate('doctor', 'firstName lastName specialization')
      .sort({ priorityLevel: 1, priorityScore: -1, appointmentDate: 1 });

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

// @desc    Get single appointment
// @route   GET /api/appointments/:id
// @access  Private
exports.getAppointment = async (req, res) => {
  try {
    const appointment = await Appointment.findById(req.params.id)
      .populate('patient', 'firstName lastName email phone dateOfBirth gender address')
      .populate('doctor', 'firstName lastName specialization phone email');

    if (!appointment) {
      return res.status(404).json({
        success: false,
        message: 'Appointment not found'
      });
    }

    res.status(200).json({
      success: true,
      appointment
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Update appointment
// @route   PUT /api/appointments/:id
// @access  Private
exports.updateAppointment = async (req, res) => {
  try {
    const { status, diagnosis, treatment, notes, doctorId } = req.body;

    let appointment = await Appointment.findById(req.params.id);

    if (!appointment) {
      return res.status(404).json({
        success: false,
        message: 'Appointment not found'
      });
    }

    const updateData = {};
    if (status) updateData.status = status;
    if (diagnosis) updateData.diagnosis = diagnosis;
    if (treatment) updateData.treatment = treatment;
    if (notes) updateData.notes = notes;
    if (doctorId) updateData.doctor = doctorId;

    appointment = await Appointment.findByIdAndUpdate(
      req.params.id,
      updateData,
      { new: true, runValidators: true }
    ).populate('patient doctor');

    await ActivityLog.create({
      user: req.user._id,
      action: 'appointment_updated',
      targetModel: 'Appointment',
      targetId: appointment._id,
      description: `Appointment ${status ? 'status changed to ' + status : 'updated'}`,
      ipAddress: req.ip
    });

    res.status(200).json({
      success: true,
      message: 'Appointment updated successfully',
      appointment
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Delete appointment
// @route   DELETE /api/appointments/:id
// @access  Private
exports.deleteAppointment = async (req, res) => {
  try {
    const appointment = await Appointment.findById(req.params.id);

    if (!appointment) {
      return res.status(404).json({
        success: false,
        message: 'Appointment not found'
      });
    }

    await appointment.remove();

    res.status(200).json({
      success: true,
      message: 'Appointment deleted successfully'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Claim unassigned appointment (for doctors)
// @route   PUT /api/appointments/:id/claim
// @access  Private (Doctor)
exports.claimAppointment = async (req, res) => {
  try {
    const appointment = await Appointment.findById(req.params.id);

    if (!appointment) {
      return res.status(404).json({
        success: false,
        message: 'Appointment not found'
      });
    }

    if (appointment.doctor) {
      return res.status(400).json({
        success: false,
        message: 'Appointment already assigned to a doctor'
      });
    }

    appointment.doctor = req.user._id;
    appointment.status = 'confirmed';
    await appointment.save();

    await ActivityLog.create({
      user: req.user._id,
      action: 'appointment_claimed',
      targetModel: 'Appointment',
      targetId: appointment._id,
      description: `Doctor claimed appointment`,
      ipAddress: req.ip
    });

    const updatedAppointment = await Appointment.findById(appointment._id)
      .populate('patient doctor');

    res.status(200).json({
      success: true,
      message: 'Appointment claimed successfully',
      appointment: updatedAppointment
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get appointment statistics
// @route   GET /api/appointments/stats/summary
// @access  Private (Doctor/Admin)
exports.getAppointmentStats = async (req, res) => {
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let query = {};
    if (req.user.role === 'doctor') {
      query.doctor = req.user._id;
    }

    const stats = await Appointment.aggregate([
      { $match: query },
      {
        $facet: {
          total: [{ $count: 'count' }],
          today: [
            { $match: { appointmentDate: { $gte: today } } },
            { $count: 'count' }
          ],
          byPriority: [
            { $group: { _id: '$priorityLevel', count: { $sum: 1 } } }
          ],
          byStatus: [
            { $group: { _id: '$status', count: { $sum: 1 } } }
          ]
        }
      }
    ]);

    res.status(200).json({
      success: true,
      stats: stats[0]
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
