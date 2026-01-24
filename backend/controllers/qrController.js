const QRCode = require('qrcode');
const PatientAdmission = require('../models/PatientAdmission');
const QRScan = require('../models/QRScan');
const ActivityLog = require('../models/ActivityLog');

// @desc    Generate QR code for patient admission
// @route   POST /api/qr/generate
// @access  Private (Staff, Nurse)
exports.generateQR = async (req, res) => {
  try {
    const { admissionId } = req.body;

    const admission = await PatientAdmission.findById(admissionId)
      .populate('patient', 'firstName lastName')
      .populate('bed', 'bedNumber wardName');

    if (!admission) {
      return res.status(404).json({
        success: false,
        message: 'Admission not found'
      });
    }

    // Generate QR code image (Data URL)
    const qrData = JSON.stringify({
      token: admission.qrCodeToken,
      admissionId: admission._id,
      patientName: `${admission.patient.firstName} ${admission.patient.lastName}`,
      bedNumber: admission.bed?.bedNumber || 'Not assigned'
    });

    const qrCodeDataURL = await QRCode.toDataURL(qrData, {
      errorCorrectionLevel: 'H',
      width: 300,
      margin: 2
    });

    await ActivityLog.create({
      user: req.user._id,
      action: 'qr_generated',
      targetModel: 'PatientAdmission',
      targetId: admission._id,
      description: `QR code generated for patient admission`,
      ipAddress: req.ip
    });

    res.status(200).json({
      success: true,
      qrCode: qrCodeDataURL,
      token: admission.qrCodeToken,
      admission
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Scan QR code and get patient info
// @route   POST /api/qr/scan
// @access  Private (Staff, Nurse, Doctor)
exports.scanQR = async (req, res) => {
  try {
    const { token } = req.body;

    if (!token) {
      return res.status(400).json({
        success: false,
        message: 'QR token is required'
      });
    }

    const admission = await PatientAdmission.findOne({ qrCodeToken: token })
      .populate('patient', 'firstName lastName email phone dateOfBirth gender address')
      .populate('bed', 'bedNumber wardName bedType')
      .populate('assignedDoctor', 'firstName lastName specialization phone email');

    if (!admission) {
      return res.status(404).json({
        success: false,
        message: 'Invalid QR code or admission not found'
      });
    }

    // Log scan
    await QRScan.create({
      admission: admission._id,
      scannedBy: req.user._id,
      location: req.body.location || null,
      scanTimestamp: new Date()
    });

    await ActivityLog.create({
      user: req.user._id,
      action: 'qr_scanned',
      targetModel: 'PatientAdmission',
      targetId: admission._id,
      description: `QR code scanned for patient ${admission.patient.firstName}`,
      ipAddress: req.ip
    });

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

// @desc    Get scan history for admission
// @route   GET /api/qr/scans/:admissionId
// @access  Private
exports.getScanHistory = async (req, res) => {
  try {
    const scans = await QRScan.find({ admission: req.params.admissionId })
      .populate('scannedBy', 'firstName lastName role')
      .sort({ scanTimestamp: -1 });

    res.status(200).json({
      success: true,
      count: scans.length,
      scans
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Verify QR code validity
// @route   POST /api/qr/verify
// @access  Public (for printing/verification)
exports.verifyQR = async (req, res) => {
  try {
    const { token } = req.body;

    const admission = await PatientAdmission.findOne({ qrCodeToken: token })
      .select('_id qrCodeToken status admissionDate')
      .populate('patient', 'firstName lastName')
      .populate('bed', 'bedNumber wardName');

    if (!admission) {
      return res.status(404).json({
        success: false,
        valid: false,
        message: 'Invalid QR code'
      });
    }

    res.status(200).json({
      success: true,
      valid: true,
      admission: {
        id: admission._id,
        patientName: `${admission.patient.firstName} ${admission.patient.lastName}`,
        bedInfo: admission.bed ? `${admission.bed.wardName} - ${admission.bed.bedNumber}` : 'Not assigned',
        status: admission.status,
        admissionDate: admission.admissionDate
      }
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
