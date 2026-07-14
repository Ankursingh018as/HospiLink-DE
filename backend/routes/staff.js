const express = require('express');
const router = express.Router();
const {
  getAllStaff,
  getStaffProfile
} = require('../controllers/staffController');
const { protect, authorize } = require('../middleware/auth');

router.use(protect);

router.get('/', authorize('admin', 'staff', 'nurse'), getAllStaff);
router.get('/:id', getStaffProfile);

module.exports = router;
