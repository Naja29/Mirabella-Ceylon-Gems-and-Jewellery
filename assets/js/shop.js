'use strict';

/* Sort select → URL navigation */
const Sort = (() => {
  function init() {
    const select = document.getElementById('sortBy');
    if (!select) return;
    select.addEventListener('change', () => {
      const base   = select.dataset.baseUrl || 'shop.php';
      const sep    = base.includes('?') ? '&' : '?';
      window.location.href = base + sep + 'sort=' + encodeURIComponent(select.value);
    });
  }
  return { init };
})();


/* Price presets → update form inputs */
const PriceFilter = (() => {
  function init() {
    const presets  = document.querySelectorAll('.price-preset');
    const minInput = document.getElementById('priceMin');
    const maxInput = document.getElementById('priceMax');
    if (!presets.length) return;

    presets.forEach(btn => {
      btn.addEventListener('click', () => {
        presets.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (minInput) minInput.value = btn.dataset.min;
        if (maxInput) maxInput.value = btn.dataset.max;
      });
    });
  }
  return { init };
})();


/* View Toggle (2-col / 3-col) */
const ViewToggle = (() => {
  function init() {
    const btns = document.querySelectorAll('.view-btn');
    const grid = document.getElementById('shopGrid');
    if (!btns.length || !grid) return;

    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        grid.classList.toggle('grid-2col', btn.dataset.view === 'grid2');
      });
    });
  }
  return { init };
})();


/* Mobile Sidebar */
const MobileSidebar = (() => {
  function init() {
    const toggleBtn = document.getElementById('filterToggleBtn');
    const closeBtn  = document.getElementById('sidebarClose');
    const sidebar   = document.getElementById('shopSidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    if (!toggleBtn || !sidebar) return;

    function open() {
      sidebar.classList.add('open');
      if (overlay) overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    toggleBtn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (overlay)  overlay.addEventListener('click', close);
  }
  return { init };
})();


/* Init */
function bootShop() {
  Sort.init();
  PriceFilter.init();
  ViewToggle.init();
  MobileSidebar.init();
}

document.addEventListener('mc:ready',        bootShop);
document.addEventListener('DOMContentLoaded', bootShop);
