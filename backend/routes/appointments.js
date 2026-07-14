const express = require('express');
const router = express.Router();
const {
  createAppointment,
  getAppointments,
  getAppointment,
  updateAppointment,
  deleteAppointment,
  claimAppointment,
  getAppointmentStats
} = require('../controllers/appointmentController');
const { protect, authorize } = require('../middleware/auth');

// All routes require authentication
router.use(protect);

router.route('/')
  .get(getAppointments)
  .post(authorize('patient', 'admin'), createAppointment);

router.get('/stats/summary', authorize('doctor', 'admin'), getAppointmentStats);

router.route('/:id')
  .get(getAppointment)
  .put(authorize('doctor', 'admin', 'patient'), updateAppointment)
  .delete(authorize('admin', 'patient'), deleteAppointment);

router.put('/:id/claim', authorize('doctor'), claimAppointment);

module.exports = router;
