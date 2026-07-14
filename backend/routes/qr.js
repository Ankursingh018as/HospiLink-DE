const express = require('express');
const router = express.Router();
const {
  generateQR,
  scanQR,
  getScanHistory,
  verifyQR
} = require('../controllers/qrController');
const { protect, authorize } = require('../middleware/auth');

// Public route for verification
router.post('/verify', verifyQR);

// Protected routes
router.use(protect);

router.post('/generate', authorize('staff', 'nurse', 'admin'), generateQR);
router.post('/scan', authorize('staff', 'nurse', 'doctor', 'admin'), scanQR);
router.get('/scans/:admissionId', getScanHistory);

module.exports = router;
