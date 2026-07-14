const webpush = require('web-push');
const PushSubscription = require('../models/PushSubscription');

// Configure VAPID details
webpush.setVapidDetails(
  process.env.VAPID_EMAIL || 'mailto:admin@hospilink.com',
  process.env.VAPID_PUBLIC_KEY,
  process.env.VAPID_PRIVATE_KEY
);

/**
 * Send a web push notification to a specific subscription
 */
const sendWebPush = async (subscription, payload) => {
  try {
    const pushPayload = JSON.stringify({
      title: payload.title,
      body: payload.body,
      icon: payload.icon || '/images/hospilink-icon.png',
      badge: '/images/badge-icon.png',
      tag: payload.tag || 'hospilink-notification',
      data: {
        url: payload.actionUrl || '/',
        notificationId: payload.notificationId,
        type: payload.type
      },
      actions: payload.actions || [],
      requireInteraction: payload.priority === 'urgent' || payload.priority === 'high',
      vibrate: payload.priority === 'urgent' ? [200, 100, 200, 100, 200] : [200, 100, 200]
    });

    await webpush.sendNotification(subscription, pushPayload);
    return { success: true };
  } catch (error) {
    if (error.statusCode === 404 || error.statusCode === 410) {
      // Subscription expired/invalid — mark inactive
      await PushSubscription.findOneAndUpdate(
        { endpoint: subscription.endpoint },
        { isActive: false }
      );
    }
    console.error('Web push error:', error.message);
    return { success: false, error: error.message };
  }
};

/**
 * Send push notification to all active subscriptions of a user
 */
const sendPushToUser = async (userId, payload) => {
  try {
    const subscriptions = await PushSubscription.find({
      user: userId,
      isActive: true
    });

    if (!subscriptions.length) {
      return { success: true, sent: 0, message: 'No active subscriptions' };
    }

    const results = await Promise.allSettled(
      subscriptions.map(sub => sendWebPush({
        endpoint: sub.endpoint,
        keys: sub.keys
      }, payload))
    );

    const sent = results.filter(r => r.status === 'fulfilled' && r.value.success).length;

    // Update lastUsed
    await PushSubscription.updateMany(
      { user: userId, isActive: true },
      { lastUsed: new Date() }
    );

    return { success: true, sent, total: subscriptions.length };
  } catch (error) {
    console.error('Push to user error:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Send push notification to multiple users by role
 */
const sendPushToRole = async (userIds, payload) => {
  const results = await Promise.allSettled(
    userIds.map(uid => sendPushToUser(uid, payload))
  );
  return results;
};

/**
 * Save a new push subscription for a user
 */
const saveSubscription = async (userId, subscriptionData, userAgent) => {
  try {
    const existing = await PushSubscription.findOne({ endpoint: subscriptionData.endpoint });
    if (existing) {
      existing.user = userId;
      existing.keys = subscriptionData.keys;
      existing.isActive = true;
      existing.lastUsed = new Date();
      existing.userAgent = userAgent;
      await existing.save();
      return existing;
    }

    const sub = await PushSubscription.create({
      user: userId,
      endpoint: subscriptionData.endpoint,
      keys: subscriptionData.keys,
      userAgent,
      isActive: true
    });
    return sub;
  } catch (error) {
    console.error('Save subscription error:', error);
    throw error;
  }
};

/**
 * Remove a push subscription
 */
const removeSubscription = async (endpoint) => {
  return PushSubscription.findOneAndUpdate(
    { endpoint },
    { isActive: false }
  );
};

module.exports = {
  sendWebPush,
  sendPushToUser,
  sendPushToRole,
  saveSubscription,
  removeSubscription,
  getVapidPublicKey: () => process.env.VAPID_PUBLIC_KEY
};
