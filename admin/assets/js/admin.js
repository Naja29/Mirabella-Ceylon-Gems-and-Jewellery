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
  });

  document.addEventListener('click', function () {
    dropdown.classList.remove('open');
  });
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
