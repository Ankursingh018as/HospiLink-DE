/**
 * HospiLink Notification Panel UI
 * Beautiful sliding notification tray injected into all dashboards
 * Depends on: js/notifications.js (loaded first)
 */

class NotificationPanel {
  constructor(options = {}) {
    this.role = options.role || window.HOSPILINK_USER_ROLE || 'patient';
    this.roleConfig = this.getRoleConfig();
    this.isOpen = false;
    this.nc = window.hospiNotifications;
    this.containerId = 'hl-notification-panel';
    this.currentFilter = 'all';
  }

  getRoleConfig() {
    const configs = {
      staff: {
        label: 'Nurse Notifications',
        color: '#f093fb',
        gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        types: ['drip_reminder', 'medicine_reminder', 'system'],
        welcomeMsg: 'Stay updated on IV drips & medicine schedules'
      },
      doctor: {
        label: 'Clinical Alerts',
        color: '#43e97b',
        gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        types: ['routine_check', 'followup_doctor', 'appointment_alert', 'system'],
        welcomeMsg: 'Patient check alerts and follow-up reminders'
      },
      patient: {
        label: 'Your Notifications',
        color: '#4facfe',
        gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        types: ['medicine_reminder', 'followup_patient', 'appointment_reminder', 'system'],
        welcomeMsg: 'Medicine reminders and appointment updates'
      },
      admin: {
        label: 'System Notifications',
        color: '#667eea',
        gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        types: ['daily_digest', 'system'],
        welcomeMsg: 'System alerts and daily digests'
      }
    };
    return configs[this.role] || configs.patient;
  }

