const Bed = require('../models/Bed');
const PatientAdmission = require('../models/PatientAdmission');
const ActivityLog = require('../models/ActivityLog');

// @desc    Get all beds with filtering
// @route   GET /api/beds
// @access  Private (Staff, Nurse, Admin)
exports.getAllBeds = async (req, res) => {
  try {
    const { wardName, isAvailable, status } = req.query;
    
    let query = {};
    if (wardName) query.wardName = wardName;
    if (isAvailable !== undefined) query.isAvailable = isAvailable === 'true';
    if (status) query.status = status;

    const beds = await Bed.find(query)
      .populate('patient', 'firstName lastName email phone')
      .sort({ wardName: 1, bedNumber: 1 });

    res.status(200).json({
      success: true,
      count: beds.length,
      beds
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get bed statistics
// @route   GET /api/beds/stats/occupancy
// @access  Private
exports.getBedStats = async (req, res) => {
  try {
    const stats = await Bed.aggregate([
      {
        $group: {
          _id: '$wardName',
          total: { $sum: 1 },
          occupied: {
            $sum: { $cond: [{ $eq: ['$isAvailable', false] }, 1, 0] }
          },
          available: {
            $sum: { $cond: [{ $eq: ['$isAvailable', true] }, 1, 0] }
          }
        }
      }
    ]);

    const totalStats = await Bed.aggregate([
      {
        $group: {
          _id: null,
          totalBeds: { $sum: 1 },
          occupiedBeds: {
            $sum: { $cond: [{ $eq: ['$isAvailable', false] }, 1, 0] }
          }
        }
      }
    ]);

    res.status(200).json({
      success: true,
      byWard: stats,
      overall: totalStats[0] || { totalBeds: 0, occupiedBeds: 0 }
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get single bed details
// @route   GET /api/beds/:id
// @access  Private
exports.getBed = async (req, res) => {
  try {
    const bed = await Bed.findById(req.params.id)
      .populate('patient', 'firstName lastName email phone');

    if (!bed) {
      return res.status(404).json({
        success: false,
        message: 'Bed not found'
      });
    }

    res.status(200).json({
      success: true,
      bed
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Create new bed
// @route   POST /api/beds
// @access  Private (Admin)
exports.createBed = async (req, res) => {
  try {
    const { bedNumber, wardName, bedType } = req.body;

    const existingBed = await Bed.findOne({ bedNumber });
    if (existingBed) {
      return res.status(400).json({
        success: false,
        message: 'Bed number already exists'
      });
    }

    const bed = await Bed.create({
      bedNumber,
      wardName,
      bedType,
      isAvailable: true,
      status: 'available'
    });

    await ActivityLog.create({
      user: req.user._id,
      action: 'bed_created',
      targetModel: 'Bed',
      targetId: bed._id,
      description: `New bed ${bedNumber} created in ${wardName}`,
      ipAddress: req.ip
    });

    res.status(201).json({
      success: true,
      message: 'Bed created successfully',
      bed
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Assign bed to patient admission
// @route   PUT /api/beds/:id/assign
// @access  Private (Staff, Nurse)
exports.assignBed = async (req, res) => {
  try {
    const { admissionId } = req.body;

    const bed = await Bed.findById(req.params.id);
    if (!bed) {
      return res.status(404).json({
        success: false,
        message: 'Bed not found'
      });
    }

    if (!bed.isAvailable) {
      return res.status(400).json({
        success: false,
        message: 'Bed is already occupied'
      });
    }

    const admission = await PatientAdmission.findById(admissionId);
    if (!admission) {
      return res.status(404).json({
        success: false,
        message: 'Patient admission not found'
      });
    }

    // Update bed
    bed.assignedTo = admissionId;
    bed.isAvailable = false;
    bed.status = 'occupied';
    await bed.save();

    // Update admission
    admission.bed = bed._id;
    await admission.save();

    await ActivityLog.create({
      user: req.user._id,
      action: 'bed_assigned',
      targetModel: 'Bed',
      targetId: bed._id,
      description: `Bed ${bed.bedNumber} assigned to patient`,
      ipAddress: req.ip
    });

    res.status(200).json({
      success: true,
      message: 'Bed assigned successfully',
      bed
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Release bed (discharge patient)
// @route   PUT /api/beds/:id/release
// @access  Private (Staff, Nurse)
exports.releaseBed = async (req, res) => {
  try {
    const bed = await Bed.findById(req.params.id);

    if (!bed) {
      return res.status(404).json({
        success: false,
        message: 'Bed not found'
      });
    }

    if (bed.isAvailable) {
      return res.status(400).json({
        success: false,
        message: 'Bed is already available'
      });
    }

    // Release bed
    bed.assignedTo = null;
    bed.isAvailable = true;
    bed.status = 'available';
    await bed.save();

    await ActivityLog.create({
      user: req.user._id,
      action: 'bed_released',
      targetModel: 'Bed',
      targetId: bed._id,
      description: `Bed ${bed.bedNumber} released`,
      ipAddress: req.ip
    });

    res.status(200).json({
      success: true,
      message: 'Bed released successfully',
      bed
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Update bed status
// @route   PUT /api/beds/:id
// @access  Private (Admin, Staff)
exports.updateBed = async (req, res) => {
  try {
    const { status, wardName, bedType } = req.body;

    const bed = await Bed.findById(req.params.id);
    if (!bed) {
      return res.status(404).json({
        success: false,
        message: 'Bed not found'
      });
    }

    if (status) bed.status = status;
    if (wardName) bed.wardName = wardName;
    if (bedType) bed.bedType = bedType;

    await bed.save();

    res.status(200).json({
      success: true,
      message: 'Bed updated successfully',
      bed
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Delete bed
// @route   DELETE /api/beds/:id
// @access  Private (Admin)
exports.deleteBed = async (req, res) => {
  try {
    const bed = await Bed.findById(req.params.id);

    if (!bed) {
      return res.status(404).json({
        success: false,
        message: 'Bed not found'
      });
    }

    if (!bed.isAvailable) {
      return res.status(400).json({
        success: false,
        message: 'Cannot delete occupied bed'
      });
    }

    await bed.remove();

    res.status(200).json({
      success: true,
      message: 'Bed deleted successfully'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
