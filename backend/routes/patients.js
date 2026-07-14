const express = require('express');
const router = express.Router();
const {
  admitPatient,
  getAllPatients,
  getPatient,
  updatePatientAdmission,
  dischargePatient,
  getPatientHistory,
  getPatientStats
} = require('../controllers/patientController');
const { protect, authorize } = require('../middleware/auth');

router.use(protect);

router.post('/admit', authorize('staff', 'nurse', 'doctor', 'admin'), admitPatient);
router.get('/stats/summary', authorize('staff', 'admin'), getPatientStats);

router.route('/')
  .get(authorize('staff', 'nurse', 'doctor', 'admin'), getAllPatients);

router.route('/:id')
  .get(getPatient)
  .put(authorize('staff', 'nurse', 'doctor'), updatePatientAdmission);

router.put('/:id/discharge', authorize('doctor', 'staff', 'admin'), dischargePatient);
router.get('/:id/history', getPatientHistory);

module.exports = router;