  // ─── Inject CSS ──────────────────────────────────────────────────
  injectStyles() {
    if (document.getElementById('hl-notif-styles')) return;
    const style = document.createElement('style');
    style.id = 'hl-notif-styles';
    style.textContent = `
      /* ── HospiLink Notification Panel ── */
      #hl-bell-btn {
        position: fixed;
        top: 18px;
        right: 24px;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: ${this.roleConfig.gradient};
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 9000;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        text-decoration: none;
      }
      #hl-bell-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 28px rgba(0,0,0,0.3);
      }
      #hl-bell-btn svg { color: white; }
      #hl-bell-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #e53e3e;
        color: white;
        font-size: 10px;
        font-weight: 800;
        min-width: 20px;
        height: 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        border: 2px solid white;
        animation: hl-bounce 0.4s ease;
        transition: all 0.3s ease;
      }
      #hl-bell-badge.hidden { display: none; }
      @keyframes hl-bounce {
        0% { transform: scale(0); }
        60% { transform: scale(1.3); }
        100% { transform: scale(1); }
      }

      /* ── Overlay ── */
      #hl-notif-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.3);
        z-index: 8998;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
      }
      #hl-notif-overlay.active {
        opacity: 1;
        pointer-events: all;
      }

      /* ── Panel ── */
      #hl-notification-panel {
        position: fixed;
        top: 0;
        right: -420px;
        width: 400px;
        height: 100vh;
        background: #0f1117;
        z-index: 8999;
        display: flex;
        flex-direction: column;
        transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -8px 0 40px rgba(0,0,0,0.4);
        font-family: 'Segoe UI', system-ui, sans-serif;
      }
      #hl-notification-panel.open { right: 0; }

      /* ── Panel Header ── */
      .hl-panel-header {
        background: ${this.roleConfig.gradient};
        padding: 20px 20px 16px;
        flex-shrink: 0;
      }
      .hl-panel-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 4px;
      }
      .hl-panel-title {
        color: white;
        font-size: 18px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .hl-panel-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
      }
      .hl-panel-close:hover { background: rgba(255,255,255,0.35); }
      .hl-panel-subtitle {
        color: rgba(255,255,255,0.8);
        font-size: 12px;
        margin-bottom: 12px;
      }
      .hl-panel-actions {
        display: flex;
        gap: 8px;
      }
      .hl-panel-action-btn {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        letter-spacing: 0.3px;
      }
      .hl-panel-action-btn:hover {
        background: rgba(255,255,255,0.3);
      }

      /* ── Filter Tabs ── */
      .hl-filter-tabs {
        display: flex;
        background: #1a1d27;
        border-bottom: 1px solid #2d3148;
        flex-shrink: 0;
        overflow-x: auto;
        scrollbar-width: none;
      }
      .hl-filter-tabs::-webkit-scrollbar { display: none; }
      .hl-filter-tab {
        flex-shrink: 0;
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 600;
        color: #718096;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      .hl-filter-tab.active {
        color: white;
        border-bottom-color: ${this.roleConfig.color};
      }
      .hl-filter-tab:hover:not(.active) { color: #a0aec0; }

      /* ── Notification List ── */
      .hl-notif-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0;
        scrollbar-width: thin;
        scrollbar-color: #2d3148 transparent;
      }
      .hl-notif-list::-webkit-scrollbar { width: 4px; }
      .hl-notif-list::-webkit-scrollbar-track { background: transparent; }
      .hl-notif-list::-webkit-scrollbar-thumb { background: #2d3148; border-radius: 4px; }

      /* ── Notification Item ── */
      .hl-notif-item {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: background 0.15s;
        border-bottom: 1px solid #1a1d27;
        position: relative;
        animation: hl-slide-in 0.3s ease;
      }
      @keyframes hl-slide-in {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
      }
      .hl-notif-item:hover { background: #1a1d27; }
      .hl-notif-item.unread { background: rgba(99,102,241,0.08); }
      .hl-notif-item.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        border-radius: 0 3px 3px 0;
        background: ${this.roleConfig.color};
      }
      .hl-notif-icon {
        font-size: 26px;
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #1a1d27;
      }
      .hl-notif-body { flex: 1; min-width: 0; }
      .hl-notif-title {
        font-size: 13px;
        font-weight: 600;
        color: #e2e8f0;
        line-height: 1.4;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .hl-notif-msg {
        font-size: 12px;
        color: #718096;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      .hl-notif-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
      }
      .hl-notif-time {
        font-size: 11px;
        color: #4a5568;
      }
      .hl-priority-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
      }
      .hl-notif-actions {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
        align-self: flex-start;
        margin-top: 2px;
        opacity: 0;
        transition: opacity 0.2s;
      }
      .hl-notif-item:hover .hl-notif-actions { opacity: 1; }
      .hl-notif-action {
        background: #2d3148;
        border: none;
        color: #a0aec0;
        width: 26px;
        height: 26px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
      }
      .hl-notif-action:hover { background: #3d4268; color: white; }
      .hl-notif-action.delete:hover { background: #c53030; color: white; }

      /* ── Empty State ── */
      .hl-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 280px;
        color: #4a5568;
        text-align: center;
        padding: 24px;
      }
      .hl-empty-icon { font-size: 52px; margin-bottom: 16px; opacity: 0.6; }
      .hl-empty-title { font-size: 15px; font-weight: 600; color: #718096; margin-bottom: 6px; }
      .hl-empty-sub { font-size: 13px; color: #4a5568; }

      /* ── Loading ── */
      .hl-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
        color: #4a5568;
        font-size: 13px;
        gap: 10px;
      }
      @keyframes hl-spin { to { transform: rotate(360deg); } }
      .hl-spinner {
        width: 18px;
        height: 18px;
        border: 2px solid #2d3148;
        border-top-color: ${this.roleConfig.color};
        border-radius: 50%;
        animation: hl-spin 0.8s linear infinite;
      }

      /* ── Panel Footer ── */
      .hl-panel-footer {
        padding: 12px 16px;
        border-top: 1px solid #1a1d27;
        flex-shrink: 0;
        background: #0a0c14;
      }
      .hl-push-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #718096;
      }
      .hl-push-status {
        display: flex;
        align-items: center;
        gap: 6px;
      }
      .hl-push-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #38a169;
        animation: hl-pulse 2s infinite;
      }
      .hl-push-dot.off { background: #4a5568; animation: none; }
      @keyframes hl-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
      }
      .hl-enable-push-btn {
        background: none;
        border: 1px solid #3d4268;
        color: ${this.roleConfig.color};
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
      }
      .hl-enable-push-btn:hover {
        background: ${this.roleConfig.color};
        color: white;
        border-color: ${this.roleConfig.color};
      }

      /* ── Toast ── */
      #hl-toast-container {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10000;
        display: flex;
        flex-direction: column-reverse;
        gap: 8px;
        pointer-events: none;
      }
      .hl-toast {
        background: #1a1d27;
        border: 1px solid #2d3148;
        border-left: 4px solid ${this.roleConfig.color};
        color: #e2e8f0;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        min-width: 280px;
        max-width: 380px;
        animation: hl-toast-in 0.3s ease;
        pointer-events: all;
        display: flex;
        align-items: center;
        gap: 10px;
      }
      @keyframes hl-toast-in {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
      }
      .hl-toast.fade-out {
        animation: hl-toast-out 0.3s ease forwards;
      }
      @keyframes hl-toast-out {
        to { opacity: 0; transform: translateY(10px) scale(0.95); }
      }

      @media (max-width: 480px) {
        #hl-notification-panel { width: 100%; right: -100%; }
        #hl-bell-btn { top: 12px; right: 12px; }
      }
    `;
    document.head.appendChild(style);
  }

