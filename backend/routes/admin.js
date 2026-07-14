const express = require('express');
const router = express.Router();
const {
  getSystemStats,
  getAllUsers,
  updateUser,
  deleteUser,
  getActivityLogs
} = require('../controllers/adminController');
const { protect, authorize } = require('../middleware/auth');

// All routes require admin access
router.use(protect);
router.use(authorize('admin'));

router.get('/stats', getSystemStats);
router.get('/users', getAllUsers);
router.put('/users/:id', updateUser);
router.delete('/users/:id', deleteUser);
router.get('/logs', getActivityLogs);

module.exports = router;
