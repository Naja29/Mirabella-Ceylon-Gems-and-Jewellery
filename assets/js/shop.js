'use strict';

/* Category Filter */
const CategoryFilter = (() => {
  function init() {
    const tabs  = document.querySelectorAll('.cat-tab');
    const cards = document.querySelectorAll('#shopGrid .product-card');
    if (!tabs.length) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const filter = tab.dataset.filter;
        let visible = 0;

        cards.forEach(card => {
          const match = filter === 'all' || card.dataset.cat === filter;
          card.style.display = match ? '' : 'none';
          if (match) visible++;
        });

        updateCount(visible);
        toggleNoResults(visible === 0);
      });
    });

    // Read ?cat= from URL on load
    const params = new URLSearchParams(window.location.search);
    const cat    = params.get('cat');
    if (cat) {
      const match = [...tabs].find(t => t.dataset.filter === cat);
      if (match) match.click();
    }
  }
  return { init };
})();


/* Sort */
const Sort = (() => {
  function init() {
    const select = document.getElementById('sortBy');
    const grid   = document.getElementById('shopGrid');
    if (!select || !grid) return;

    select.addEventListener('change', () => {
      const cards  = [...grid.querySelectorAll('.product-card')];
      const method = select.value;

      cards.sort((a, b) => {
        const pa = parseInt(a.dataset.price || 0);
        const pb = parseInt(b.dataset.price || 0);
        if (method === 'price-asc')  return pa - pb;
        if (method === 'price-desc') return pb - pa;
        return 0; // newest / bestseller: keep original order
      });

      cards.forEach(c => grid.appendChild(c));
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


/* Price Presets */
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
      overlay && overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      sidebar.classList.remove('open');
      overlay && overlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    toggleBtn.addEventListener('click', open);
    closeBtn  && closeBtn.addEventListener('click', close);
    overlay   && overlay.addEventListener('click', close);
  }
  return { init };
})();


/* Clear Filters */
const ClearFilters = (() => {
  function init() {
    const btns = document.querySelectorAll('#clearFilters, #clearFiltersEmpty');
    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        // Reset category to "All"
        const allTab = document.querySelector('.cat-tab[data-filter="all"]');
        if (allTab) allTab.click();

        // Reset price presets
        const firstPreset = document.querySelector('.price-preset');
        if (firstPreset) firstPreset.click();

        // Uncheck all checkboxes
        document.querySelectorAll('.filter-check input').forEach(cb => { cb.checked = false; });

        // Reset inStock toggle
        const inStock = document.getElementById('inStockOnly');
        if (inStock) inStock.checked = true;
      });
    });
  }
  return { init };
})();


/* Pagination (visual only) */
const Pagination = (() => {
  function init() {
    const pageBtns = document.querySelectorAll('.page-btn:not(.page-btn--nav)');
    pageBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        pageBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });
  }
  return { init };
})();


/* Helpers */
function updateCount(n) {
  const el = document.getElementById('visibleCount');
  if (el) el.textContent = n;
}

function toggleNoResults(show) {
  const el   = document.getElementById('noResults');
  const grid = document.getElementById('shopGrid');
  if (!el || !grid) return;
  el.style.display   = show ? '' : 'none';
  grid.style.display = show ? 'none' : '';
}


/* Init */
document.addEventListener('mc:ready', () => {
  CategoryFilter.init();
  Sort.init();
  ViewToggle.init();
  PriceFilter.init();
  MobileSidebar.init();
  ClearFilters.init();
  Pagination.init();
});