  // ─── Build HTML ──────────────────────────────────────────────────
  buildPanel() {
    // Bell button
    const bell = document.createElement('button');
    bell.id = 'hl-bell-btn';
    bell.setAttribute('aria-label', 'Notifications');
    bell.setAttribute('title', 'Notifications');
    bell.innerHTML = `
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <span id="hl-bell-badge" class="hidden">0</span>
    `;
    bell.addEventListener('click', () => this.toggle());
    document.body.appendChild(bell);

    // Overlay
    const overlay = document.createElement('div');
    overlay.id = 'hl-notif-overlay';
    overlay.addEventListener('click', () => this.close());
    document.body.appendChild(overlay);

    // Panel
    const panel = document.createElement('div');
    panel.id = 'hl-notification-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Notification Panel');
    panel.innerHTML = `
      <div class="hl-panel-header">
        <div class="hl-panel-header-top">
          <div class="hl-panel-title">🔔 ${this.roleConfig.label}</div>
          <button class="hl-panel-close" id="hl-panel-close-btn" aria-label="Close">✕</button>
        </div>
        <div class="hl-panel-subtitle">${this.roleConfig.welcomeMsg}</div>
        <div class="hl-panel-actions">
          <button class="hl-panel-action-btn" id="hl-mark-all-read">✓ Mark All Read</button>
          <button class="hl-panel-action-btn" id="hl-refresh-btn">↻ Refresh</button>
        </div>
      </div>

      <div class="hl-filter-tabs" id="hl-filter-tabs">
        <div class="hl-filter-tab active" data-filter="all">All</div>
        <div class="hl-filter-tab" data-filter="unread">Unread</div>
        ${this.roleConfig.types.map(t => `
          <div class="hl-filter-tab" data-filter="${t}">${this.getTabLabel(t)}</div>
        `).join('')}
      </div>

      <div class="hl-notif-list" id="hl-notif-list">
        <div class="hl-loading"><div class="hl-spinner"></div> Loading...</div>
      </div>

      <div class="hl-panel-footer">
        <div class="hl-push-toggle">
          <div class="hl-push-status" id="hl-push-status">
            <div class="hl-push-dot off" id="hl-push-dot"></div>
            <span id="hl-push-label">Push notifications off</span>
          </div>
          <button class="hl-enable-push-btn" id="hl-enable-push">Enable Push</button>
        </div>
      </div>
    `;
    document.body.appendChild(panel);

