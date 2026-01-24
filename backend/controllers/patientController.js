const PatientAdmission = require('../models/PatientAdmission');
const User = require('../models/User');
const Bed = require('../models/Bed');
const MedicalHistory = require('../models/MedicalHistory');
const Appointment = require('../models/Appointment');
const ActivityLog = require('../models/ActivityLog');

// @desc    Admit patient
// @route   POST /api/patients/admit
// @access  Private (Staff, Nurse, Doctor)
exports.admitPatient = async (req, res) => {
  try {
    const { patientId, bedId, admissionReason, assignedDoctorId } = req.body;

    const patient = await User.findById(patientId);
    if (!patient || patient.role !== 'patient') {
      return res.status(404).json({
        success: false,
        message: 'Patient not found'
      });
    }

    // Check if patient already admitted
    const existingAdmission = await PatientAdmission.findOne({
      patient: patientId,
      status: 'active'
    });

    if (existingAdmission) {
      return res.status(400).json({
        success: false,
        message: 'Patient is already admitted'
      });
    }

    // Create admission
    const admission = await PatientAdmission.create({
      patient: patientId,
      bed: bedId || null,
      admissionReason,
      assignedDoctor: assignedDoctorId || null,
      status: 'active'
    });

    // If bed assigned, update bed status
    if (bedId) {
      await Bed.findByIdAndUpdate(bedId, {
        assignedTo: admission._id,
        isAvailable: false,
        status: 'occupied'
      });
    }

    await ActivityLog.create({
      user: req.user._id,
      action: 'patient_admitted',
      targetModel: 'PatientAdmission',
      targetId: admission._id,
      description: `Patient ${patient.firstName} ${patient.lastName} admitted`,
      ipAddress: req.ip
    });

    const populatedAdmission = await PatientAdmission.findById(admission._id)
      .populate('patient', 'firstName lastName email phone')
      .populate('bed')
      .populate('assignedDoctor', 'firstName lastName specialization');

    res.status(201).json({
      success: true,
      message: 'Patient admitted successfully',
      admission: populatedAdmission
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get all patients (admitted)
// @route   GET /api/patients
// @access  Private (Staff, Doctor, Admin)
exports.getAllPatients = async (req, res) => {
  try {
    const { status, search } = req.query;

    let query = {};
    if (status) query.status = status;

    // Search by patient name (requires population)
    let admissions = await PatientAdmission.find(query)
      .populate('patient', 'firstName lastName email phone dateOfBirth gender address')
      .populate('bed', 'bedNumber wardName bedType')
      .populate('assignedDoctor', 'firstName lastName specialization')
      .sort({ admissionDate: -1 });

    // Filter by search term if provided
    if (search) {
      const searchLower = search.toLowerCase();
      admissions = admissions.filter(admission => {
        const fullName = `${admission.patient.firstName} ${admission.patient.lastName}`.toLowerCase();
        return fullName.includes(searchLower) || 
               admission.admissionReason?.toLowerCase().includes(searchLower);
      });
    }

    res.status(200).json({
      success: true,
      count: admissions.length,
      patients: admissions
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get single patient admission
// @route   GET /api/patients/:id
// @access  Private
exports.getPatient = async (req, res) => {
  try {
    const admission = await PatientAdmission.findById(req.params.id)
      .populate('patient', 'firstName lastName email phone dateOfBirth gender address')
      .populate('bed', 'bedNumber wardName bedType')
      .populate('assignedDoctor', 'firstName lastName specialization phone email');

    if (!admission) {
      return res.status(404).json({
        success: false,
        message: 'Patient admission not found'
      });
    }

    res.status(200).json({
      success: true,
      admission
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Update patient admission
// @route   PUT /api/patients/:id
// @access  Private (Staff, Doctor)
exports.updatePatientAdmission = async (req, res) => {
  try {
    const { vitalSigns, medications, notes } = req.body;

    const admission = await PatientAdmission.findById(req.params.id);
    if (!admission) {
      return res.status(404).json({
        success: false,
        message: 'Admission not found'
      });
    }

    if (vitalSigns) admission.vitalSigns = { ...admission.vitalSigns, ...vitalSigns };
    if (medications) admission.medications = medications;
    if (notes) admission.notes = notes;

    await admission.save();

    res.status(200).json({
      success: true,
      message: 'Patient admission updated',
      admission
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Discharge patient
// @route   PUT /api/patients/:id/discharge
// @access  Private (Doctor, Staff)
exports.dischargePatient = async (req, res) => {
  try {
    const { dischargeSummary } = req.body;

    const admission = await PatientAdmission.findById(req.params.id);
    if (!admission) {
      return res.status(404).json({
        success: false,
        message: 'Admission not found'
      });
    }

    if (admission.status === 'discharged') {
      return res.status(400).json({
        success: false,
        message: 'Patient already discharged'
      });
    }

    admission.status = 'discharged';
    admission.dischargeDate = new Date();
    admission.dischargeSummary = dischargeSummary;
    await admission.save();

    // Release bed if assigned
    if (admission.bed) {
      await Bed.findByIdAndUpdate(admission.bed, {
        assignedTo: null,
        isAvailable: true,
        status: 'available'
      });
    }

    await ActivityLog.create({
      user: req.user._id,
      action: 'patient_discharged',
      targetModel: 'PatientAdmission',
      targetId: admission._id,
      description: 'Patient discharged',
      ipAddress: req.ip
    });

    res.status(200).json({
      success: true,
      message: 'Patient discharged successfully',
      admission
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get patient medical history
// @route   GET /api/patients/:id/history
// @access  Private
exports.getPatientHistory = async (req, res) => {
  try {
    const admission = await PatientAdmission.findById(req.params.id);
    if (!admission) {
      return res.status(404).json({
        success: false,
        message: 'Admission not found'
      });
    }

    const history = await MedicalHistory.find({ patient: admission.patient })
      .populate('createdByDoctor', 'firstName lastName specialization')
      .populate('appointment')
      .sort({ visitDate: -1 });

    const appointments = await Appointment.find({ patient: admission.patient })
      .populate('doctor', 'firstName lastName specialization')
      .sort({ appointmentDate: -1 })
      .limit(10);

    res.status(200).json({
      success: true,
      history,
      appointments
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get patient statistics
// @route   GET /api/patients/stats/summary
// @access  Private (Staff, Admin)
exports.getPatientStats = async (req, res) => {
  try {
    const stats = await PatientAdmission.aggregate([
      {
        $facet: {
          total: [{ $count: 'count' }],
          active: [
            { $match: { status: 'active' } },
            { $count: 'count' }
          ],
          discharged: [
            { $match: { status: 'discharged' } },
            { $count: 'count' }
          ],
          critical: [
            { $match: { 'vitalSigns.condition': 'critical' } },
            { $count: 'count' }
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
