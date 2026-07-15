/**
 * HospiLink Notifications Core — PHP/MySQL Edition
 * Polls /php/notifications_api.php and drives the notification panel
 * Loaded before notificationPanel.js on all dashboards
 */

class HospiLinkNotifications {
  constructor() {
    this.notifications  = [];
    this.unreadCount    = 0;
    this.pollInterval   = null;
    this.listeners      = {};
    this.apiBase        = '/HospiLink-DE/php/notifications_api.php';
    this.pollMs         = 30000; // 30-second refresh
  }

  // ── Event bus ──────────────────────────────────────────────────
  on(event, cb) {
    if (!this.listeners[event]) this.listeners[event] = [];
    this.listeners[event].push(cb);
  }
  emit(event, data) {
    (this.listeners[event] || []).forEach(cb => cb(data));
  }

  // ── Normalise PHP row → panel-compatible object ─────────────────
  normalise(row) {
    return {
      _id:         String(row.id),
      id:          String(row.id),
      type:        row.type        || 'system',
      title:       row.title       || '',
      message:     row.message     || '',
      priority:    this.typeToPriority(row.type),
      isRead:      row.is_read === true || row.is_read == 1,
      createdAt:   row.created_at,
      createdFmt:  row.created_fmt || row.created_at || '',
      sentViaEmail: false,
      sentViaPush:  false,
      admission_id: row.admission_id
    };
  }

  typeToPriority(type) {
    const map = {
      drip:     'urgent',
      iv:       'urgent',
      medicine: 'high',
      task:     'medium',
      note:     'medium',
      system:   'low',
    };
    for (const [key, val] of Object.entries(map)) {
      if (String(type).includes(key)) return val;
    }
    return 'medium';
  }

  // ── Fetch notifications from PHP API ───────────────────────────
  async fetchNotifications() {
    try {
      const res  = await fetch(this.apiBase + '?action=list', { credentials: 'same-origin' });
      const data = await res.json();
      if (data.success && Array.isArray(data.notifications)) {
        this.notifications = data.notifications.map(n => this.normalise(n));
        this.unreadCount   = this.notifications.filter(n => !n.isRead).length;
        this.emit('badge',   this.unreadCount);
        this.emit('updated', this.notifications);
      }
    } catch (err) {
      console.warn('[HospiLink] fetchNotifications error:', err.message);
    }
  }

  // ── Badge count only (lightweight poll) ─────────────────────────
  async fetchCount() {
    try {
      const res  = await fetch(this.apiBase + '?action=count', { credentials: 'same-origin' });
      const data = await res.json();
      const count = data.count ?? 0;
      if (count !== this.unreadCount) {
        this.unreadCount = count;
        this.emit('badge', count);
        if (count > 0) await this.fetchNotifications();
      }
    } catch (_) {}
  }

  // ── Mark single read ──────────────────────────────────────────
  async markRead(id) {
    const notif = this.notifications.find(n => n._id === String(id));
    if (notif && !notif.isRead) {
      notif.isRead = true;
      this.unreadCount = Math.max(0, this.unreadCount - 1);
      this.emit('badge', this.unreadCount);
      try {
        await fetch(`${this.apiBase}?action=read&id=${id}`, {
          method: 'GET', credentials: 'same-origin'
        });
      } catch (_) {}
    }
  }

  // ── Mark all read ─────────────────────────────────────────────
  async markAllRead() {
    this.notifications.forEach(n => n.isRead = true);
    this.unreadCount = 0;
    this.emit('badge', 0);
    this.emit('updated', this.notifications);
    try {
      await fetch(`${this.apiBase}?action=read_all`, {
        method: 'GET', credentials: 'same-origin'
      });
    } catch (_) {}
  }

  // ── Delete (local only — no API endpoint needed for now) ──────
  deleteNotification(id) {
    const idx = this.notifications.findIndex(n => n._id === String(id));
    if (idx !== -1) {
      const was = this.notifications[idx].isRead;
      this.notifications.splice(idx, 1);
      if (!was) this.unreadCount = Math.max(0, this.unreadCount - 1);
      this.emit('badge',   this.unreadCount);
      this.emit('updated', this.notifications);
    }
  }

  // ── Format time ───────────────────────────────────────────────
  formatTimeAgo(ts) {
    if (!ts) return '';
    const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
    if (diff < 60)    return 'Just now';
    if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
  }

  // ── Icon by type ─────────────────────────────────────────────
  getTypeIcon(type) {
    const icons = {
      drip:       '<i class="ri-syringe-line"></i>',
      iv:         '<i class="ri-syringe-line"></i>',
      medicine:   '<i class="ri-capsule-line"></i>',
      task:       '<i class="ri-file-list-3-line"></i>',
      note:       '<i class="ri-stethoscope-line"></i>',
      followup:   '<i class="ri-calendar-line"></i>',
      appointment:'<i class="ri-calendar-event-line"></i>',
      digest:     '<i class="ri-hospital-line"></i>',
      system:     '<i class="ri-notification-line"></i>',
    };
    for (const [key, icon] of Object.entries(icons)) {
      if (String(type).includes(key)) return icon;
    }
    return '<i class="ri-notification-line"></i>';
  }

  // ── Priority colour ──────────────────────────────────────────
  getPriorityColor(priority) {
    const colors = {
      urgent: '#e53e3e',
      high:   '#dd6b20',
      medium: '#3182ce',
      low:    '#38a169',
    };
    return colors[priority] || colors.medium;
  }

  // ── Web Push ─────────────────────────────────────────────────
  async requestPushPermission() {
    if (!('Notification' in window)) return false;
    try {
      const result = await Notification.requestPermission();
      return result === 'granted';
    } catch (_) {
      return false;
    }
  }

  // ── Init ─────────────────────────────────────────────────────
  async init() {
    await this.fetchNotifications();

    // Poll every 30 s for badge count; full fetch only if new items
    this.pollInterval = setInterval(() => this.fetchCount(), this.pollMs);

    // Register service worker for background push (if supported)
    if ('serviceWorker' in navigator) {
      try {
        await navigator.serviceWorker.register('/HospiLink-DE/sw.js');
      } catch (_) {}
    }
  }

  destroy() {
    if (this.pollInterval) clearInterval(this.pollInterval);
  }
}

// Expose singleton
window.hospiNotifications = new HospiLinkNotifications();
