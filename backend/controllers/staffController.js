const User = require('../models/User');

// @desc    Get all staff members
// @route   GET /api/staff
// @access  Private (Admin)
exports.getAllStaff = async (req, res) => {
  try {
    const staff = await User.find({ 
      role: { $in: ['staff', 'nurse'] } 
    }).select('-password');

    res.status(200).json({
      success: true,
      count: staff.length,
      staff
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get staff profile
// @route   GET /api/staff/:id
// @access  Private
exports.getStaffProfile = async (req, res) => {
  try {
    const staff = await User.findById(req.params.id).select('-password');

    if (!staff || !['staff', 'nurse'].includes(staff.role)) {
      return res.status(404).json({
        success: false,
        message: 'Staff member not found'
      });
    }

    res.status(200).json({
      success: true,
      staff
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
