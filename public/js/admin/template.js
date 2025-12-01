// =================================================================
// BAGIAN 1: SIDEBAR, NAVBAR, LOGOUT (LOGIKA ASLI - TIDAK DIUBAH)
// =================================================================
(() => {
  const layout = document.getElementById('layoutRoot');
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (!layout || !toggle || !sidebar) {
    return;
  }

  const COLLAPSED_CLASS = 'sidebar-collapsed';
  const OPEN_CLASS = 'sidebar-open';

  const isMobile = () => window.matchMedia('(max-width: 640px)').matches;

  const applyInitialState = () => {
    if (isMobile()) {
      layout.classList.remove(COLLAPSED_CLASS);
      layout.classList.remove(OPEN_CLASS);
      document.body.classList.remove('no-scroll');
      toggle.setAttribute('aria-expanded', 'false');
    } else {
      layout.classList.remove(OPEN_CLASS);
      document.body.classList.remove('no-scroll');
      const narrow = window.matchMedia('(max-width: 980px)').matches;
      if (narrow) {
        layout.classList.add(COLLAPSED_CLASS);
        toggle.setAttribute('aria-expanded', 'false');
      } else {
        layout.classList.remove(COLLAPSED_CLASS);
        toggle.setAttribute('aria-expanded', 'true');
      }
    }
  };

  applyInitialState();
  window.addEventListener('resize', () => {
    applyInitialState();
  });

  toggle.addEventListener('click', () => {
    if (isMobile()) {
      const isOpen = layout.classList.toggle(OPEN_CLASS);
      toggle.setAttribute('aria-expanded', String(isOpen));
      if (isOpen) {
        document.body.classList.add('no-scroll');
      } else {
        document.body.classList.remove('no-scroll');
      }
    } else {
      const isCollapsed = layout.classList.toggle(COLLAPSED_CLASS);
      toggle.setAttribute('aria-expanded', String(!isCollapsed));
    }
  });

  if (backdrop) {
    backdrop.addEventListener('click', () => {
      if (!isMobile()) return;
      layout.classList.remove(OPEN_CLASS);
      document.body.classList.remove('no-scroll');
      toggle.setAttribute('aria-expanded', 'false');
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isMobile() && layout.classList.contains(OPEN_CLASS)) {
      layout.classList.remove(OPEN_CLASS);
      document.body.classList.remove('no-scroll');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });

  // Auto highlight active nav by URL
  const setActiveNav = () => {
    const links = Array.from(sidebar.querySelectorAll('a.nav__item[href]'));
    if (!links.length) return;
    const current = window.location.pathname.replace(/\/+$/, '');
    let matched = false;
    links.forEach((a) => {
      a.classList.remove('is-active');
      try {
        const path = new URL(a.getAttribute('href'), window.location.origin).pathname.replace(/\/+$/, '');
        if (path && path === current) {
          a.classList.add('is-active');
          matched = true;
        }
      } catch (_) { /* ignore */ }
    });
  };
  setActiveNav();

  // --- KONFIRMASI LOGOUT ---
  const logoutForm = document.getElementById('logoutForm');
  if (logoutForm) {
    logoutForm.addEventListener('submit', function (e) {
      e.preventDefault(); 
      const confirmation = window.confirm('Apakah Anda yakin ingin keluar?');
      if (confirmation) {
        this.submit();
      }
    });
  }
})();

// =================================================================
// BAGIAN 2: NOTIFIKASI REAL-TIME (DIPERBAIKI & HANDLE ERROR)
// =================================================================
(() => {
  const dropdownToggle = document.getElementById('notificationDropdownToggle');
  const dropdown = document.querySelector('.navbar__notification.dropdown');
  const dropdownMenu = document.getElementById('notificationDropdownMenu');
  const notificationList = document.getElementById('notificationList');
  const notificationBadge = document.getElementById('notificationBadge');
  const filterBtns = document.querySelectorAll('.filter-btn');

  if (!dropdownToggle || !dropdown || !notificationList) return;

  let notificationsData = [];
  let currentFilter = 'all';

  // Helper: Mendapatkan Icon SVG berdasarkan kategori
  const getIcon = (type, category) => {
      // Icon Keamanan (Shield)
      if (category === 'keamanan') return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
      // Icon Acara (Calendar)
      if (category === 'acara') return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
      // Icon Absensi / Info Default (Check/Alert)
      return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
  };

  const fetchNotifications = async () => {
      try {
          const res = await fetch('/admin/notifications', {
              headers: { 'Accept': 'application/json' }
          });
          
          if (res.ok) {
              const json = await res.json();
              if (json.success) {
                  notificationsData = json.data || [];
                  updateBadge(json.count); 
                  renderNotifications();   
              }
          } else {
             throw new Error(`Server Error: ${res.status}`);
          }
      } catch (e) {
          console.error('Gagal memuat notifikasi:', e);

          notificationList.innerHTML = `
            <div class="empty-notifications" style="padding: 20px; text-align: center;">
                <p style="font-size:12px; color:#64748b;">Gagal memuat data.</p>
            </div>`;
      }
  };

  const updateBadge = (count) => {
      if (notificationBadge) {
          notificationBadge.textContent = count;
          notificationBadge.style.display = (count > 0) ? 'flex' : 'none';
      }
  };

  const renderNotifications = () => {
    const filtered = currentFilter === 'all' 
      ? notificationsData 
      : notificationsData.filter(n => n.category === currentFilter);

    if (filtered.length === 0) {
      notificationList.innerHTML = `
        <div class="empty-notifications">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
          </svg>
          <p>Tidak ada notifikasi</p>
        </div>
      `;
      return;
    }

    notificationList.innerHTML = filtered.map(notif => `
      <a href="${notif.url}" class="notification-item ${notif.read_at ? 'read' : 'unread'}" data-id="${notif.id}">
        <div class="notification-icon notification-icon--${notif.type}">
          ${getIcon(notif.type, notif.category)}
        </div>
        <div class="notification-content">
          <p class="notification-title">${notif.title}</p>
          <span class="notification-time">${notif.time}</span>
        </div>
        ${!notif.read_at ? '<span class="dot-unread"></span>' : ''}
      </a>
    `).join('');
  };

  const markAllRead = async () => {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      try {
          await fetch('/admin/notifications/read-all', {
              method: 'POST',
              headers: { 
                  'X-CSRF-TOKEN': token,
                  'Content-Type': 'application/json'
              }
          });
          updateBadge(0);
      } catch (e) { console.error(e); }
  };

  dropdownToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = dropdown.classList.toggle('is-open');
    dropdownToggle.setAttribute('aria-expanded', String(isOpen));
    
    if (isOpen) {
      fetchNotifications(); 
      markAllRead();        
    }
  });

  const closeDropdown = () => {
    dropdown.classList.remove('is-open');
    dropdownToggle.setAttribute('aria-expanded', 'false');
  };

  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target)) closeDropdown();
  });

  if (dropdownMenu) dropdownMenu.addEventListener('click', (e) => e.stopPropagation());

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.getAttribute('data-filter');
      renderNotifications();
    });
  });

  const settingsLink = dropdown.querySelector('.settings-link');
  if (settingsLink) {
    settingsLink.addEventListener('click', () => closeDropdown());
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dropdown.classList.contains('is-open')) {
      closeDropdown();
    }
  });

  fetchNotifications();

  setInterval(fetchNotifications, 4000);
})();