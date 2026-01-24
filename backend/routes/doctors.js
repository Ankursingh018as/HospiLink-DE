const express = require('express');
const router = express.Router();
const {
  getAllDoctors,
  getDoctorProfile,
  getDoctorAppointments
} = require('../controllers/doctorController');
const { protect } = require('../middleware/auth');

router.use(protect);

router.get('/', getAllDoctors);
router.get('/:id', getDoctorProfile);
router.get('/:id/appointments', getDoctorAppointments);

module.exports = router;
