/**
 * HospiLink Service Worker
 * Handles web push notifications
 */

const CACHE_NAME = 'hospilink-v1';
const ICON_URL = '/images/hospilink-icon.png';

// ─── Push Event ───────────────────────────────────────────────────
self.addEventListener('push', (event) => {
  if (!event.data) return;

  let data;
  try {
    data = event.data.json();
  } catch (e) {
    data = {
      title: 'HospiLink Notification',
      body: event.data.text(),
      data: {}
    };
  }

  const title = data.title || 'HospiLink';
  const options = {
    body: data.body || '',
    icon: data.icon || ICON_URL,
    badge: '/images/badge-icon.png',
    tag: data.tag || 'hospilink-' + Date.now(),
    data: data.data || {},
    requireInteraction: data.requireInteraction || false,
    vibrate: data.vibrate || [200, 100, 200],
    actions: data.actions || [],
    silent: false,
    timestamp: Date.now()
  };

  // Add type-specific styling
  const typeIcons = {
    drip_reminder:       '💉',
    medicine_reminder:   '💊',
    routine_check:       '🩺',
    followup_doctor:     '📅',
    followup_patient:    '📆',
    appointment_reminder:'📆',
    daily_digest:        '🏥',
    system:              '🔔'
  };

  const type = data.data?.type || 'system';
  if (typeIcons[type]) {
    options.body = `${typeIcons[type]} ${options.body}`;
  }

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// ─── Notification Click ───────────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const notifData = event.notification.data || {};
  const actionUrl = notifData.url || '/';

  if (event.action === 'close') return;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      // Try to focus an existing window
      for (const client of windowClients) {
        if (client.url.includes(location.origin) && 'focus' in client) {
          client.postMessage({
            type: 'NOTIFICATION_CLICKED',
            notificationId: notifData.notificationId,
            actionUrl
          });
          return client.focus();
        }
      }
      // Open new window if none found
      if (clients.openWindow) {
        return clients.openWindow(actionUrl);
      }
    })
  );
});

// ─── Notification Close ───────────────────────────────────────────
self.addEventListener('notificationclose', (event) => {
  // Analytics: can log dismissed notifications here
  console.log('[SW] Notification closed:', event.notification.tag);
});

// ─── Activate ─────────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(clients.claim());
  console.log('[SW] HospiLink Service Worker activated');
});

// ─── Install ──────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
  self.skipWaiting();
  console.log('[SW] HospiLink Service Worker installed');
});
