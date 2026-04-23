'use strict';

/* Sidebar collapse toggle */
(function () {
  const sidebar  = document.getElementById('adminSidebar');
  const topbar   = document.getElementById('adminTopbar');
  const main     = document.getElementById('adminMain');
  const toggle   = document.getElementById('sidebarToggle');
  if (!sidebar || !toggle) return;

  const COLLAPSED_KEY = 'mc_sidebar_collapsed';
  const isMobile = () => window.innerWidth <= 768;

  function applyState(collapsed) {
    if (isMobile()) {
      sidebar.classList.toggle('mobile-open', !collapsed);
    } else {
      sidebar.classList.toggle('collapsed', collapsed);
      topbar  && topbar.classList.toggle('expanded', collapsed);
      main    && main.classList.toggle('expanded', collapsed);
    }
  }

  /* Restore saved state on desktop */
  const saved = localStorage.getItem(COLLAPSED_KEY) === 'true';
  if (!isMobile()) applyState(saved);

  toggle.addEventListener('click', function () {
    if (isMobile()) {
      const isOpen = sidebar.classList.contains('mobile-open');
      applyState(isOpen); // toggle
    } else {
      const isCollapsed = sidebar.classList.contains('collapsed');
      applyState(!isCollapsed);
      localStorage.setItem(COLLAPSED_KEY, String(!isCollapsed));
    }
  });

  /* Close mobile sidebar on outside click */
  document.addEventListener('click', function (e) {
    if (!isMobile()) return;
    if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
      sidebar.classList.remove('mobile-open');
    }
  });
})();


/* Topbar user dropdown */
(function () {
  const btn      = document.getElementById('topbarUser');
  const dropdown = document.getElementById('topbarDropdown');
  if (!btn || !dropdown) return;

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    dropdown.classList.toggle('open');
    document.getElementById('notifDropdown')?.classList.remove('open');
  });

  document.addEventListener('click', function () {
    dropdown.classList.remove('open');
  });
})();

/* Notification dropdown */
(function () {
  const btn      = document.getElementById('notifBtn');
  const dropdown = document.getElementById('notifDropdown');
  if (!btn || !dropdown) return;

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    dropdown.classList.toggle('open');
    document.getElementById('topbarDropdown')?.classList.remove('open');
  });

  document.addEventListener('click', function () {
    dropdown.classList.remove('open');
  });
})();


/* Notification badge system */
(function () {
  function setBadge(id, count) {
    var el = document.getElementById(id);
    if (!el) return;
    if (count > 0) {
      el.textContent = count > 99 ? '99+' : String(count);
      el.classList.add('visible');
    } else {
      el.classList.remove('visible');
    }
  }

  function applyNotifCounts(data) {
    var orders    = data.orders    || 0;
    var messages  = data.messages  || 0;
    var reviews   = data.reviews   || 0;
    var customers = data.customers || 0;
    var total     = data.total     || 0;

    setBadge('sbOrderBadge',    orders);
    setBadge('sbMsgBadge',      messages);
    setBadge('sbReviewBadge',   reviews);
    setBadge('sbCustomerBadge', customers);

    // Topbar bell
    var bell = document.getElementById('notifBellCount');
    if (bell) {
      bell.textContent = total > 9 ? '9+' : String(total);
      if (total > 0) {
        bell.style.display = 'inline-flex';
      } else {
        bell.style.display = 'none';
      }
    }

    // Keep a live copy so page scripts (e.g. messages mark-read) can decrement
    window.MC_NOTIF = { orders: orders, messages: messages, reviews: reviews, customers: customers, total: total };
  }

  // Expose globally
  window.applyNotifCounts = applyNotifCounts;

  // Fetch counts and update — called on load AND every 30s
  function pollCounts() {
    fetch('ajax/notif_counts.php', { credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) { if (d) applyNotifCounts(d); })
      .catch(function () {});
  }

  // Run immediately on load, then every 30 seconds
  pollCounts();
  setInterval(pollCounts, 30000);
})();


/* Auto-hide success/info alerts after 4s */
(function () {
  document.querySelectorAll('.alert-auto-hide').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity 0.4s';
      el.style.opacity    = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 4000);
  });
})();
