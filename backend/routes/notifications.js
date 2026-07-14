const express = require('express');
const router = express.Router();
const { protect } = require('../middleware/auth');
const ctrl = require('../controllers/notificationController');

// All notification routes require authentication
router.use(protect);

// ─── Notification CRUD ────────────────────────────────────────
router.get('/', ctrl.getNotifications);
router.get('/unread-count', ctrl.getUnreadCount);
router.get('/stats', ctrl.getStats);
router.patch('/read-all', ctrl.markAllAsRead);
router.delete('/clear-all', ctrl.clearAllNotifications);
router.patch('/:id/read', ctrl.markAsRead);
router.delete('/:id', ctrl.deleteNotification);

// ─── Web Push ────────────────────────────────────────────────
router.get('/vapid-public-key', ctrl.getVapidPublicKey);
router.post('/subscribe', ctrl.subscribe);
router.delete('/unsubscribe', ctrl.unsubscribe);

// ─── Admin / Test ────────────────────────────────────────────
router.post('/test', ctrl.sendTestNotification);

module.exports = router;
