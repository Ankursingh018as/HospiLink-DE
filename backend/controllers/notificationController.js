const Notification = require('../models/Notification');
const PushSubscription = require('../models/PushSubscription');
const { saveSubscription, removeSubscription, getVapidPublicKey } = require('../utils/webPushService');
const notificationService = require('../utils/notificationService');
const User = require('../models/User');

// ─── GET /api/notifications ──────────────────────────────────────
// Get paginated notifications for current user
exports.getNotifications = async (req, res) => {
  try {
    const { page = 1, limit = 20, type, unreadOnly } = req.query;
    const filter = { recipient: req.user.id };

    if (type) filter.type = type;
    if (unreadOnly === 'true') filter.isRead = false;

    const notifications = await Notification.find(filter)
      .sort({ createdAt: -1 })
      .limit(Number(limit))
      .skip((Number(page) - 1) * Number(limit))
      .lean();

    const total = await Notification.countDocuments(filter);
    const unreadCount = await Notification.countDocuments({ ...filter, isRead: false });

    res.json({
      success: true,
      data: {
        notifications,
        pagination: {
          current: Number(page),
          total: Math.ceil(total / Number(limit)),
          count: total
        },
        unreadCount
      }
    });
  } catch (error) {
    console.error('Get notifications error:', error);
    res.status(500).json({ success: false, message: 'Failed to fetch notifications' });
  }
};

// ─── GET /api/notifications/unread-count ───────────────────────
exports.getUnreadCount = async (req, res) => {
  try {
    const count = await Notification.countDocuments({
      recipient: req.user.id,
      isRead: false
    });
    res.json({ success: true, data: { count } });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Failed to get count' });
  }
};

// ─── PATCH /api/notifications/:id/read ─────────────────────────
exports.markAsRead = async (req, res) => {
  try {
    const notification = await Notification.findOneAndUpdate(
      { _id: req.params.id, recipient: req.user.id },
      { isRead: true, readAt: new Date() },
      { new: true }
    );

    if (!notification) {
      return res.status(404).json({ success: false, message: 'Notification not found' });
    }

    res.json({ success: true, data: notification });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Failed to mark as read' });
  }
};

// ─── PATCH /api/notifications/read-all ─────────────────────────
exports.markAllAsRead = async (req, res) => {
  try {
    const result = await Notification.updateMany(
      { recipient: req.user.id, isRead: false },
      { isRead: true, readAt: new Date() }
    );
    res.json({ success: true, data: { modifiedCount: result.modifiedCount } });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Failed to mark all as read' });
  }
};

// ─── DELETE /api/notifications/:id ─────────────────────────────
exports.deleteNotification = async (req, res) => {
  try {
    const notification = await Notification.findOneAndDelete({
      _id: req.params.id,
      recipient: req.user.id
    });

    if (!notification) {
      return res.status(404).json({ success: false, message: 'Notification not found' });
    }

    res.json({ success: true, message: 'Notification deleted' });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Failed to delete notification' });
  }
};

// ─── DELETE /api/notifications/clear-all ───────────────────────
exports.clearAllNotifications = async (req, res) => {
  try {
    await Notification.deleteMany({ recipient: req.user.id, isRead: true });
    res.json({ success: true, message: 'Read notifications cleared' });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Failed to clear notifications' });
  }
};

// ─── GET /api/notifications/vapid-public-key ───────────────────
exports.getVapidPublicKey = (req, res) => {
  const key = getVapidPublicKey();
  if (!key) {
    return res.status(503).json({ success: false, message: 'Web push not configured' });
  }
  res.json({ success: true, data: { publicKey: key } });
};

// ─── POST /api/notifications/subscribe ─────────────────────────
exports.subscribe = async (req, res) => {
  try {
    const { endpoint, keys } = req.body;

    if (!endpoint || !keys?.p256dh || !keys?.auth) {
      return res.status(400).json({
        success: false,
        message: 'Invalid subscription data. endpoint and keys (p256dh, auth) are required.'
      });
    }

    const userAgent = req.headers['user-agent'] || 'Unknown';
    const sub = await saveSubscription(req.user.id, { endpoint, keys }, userAgent);

    res.json({ success: true, message: 'Push subscription saved', data: { id: sub._id } });
  } catch (error) {
    console.error('Subscribe error:', error);
    res.status(500).json({ success: false, message: 'Failed to save subscription' });
  }
};

// ─── DELETE /api/notifications/unsubscribe ──────────────────────
exports.unsubscribe = async (req, res) => {
  try {
    const { endpoint } = req.body;
    if (!endpoint) {
      return res.status(400).json({ success: false, message: 'Endpoint required' });
    }
    await removeSubscription(endpoint);
    res.json({ success: true, message: 'Unsubscribed from push notifications' });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Failed to unsubscribe' });
  }
};

// ─── POST /api/notifications/test ──────────────────────────────
// Admin-only: send test notifications by role
exports.sendTestNotification = async (req, res) => {
  try {
    const { targetRole, type } = req.body;

    // Only admins can trigger test
    if (req.user.role !== 'admin') {
      return res.status(403).json({ success: false, message: 'Admin access required' });
    }

    const targets = targetRole
      ? await User.find({ role: targetRole, status: 'active' }).select('_id firstName lastName email role')
      : [req.user];

    const results = [];
    for (const target of targets) {
      const notification = await notificationService.createNotification({
        recipient: target._id,
        recipientRole: target.role,
        type: type || 'system',
        title: '🧪 Test Notification',
        message: `This is a test notification for role: ${target.role}. System working correctly! [SUCCESS]`,
        priority: 'low',
        iconType: '[NOTIFICATION]',
        sentViaEmail: false,
        sentViaPush: false
      });

      // Send push
      const { sendPushToUser } = require('../utils/webPushService');
      await sendPushToUser(target._id, {
        title: '🧪 HospiLink Test',
        body: `Test notification for ${target.role} role. System is working!`,
        tag: `test-${Date.now()}`,
        type: 'system',
        priority: 'low',
        notificationId: notification?._id?.toString()
      });

      results.push({ userId: target._id, role: target.role, status: 'sent' });
    }

    res.json({ success: true, message: `Test notifications sent to ${results.length} user(s)`, data: results });
  } catch (error) {
    console.error('Test notification error:', error);
    res.status(500).json({ success: false, message: 'Failed to send test notification' });
  }
};

// ─── GET /api/notifications/stats ──────────────────────────────
// Admin: notification system statistics
exports.getStats = async (req, res) => {
  try {
    if (req.user.role !== 'admin') {
      return res.status(403).json({ success: false, message: 'Admin access required' });
    }

    const [total, unread, byType, byRole, emailSent, pushSent] = await Promise.all([
      Notification.countDocuments({}),
      Notification.countDocuments({ isRead: false }),
      Notification.aggregate([
        { $group: { _id: '$type', count: { $sum: 1 } } },
        { $sort: { count: -1 } }
      ]),
      Notification.aggregate([
        { $group: { _id: '$recipientRole', count: { $sum: 1 } } }
      ]),
      Notification.countDocuments({ sentViaEmail: true }),
      Notification.countDocuments({ sentViaPush: true })
    ]);

    res.json({
      success: true,
      data: { total, unread, byType, byRole, emailSent, pushSent }
    });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Failed to get stats' });
  }
};