    // Toast container
    const toastContainer = document.createElement('div');
    toastContainer.id = 'hl-toast-container';
    document.body.appendChild(toastContainer);

    // Wire up events
    document.getElementById('hl-panel-close-btn').addEventListener('click', () => this.close());
    document.getElementById('hl-mark-all-read').addEventListener('click', () => this.onMarkAllRead());
    document.getElementById('hl-refresh-btn').addEventListener('click', () => this.onRefresh());
    document.getElementById('hl-enable-push').addEventListener('click', () => this.onEnablePush());

    // Filter tabs
    document.querySelectorAll('.hl-filter-tab').forEach(tab => {
      tab.addEventListener('click', (e) => {
        document.querySelectorAll('.hl-filter-tab').forEach(t => t.classList.remove('active'));
        e.target.classList.add('active');
        this.currentFilter = e.target.dataset.filter;
        this.renderList();
      });
    });

    this.updatePushStatus();
  }

  getTabLabel(type) {
    const labels = {
      drip_reminder: '💉 Drips',
      medicine_reminder: '💊 Meds',
      routine_check: '🩺 Checks',
      followup_doctor: '📅 Follow-ups',
      followup_patient: '📆 Follow-ups',
      appointment_reminder: '📆 Appts',
      appointment_alert: '📅 Appts',
      daily_digest: '🏥 Digest',
      system: '🔔 System'
    };
    return labels[type] || type;
  }

  // ─── Render List ─────────────────────────────────────────────────
  renderList() {
    const list = document.getElementById('hl-notif-list');
    if (!list) return;

    let filtered = this.nc.notifications;
    if (this.currentFilter && this.currentFilter !== 'all') {
      if (this.currentFilter === 'unread') {
        filtered = filtered.filter(n => !n.isRead);
      } else {
        filtered = filtered.filter(n => n.type === this.currentFilter);
      }
    }

    if (filtered.length === 0) {
      list.innerHTML = `
        <div class="hl-empty">
          <div class="hl-empty-icon">🔕</div>
          <div class="hl-empty-title">All caught up!</div>
          <div class="hl-empty-sub">No notifications to show.</div>
        </div>`;
      return;
    }

    list.innerHTML = filtered.map(notif => `
      <div class="hl-notif-item ${!notif.isRead ? 'unread' : ''}"
           data-id="${notif._id}"
           role="article"
           aria-label="${notif.title}">
        <div class="hl-notif-icon">${this.nc.getTypeIcon(notif.type)}</div>
        <div class="hl-notif-body">
          <div class="hl-notif-title">${this.escapeHtml(notif.title)}</div>
          <div class="hl-notif-msg">${this.escapeHtml(notif.message)}</div>
          <div class="hl-notif-meta">
            <div class="hl-priority-dot" style="background:${this.nc.getPriorityColor(notif.priority)}"></div>
            <span class="hl-notif-time">${this.nc.formatTimeAgo(notif.createdAt || notif.created_at)}</span>
          </div>
        </div>
        <div class="hl-notif-actions">
          ${!notif.isRead ? `<button class="hl-notif-action read-btn" data-id="${notif._id}" title="Mark as read">✓</button>` : ''}
          <button class="hl-notif-action delete delete-btn" data-id="${notif._id}" title="Delete">✕</button>
        </div>
      </div>
    `).join('');

    // Wire item events
    list.querySelectorAll('.hl-notif-item').forEach(item => {
      item.addEventListener('click', (e) => {
        if (e.target.closest('.hl-notif-actions')) return;
        const id = item.dataset.id;
        this.nc.markRead(id);
        item.classList.remove('unread');
      });
    });

    list.querySelectorAll('.read-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        this.nc.markRead(btn.dataset.id);
        const item = btn.closest('.hl-notif-item');
        if (item) item.classList.remove('unread');
        btn.remove();
      });
    });

    list.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const item = btn.closest('.hl-notif-item');
        if (item) {
          item.style.opacity = '0';
          item.style.transform = 'translateX(20px)';
          item.style.transition = 'all 0.25s ease';
          setTimeout(() => {
            this.nc.deleteNotification(btn.dataset.id);
          }, 250);
        }
      });
    });
  }

  // ─── Badge ───────────────────────────────────────────────────────
  updateBadge(count) {
    const badge = document.getElementById('hl-bell-badge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  }

  // ─── Push Status ─────────────────────────────────────────────────
  async updatePushStatus() {
    const dot = document.getElementById('hl-push-dot');
    const label = document.getElementById('hl-push-label');
    const btn = document.getElementById('hl-enable-push');

    if (!('Notification' in window)) {
      if (label) label.textContent = 'Push not supported';
      if (btn) btn.style.display = 'none';
      return;
    }

    if (Notification.permission === 'granted') {
      if (dot) { dot.classList.remove('off'); }
      if (label) label.textContent = 'Push notifications active';
      if (btn) btn.textContent = '✓ Enabled';
    } else if (Notification.permission === 'denied') {
      if (label) label.textContent = 'Push notifications blocked';
      if (btn) { btn.textContent = 'Unblocked in settings'; btn.disabled = true; }
    }
  }

  // ─── Handlers ────────────────────────────────────────────────────
  async onMarkAllRead() {
    await this.nc.markAllRead();
    this.renderList();
    this.toast('✓ All notifications marked as read');
  }

  async onRefresh() {
    const list = document.getElementById('hl-notif-list');
    if (list) list.innerHTML = '<div class="hl-loading"><div class="hl-spinner"></div> Refreshing...</div>';
    await this.nc.fetchNotifications();
    this.renderList();
  }

  async onEnablePush() {
    const granted = await this.nc.requestPushPermission();
    if (granted) {
      this.updatePushStatus();
      this.toast('🔔 Push notifications enabled!');
    }
  }

  // ─── Open / Close / Toggle ───────────────────────────────────────
  open() {
    const panel = document.getElementById(this.containerId);
    const overlay = document.getElementById('hl-notif-overlay');
    if (panel) panel.classList.add('open');
    if (overlay) overlay.classList.add('active');
    this.isOpen = true;
    this.nc.fetchNotifications().then(() => this.renderList());
    document.addEventListener('keydown', this.handleKeydown);
  }

  close() {
    const panel = document.getElementById(this.containerId);
    const overlay = document.getElementById('hl-notif-overlay');
    if (panel) panel.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    this.isOpen = false;
    document.removeEventListener('keydown', this.handleKeydown);
  }

  toggle() {
    this.isOpen ? this.close() : this.open();
  }

  handleKeydown = (e) => {
    if (e.key === 'Escape') this.close();
  };

  // ─── Toast ───────────────────────────────────────────────────────
  toast(message, duration = 3000) {
    const container = document.getElementById('hl-toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'hl-toast';
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('fade-out');
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }

  // ─── Escape HTML ─────────────────────────────────────────────────
  escapeHtml(str = '') {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ─── Mount ───────────────────────────────────────────────────────
  async mount() {
    this.injectStyles();
    this.buildPanel();
    this.currentFilter = 'all';

    // Listen to notification center events
    this.nc.on('badge',   (count) => this.updateBadge(count));
    this.nc.on('updated', ()      => { if (this.isOpen) this.renderList(); });

    // Initialize notification center (fetches from PHP API)
    await this.nc.init();
  }
}

// ─── Auto-mount if role is in page ───────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const roleEl = document.querySelector('[data-user-role]');
  const role = roleEl?.dataset.userRole || window.HOSPILINK_USER_ROLE || 'patient';

  const panel = new NotificationPanel({ role });
  panel.mount();
  window.hospiPanel = panel;
});
