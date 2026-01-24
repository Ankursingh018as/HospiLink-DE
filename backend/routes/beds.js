const express = require('express');
const router = express.Router();
const {
  getAllBeds,
  getBed,
  createBed,
  assignBed,
  releaseBed,
  updateBed,
  deleteBed,
  getBedStats
} = require('../controllers/bedController');
const { protect, authorize } = require('../middleware/auth');

router.use(protect);

router.route('/')
  .get(getAllBeds)
  .post(authorize('admin'), createBed);

router.get('/stats/occupancy', getBedStats);

router.route('/:id')
  .get(getBed)
  .put(authorize('admin', 'staff', 'nurse'), updateBed)
  .delete(authorize('admin'), deleteBed);

router.put('/:id/assign', authorize('staff', 'nurse', 'admin'), assignBed);
router.put('/:id/release', authorize('staff', 'nurse', 'admin'), releaseBed);

module.exports = router;
