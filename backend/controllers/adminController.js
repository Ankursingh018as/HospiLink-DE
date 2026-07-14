const User = require('../models/User');
const Appointment = require('../models/Appointment');
const PatientAdmission = require('../models/PatientAdmission');
const Bed = require('../models/Bed');
const ActivityLog = require('../models/ActivityLog');

// @desc    Get system statistics
// @route   GET /api/admin/stats
// @access  Private (Admin)
exports.getSystemStats = async (req, res) => {
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // User statistics
    const userStats = await User.aggregate([
      {
        $group: {
          _id: '$role',
          count: { $sum: 1 }
        }
      }
    ]);

    // Appointment statistics
    const appointmentStats = await Appointment.aggregate([
      {
        $facet: {
          total: [{ $count: 'count' }],
          today: [
            { $match: { appointmentDate: { $gte: today } } },
            { $count: 'count' }
          ],
          byStatus: [
            { $group: { _id: '$status', count: { $sum: 1 } } }
          ],
          byPriority: [
            { $group: { _id: '$priorityLevel', count: { $sum: 1 } } }
          ]
        }
      }
    ]);

    // Patient admission statistics
    const patientStats = await PatientAdmission.aggregate([
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
          ]
        }
      }
    ]);

    // Bed statistics
    const bedStats = await Bed.aggregate([
      {
        $group: {
          _id: null,
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

    res.status(200).json({
      success: true,
      stats: {
        users: userStats,
        appointments: appointmentStats[0],
        patients: patientStats[0],
        beds: bedStats[0] || { total: 0, occupied: 0, available: 0 }
      }
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get all users
// @route   GET /api/admin/users
// @access  Private (Admin)
exports.getAllUsers = async (req, res) => {
  try {
    const { role, isActive, search } = req.query;

    let query = {};
    if (role) query.role = role;
    if (isActive !== undefined) query.isActive = isActive === 'true';
    if (search) {
      query.$or = [
        { firstName: { $regex: search, $options: 'i' } },
        { lastName: { $regex: search, $options: 'i' } },
        { email: { $regex: search, $options: 'i' } }
      ];
    }

    const users = await User.find(query).select('-password');

    res.status(200).json({
      success: true,
      count: users.length,
      users
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Update user
// @route   PUT /api/admin/users/:id
// @access  Private (Admin)
exports.updateUser = async (req, res) => {
  try {
    const { role, isActive, specialization } = req.body;

    const user = await User.findById(req.params.id);
    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    if (role) user.role = role;
    if (isActive !== undefined) user.isActive = isActive;
    if (specialization) user.specialization = specialization;

    await user.save();

    await ActivityLog.create({
      user: req.user._id,
      action: 'user_updated',
      targetModel: 'User',
      targetId: user._id,
      description: `User ${user.email} updated by admin`,
      ipAddress: req.ip
    });

    res.status(200).json({
      success: true,
      message: 'User updated successfully',
      user
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Delete user
// @route   DELETE /api/admin/users/:id
// @access  Private (Admin)
exports.deleteUser = async (req, res) => {
  try {
    const user = await User.findById(req.params.id);

    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    await user.remove();

    res.status(200).json({
      success: true,
      message: 'User deleted successfully'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Get activity logs
// @route   GET /api/admin/logs
// @access  Private (Admin)
exports.getActivityLogs = async (req, res) => {
  try {
    const { action, userId, limit = 50 } = req.query;

    let query = {};
    if (action) query.action = action;
    if (userId) query.user = userId;

    const logs = await ActivityLog.find(query)
      .populate('user', 'firstName lastName email role')
      .sort({ createdAt: -1 })
      .limit(parseInt(limit));

    res.status(200).json({
      success: true,
      count: logs.length,
      logs
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
