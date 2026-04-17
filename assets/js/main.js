'use strict';

/* Page Loader */
const Loader = (() => {
  function init() {
    const loader = document.getElementById('pageLoader');
    if (!loader) return;

    window.addEventListener('load', () => {
      setTimeout(() => {
        loader.classList.add('hidden');
        document.body.classList.remove('page-loading');
        document.body.classList.add('page-ready');
      }, 1900);
    });
  }
  return { init };
})();


/* Header */
const Header = (() => {
  const TOPBAR_H = 38;

  function init() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    function update() {
      const solid = window.scrollY > TOPBAR_H;
      header.classList.toggle('is-solid',       solid);
      header.classList.toggle('is-transparent', !solid);
    }
    update();
    window.addEventListener('scroll', update, { passive: true });
  }
  return { init };
})();


/* Mobile Nav */
const MobileNav = (() => {
  function init() {
    const btn = document.getElementById('hamburger');
    const nav = document.getElementById('mobileNav');
    if (!btn || !nav) return;

    btn.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      btn.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', e => {
      if (!btn.contains(e.target) && !nav.contains(e.target)) {
        nav.classList.remove('open');
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }
  return { init };
})();


/*  Hero Slider */
const HeroSlider = (() => {
  let current = 0, total = 0, timer = null;
  const DELAY = 6000;
  let slides, dots, currentEl, progressBar;

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = (index + total) % total;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
    if (currentEl) currentEl.textContent = String(current + 1).padStart(2, '0');
    if (progressBar) {
      progressBar.classList.remove('running');
      void progressBar.offsetWidth;
      progressBar.classList.add('running');
    }
  }

  const next = () => goTo(current + 1);
  const prev = () => goTo(current - 1);

  function startAuto() { stopAuto(); timer = setInterval(next, DELAY); }
  function stopAuto()  { clearInterval(timer); }

  function init() {
    const wrap = document.querySelector('.hero-slider');
    if (!wrap) return;

    slides      = [...wrap.querySelectorAll('.slide')];
    dots        = [...wrap.querySelectorAll('.slider-dot')];
    currentEl   = wrap.querySelector('.slider-counter .current');
    progressBar = wrap.querySelector('.slider-progress');
    total       = slides.length;
    if (!total) return;

    dots.forEach((d, i) => d.addEventListener('click', () => { goTo(i); startAuto(); }));
    wrap.querySelector('.slider-arrow--prev')?.addEventListener('click', () => { prev(); startAuto(); });
    wrap.querySelector('.slider-arrow--next')?.addEventListener('click', () => { next(); startAuto(); });

    wrap.addEventListener('mouseenter', stopAuto);
    wrap.addEventListener('mouseleave', startAuto);

    let tx = 0;
    wrap.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
    wrap.addEventListener('touchend',   e => {
      const diff = tx - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) { diff > 0 ? next() : prev(); startAuto(); }
    }, { passive: true });

    document.addEventListener('keydown', e => {
      if (e.key === 'ArrowRight') { next(); startAuto(); }
      if (e.key === 'ArrowLeft')  { prev(); startAuto(); }
    });

    goTo(0);
    startAuto();
  }
  return { init };
})();


/* Scroll Reveal */
const Reveal = (() => {
  function init() {
    const els = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    if (!els.length) return;
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });
    els.forEach(el => io.observe(el));
  }
  return { init };
})();


/* Animated Counters */
const Counters = (() => {
  function animateCount(el, target, duration) {
    const start = performance.now();
    function step(now) {
      const elapsed  = now - start;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out expo
      const eased    = 1 - Math.pow(2, -10 * progress);
      el.textContent = Math.floor(eased * target);
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target;
    }
    requestAnimationFrame(step);
  }

  function init() {
    const nums = document.querySelectorAll('.stat-item__num[data-count]');
    if (!nums.length) return;

    const io = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          const target = parseInt(e.target.dataset.count, 10);
          animateCount(e.target, target, 1800);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.4 });

    nums.forEach(el => io.observe(el));
  }
  return { init };
})();


/* Gold Sparkle on Click */
const Sparkle = (() => {
  function burst(x, y) {
    const count = 6;
    for (let i = 0; i < count; i++) {
      const dot   = document.createElement('div');
      const angle = (360 / count) * i;
      const dist  = 28 + Math.random() * 20;
      const rad   = (angle * Math.PI) / 180;
      dot.className = 'sparkle';
      dot.style.cssText = `
        left: ${x - 3}px;
        top:  ${y - 3}px;
        --tx: ${Math.cos(rad) * dist}px;
        --ty: ${Math.sin(rad) * dist}px;
      `;
      document.body.appendChild(dot);
      dot.addEventListener('animationend', () => dot.remove());
    }
  }

  function init() {
    document.querySelectorAll('.btn--gold, .collection-card, .product-card').forEach(el => {
      el.addEventListener('click', e => burst(e.clientX, e.clientY));
    });
  }
  return { init };
})();


/*  Cart */
const Cart = (() => {
  let count = parseInt(localStorage.getItem('mc_cart') || '0', 10);

  function updateBadges() {
    document.querySelectorAll('.cart-badge').forEach(el => { el.textContent = count; });
  }

  function add() {
    count++;
    localStorage.setItem('mc_cart', count);
    updateBadges();
    Toast.show('Added to cart', 'fas fa-shopping-bag');
  }

  function init() {
    updateBadges();
    document.querySelectorAll('[data-action="add-to-cart"]').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); add(); });
    });
  }
  return { init };
})();


/* Wishlist */
const Wishlist = (() => {
  function init() {
    document.querySelectorAll('.product-card__wishlist').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const active = this.classList.toggle('active');
        const icon   = this.querySelector('i');
        icon.classList.toggle('far', !active);
        icon.classList.toggle('fas',  active);
        Toast.show(active ? 'Added to wishlist' : 'Removed from wishlist',
                   active ? 'fas fa-heart' : 'far fa-heart');
      });
    });
  }
  return { init };
})();


/* Toast */
const Toast = (() => {
  let el, timer;

  function getEl() {
    if (!el) {
      el = document.createElement('div');
      el.className = 'toast';
      document.body.appendChild(el);
      el.addEventListener('click', e => {
        if (e.target.closest('.toast__close')) dismiss();
      });
    }
    return el;
  }

  function dismiss() {
    clearTimeout(timer);
    el.classList.remove('show');
  }

  function show(msg, icon = 'fas fa-check-circle') {
    const t = getEl();

    // Reset progress bar animation by re-cloning it
    t.innerHTML = `
      <i class="toast__icon ${icon}"></i>
      <span class="toast__msg">${msg}</span>
      <button class="toast__close" aria-label="Dismiss"><i class="fas fa-times"></i></button>
      <div class="toast__progress"></div>
    `;

    clearTimeout(timer);
    // Force reflow so animation restarts cleanly
    t.classList.remove('show');
    void t.offsetWidth;
    t.classList.add('show');

    timer = setTimeout(dismiss, 2800);
  }

  return { show };
})();


/* Scroll To Top */
const ScrollTop = (() => {
  function init() {
    const btn = document.getElementById('scrollToTop');
    if (!btn) return;
    window.addEventListener('scroll', () => btn.classList.toggle('visible', window.scrollY > 500), { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }
  return { init };
})();


/* Newsletter */
const Newsletter = (() => {
  function init() {
    const form = document.getElementById('newsletterForm');
    if (!form) return;
    form.addEventListener('submit', e => {
      e.preventDefault();
      Toast.show('Thank you for subscribing!', 'fas fa-envelope');
      form.reset();
    });
  }
  return { init };
})();


/* Active Nav (scroll-spy) */
const ActiveNav = (() => {
  const SECTIONS = ['hero', 'collections', 'about', 'contact'];

  function setActive(id) {
    document.querySelectorAll('.header__nav a[data-section], .mobile-nav a').forEach(a => {
      const href    = a.getAttribute('href') || '';
      const section = a.dataset.section || href.replace('#', '');
      const isMatch = section === id;
      a.classList.toggle('active', isMatch);
    });
  }

  function init() {
    // Close mobile nav on anchor click and scroll to section
    document.querySelectorAll('.mobile-nav a').forEach(a => {
      a.addEventListener('click', () => {
        const nav = document.getElementById('mobileNav');
        const btn = document.getElementById('hamburger');
        if (nav) nav.classList.remove('open');
        if (btn) { btn.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); }
      });
    });

    // Scroll spy using IntersectionObserver
    const observers = [];
    SECTIONS.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) setActive(id); });
      }, { threshold: 0.25, rootMargin: '-80px 0px -40% 0px' });
      io.observe(el);
      observers.push(io);
    });

    // Default active on load
    setActive('hero');
  }
  return { init };
})();


/*  Account Dropdown */
const AccountDropdown = (() => {
  function init() {
    const btn      = document.getElementById('accountBtn');
    const dropdown = document.getElementById('accountDropdown');
    const wrap     = document.getElementById('accountWrap');
    if (!btn || !dropdown) return;

    function open() {
      dropdown.classList.add('open');
      btn.classList.add('active');
      btn.setAttribute('aria-expanded', 'true');
      dropdown.setAttribute('aria-hidden', 'false');
    }
    function close() {
      dropdown.classList.remove('open');
      btn.classList.remove('active');
      btn.setAttribute('aria-expanded', 'false');
      dropdown.setAttribute('aria-hidden', 'true');
    }
    function isOpen() { return dropdown.classList.contains('open'); }

    btn.addEventListener('click', e => {
      e.stopPropagation();
      isOpen() ? close() : open();
    });

    // Close on outside click
    document.addEventListener('click', e => {
      if (!wrap.contains(e.target)) close();
    });

    // Close on Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') close();
    });
  }
  return { init };
})();


/* Search Overlay */
const SearchOverlay = (() => {
  // Product catalogue — add more entries as products grow
  const PRODUCTS = [
    { name: 'Royal Blue Ceylon Sapphire — 4.82ct', cat: 'Blue Sapphires', price: '$12,500', img: 'assets/images/prod-1.jpg', href: 'product-detail.html' },
    { name: 'Royal Blue Ceylon Sapphire — 3.5ct',  cat: 'Blue Sapphires', price: '$1,850',  img: 'assets/images/prod-1.jpg', href: 'product-detail.html' },
    { name: 'Cornflower Blue Sapphire — 3.10ct',   cat: 'Blue Sapphires', price: '$9,800',  img: 'assets/images/prod-2.jpg', href: 'product-detail.html' },
    { name: 'Unheated Ceylon Sapphire — 2.2ct',    cat: 'Blue Sapphires', price: '$2,800',  img: 'assets/images/prod-4.jpg', href: 'product-detail.html' },
    { name: 'Padparadscha Sapphire — 2.75ct',      cat: 'Padparadscha',   price: '$18,500', img: 'assets/images/prod-3.jpg', href: 'product-detail.html' },
    { name: 'Padparadscha Sapphire — 2.8ct',       cat: 'Padparadscha',   price: '$5,600',  img: 'assets/images/prod-4.jpg', href: 'product-detail.html' },
    { name: 'Chrysoberyl Cat\'s Eye — 4.2ct',      cat: 'Cat\'s Eye',     price: '$2,400',  img: 'assets/images/prod-3.jpg', href: 'product-detail.html' },
    { name: 'Alexandrite Cat\'s Eye — 1.5ct',      cat: 'Cat\'s Eye',     price: '$6,500',  img: 'assets/images/prod-5.jpg', href: 'product-detail.html' },
    { name: 'Blue Star Sapphire Cabochon — 6ct',   cat: 'Star Sapphires', price: '$3,900',  img: 'assets/images/prod-5.jpg', href: 'product-detail.html' },
    { name: 'Blue Star Sapphire — 6.50ct',         cat: 'Star Sapphires', price: '$7,200',  img: 'assets/images/prod-4.jpg', href: 'product-detail.html' },
    { name: 'Natural Ceylon Ruby — 2.1ct',         cat: 'Rubies',         price: '$4,100',  img: 'assets/images/prod-6.jpg', href: 'product-detail.html' },
    { name: 'Natural Ruby — 2.20ct Pigeon Blood',  cat: 'Rubies',         price: '$14,000', img: 'assets/images/prod-1.jpg', href: 'product-detail.html' },
    { name: '18K Gold Sapphire Solitaire Ring',    cat: 'Jewellery',      price: '$3,200',  img: 'assets/images/prod-2.jpg', href: 'product-detail.html' },
    { name: '18K White Gold Diamond Necklace',     cat: 'Jewellery',      price: '$4,800',  img: 'assets/images/prod-6.jpg', href: 'product-detail.html' },
  ];

  function highlight(text, query) {
    if (!query) return text;
    const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return text.replace(re, '<mark>$1</mark>');
  }

  function search(query) {
    if (!query) return [];
    const q = query.toLowerCase();
    return PRODUCTS.filter(p =>
      p.name.toLowerCase().includes(q) || p.cat.toLowerCase().includes(q)
    );
  }

  function renderResults(results, query) {
    return results.map(p => `
      <a href="${p.href}" class="search-result-item">
        <div class="search-result-item__img">
          <img src="${p.img}" alt="${p.name}" loading="lazy" />
        </div>
        <div>
          <div class="search-result-item__cat">${p.cat}</div>
          <div class="search-result-item__name">${highlight(p.name, query)}</div>
        </div>
        <div class="search-result-item__price">${p.price}</div>
      </a>
    `).join('');
  }

  function init() {
    const openBtn    = document.getElementById('searchBtn');
    const overlay    = document.getElementById('searchOverlay');
    const closeBtn   = document.getElementById('searchClose');
    const backdrop   = document.getElementById('searchBackdrop');
    const input      = document.getElementById('searchInput');
    const clearBtn   = document.getElementById('searchClear');
    const defaultEl  = document.getElementById('searchDefault');
    const resultsList = document.getElementById('searchResultsList');
    const noResults  = document.getElementById('searchNoResults');
    const noResultsTerm = document.getElementById('searchNoResultsTerm');
    if (!openBtn || !overlay) return;

    function open() {
      overlay.classList.add('open');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(() => input && input.focus(), 200);
    }
    function close() {
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (input) { input.value = ''; onInput(); }
    }

    function onInput() {
      const q = input ? input.value.trim() : '';
      if (clearBtn) clearBtn.style.display = q ? '' : 'none';

      if (!q) {
        defaultEl   && (defaultEl.style.display = '');
        resultsList && (resultsList.style.display = 'none');
        noResults   && (noResults.style.display = 'none');
        return;
      }

      const results = search(q);
      defaultEl && (defaultEl.style.display = 'none');

      if (results.length) {
        resultsList && (resultsList.style.display = '');
        noResults   && (noResults.style.display = 'none');
        if (resultsList) resultsList.innerHTML = renderResults(results, q);
      } else {
        resultsList && (resultsList.style.display = 'none');
        noResults   && (noResults.style.display = '');
        if (noResultsTerm) noResultsTerm.textContent = q;
      }
    }

    openBtn.addEventListener('click', open);
    closeBtn  && closeBtn.addEventListener('click', close);
    backdrop  && backdrop.addEventListener('click', close);
    input     && input.addEventListener('input', onInput);
    clearBtn  && clearBtn.addEventListener('click', () => {
      if (input) { input.value = ''; input.focus(); }
      onInput();
    });

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && overlay.classList.contains('open')) close();
      // Open search with Ctrl+K or /
      if ((e.ctrlKey && e.key === 'k') && !overlay.classList.contains('open')) {
        e.preventDefault();
        open();
      }
    });
  }
  return { init };
})();


/* Product Card Links */
const ProductLinks = (() => {
  const DETAIL_PAGE = 'product-detail.html';

  function init() {
    document.querySelectorAll('.product-card').forEach(card => {
      // Make the image area a link
      const img = card.querySelector('.product-card__img');
      if (img) {
        img.style.cursor = 'pointer';
        img.addEventListener('click', e => {
          // Ignore clicks on wishlist button or overlay button
          if (e.target.closest('.product-card__wishlist')) return;
          window.location.href = DETAIL_PAGE;
        });
      }

      // Make the product name a link
      const name = card.querySelector('.product-card__name');
      if (name) {
        name.style.cursor = 'pointer';
        name.addEventListener('click', () => {
          window.location.href = DETAIL_PAGE;
        });
      }

      // Quick View button → product detail
      const qv = card.querySelector('.btn-quickview');
      if (qv) {
        qv.addEventListener('click', e => {
          e.stopPropagation();
          window.location.href = DETAIL_PAGE;
        });
      }
    });
  }
  return { init };
})();


/* Testimonials Slider */
const TestimonialsSlider = (() => {
  const AUTOPLAY_MS  = 5000;
  const VISIBLE_DESK = 3;   // cards visible on desktop
  const VISIBLE_MOB  = 1;   // cards visible on mobile

  function init() {
    const slider = document.getElementById('testimonialsSlider');
    if (!slider) return;

    const track   = document.getElementById('testimonialsTrack');
    const dotsWrap = document.getElementById('testimonialsDots');
    const prevBtn  = document.getElementById('tPrev');
    const nextBtn  = document.getElementById('tNext');
    if (!track) return;

    const cards = Array.from(track.children);
    const total = cards.length;
    let current  = 0;
    let timer    = null;

    function visibleCount() {
      return window.innerWidth <= 768 ? VISIBLE_MOB : VISIBLE_DESK;
    }

    function maxIndex() {
      return Math.max(0, total - visibleCount());
    }

    /* Build dots */
    function buildDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      const count = maxIndex() + 1;
      for (let i = 0; i < count; i++) {
        const btn = document.createElement('button');
        btn.className = 'testimonials__dot' + (i === current ? ' active' : '');
        btn.setAttribute('aria-label', 'Go to review ' + (i + 1));
        btn.addEventListener('click', () => goTo(i));
        dotsWrap.appendChild(btn);
      }
    }

    function updateDots() {
      if (!dotsWrap) return;
      dotsWrap.querySelectorAll('.testimonials__dot').forEach((d, i) => {
        d.classList.toggle('active', i === current);
      });
    }

    function goTo(index) {
      current = Math.max(0, Math.min(index, maxIndex()));
      const cardWidth = cards[0].getBoundingClientRect().width + 24; // +gap
      track.style.transform = `translateX(-${current * cardWidth}px)`;
      updateDots();
    }

    function next() { goTo(current >= maxIndex() ? 0 : current + 1); }
    function prev() { goTo(current <= 0 ? maxIndex() : current - 1); }

    function startAutoplay() {
      stopAutoplay();
      timer = setInterval(next, AUTOPLAY_MS);
    }
    function stopAutoplay() {
      if (timer) { clearInterval(timer); timer = null; }
    }

    /* Arrow buttons */
    prevBtn && prevBtn.addEventListener('click', () => { prev(); startAutoplay(); });
    nextBtn && nextBtn.addEventListener('click', () => { next(); startAutoplay(); });

    /* Pause on hover */
    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);

    /* Touch swipe */
    let touchStartX = 0;
    track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', e => {
      const dx = e.changedTouches[0].clientX - touchStartX;
      if (Math.abs(dx) > 40) { dx < 0 ? next() : prev(); startAutoplay(); }
    }, { passive: true });

    /* Recalculate on resize */
    window.addEventListener('resize', () => {
      buildDots();
      goTo(Math.min(current, maxIndex()));
    });

    buildDots();
    goTo(0);
    startAutoplay();
  }

  return { init };
})();


/* Boot */

/* Loader runs immediately — the #pageLoader div is hardcoded in
   every HTML page so it is always in the DOM on DOMContentLoaded. */
document.addEventListener('DOMContentLoaded', () => {
  Loader.init();
});

/* Everything else waits for includes.js to inject the shared
   header & footer, then fires the mc:ready custom event.
   If includes.js is not used on a page the event never fires,
   so each module's init() safely guards with an early return. */
document.addEventListener('mc:ready', () => {
  Header.init();
  MobileNav.init();
  HeroSlider.init();
  Reveal.init();
  Counters.init();
  Sparkle.init();
  Cart.init();
  Wishlist.init();
  ScrollTop.init();
  Newsletter.init();
  ActiveNav.init();
  CookieConsent.init();
  ProductLinks.init();
  AccountDropdown.init();
  SearchOverlay.init();
  TestimonialsSlider.init();
});


/* Cookie Consent */
const CookieConsent = (() => {
  const STORAGE_KEY = 'mc_cookie_consent';

  function hasConsent() {
    return localStorage.getItem(STORAGE_KEY) !== null;
  }

  function saveConsent(preferences) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      timestamp:   new Date().toISOString(),
      preferences: preferences,
    }));
  }

  function hideBanner() {
    const banner = document.getElementById('cookieBanner');
    if (!banner) return;
    banner.classList.remove('visible');
    banner.style.transform   = 'translateY(110%)';
    banner.style.opacity     = '0';
    banner.style.pointerEvents = 'none';
    setTimeout(() => { banner.style.display = 'none'; }, 550);
  }

  function openModal() {
    document.getElementById('cookieModal')?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    document.getElementById('cookieModal')?.classList.remove('open');
    document.body.style.overflow = '';
  }

  function getPreferences() {
    return {
      necessary:   true,
      analytics:   document.getElementById('analyticsToggle')?.checked  ?? true,
      marketing:   document.getElementById('marketingToggle')?.checked  ?? false,
      functional:  document.getElementById('functionalToggle')?.checked ?? true,
    };
  }

  function init() {
    const banner = document.getElementById('cookieBanner');
    if (!banner) return;

    // Show banner if no consent stored
    if (!hasConsent()) {
      setTimeout(() => banner.classList.add('visible'), 1200);
    }

    // Accept All
    document.getElementById('cookieAccept')?.addEventListener('click', () => {
      saveConsent({ necessary: true, analytics: true, marketing: true, functional: true });
      hideBanner();
      Toast.show('Preferences saved. Thank you!', 'fas fa-check-circle');
    });

    // Reject All
    document.getElementById('cookieReject')?.addEventListener('click', () => {
      saveConsent({ necessary: true, analytics: false, marketing: false, functional: false });
      hideBanner();
      Toast.show('Non-essential cookies rejected.', 'fas fa-shield-alt');
    });

    // Manage Preferences
    document.getElementById('cookieCustomise')?.addEventListener('click', openModal);

    // Modal close
    document.getElementById('cookieModalClose')?.addEventListener('click', closeModal);
    document.getElementById('cookieModalOverlay')?.addEventListener('click', closeModal);

    // Save preferences
    document.getElementById('cookieSavePrefs')?.addEventListener('click', () => {
      saveConsent(getPreferences());
      closeModal();
      hideBanner();
      Toast.show('Your preferences have been saved.', 'fas fa-check-circle');
    });

    // Accept all inside modal
    document.getElementById('cookieAcceptAll')?.addEventListener('click', () => {
      document.querySelectorAll('.cookie-toggle input[type="checkbox"]')
        .forEach(cb => { cb.checked = true; });
      saveConsent({ necessary: true, analytics: true, marketing: true, functional: true });
      closeModal();
      hideBanner();
      Toast.show('All cookies accepted. Thank you!', 'fas fa-check-circle');
    });

    // Close modal on Escape key
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeModal();
    });
  }

  return { init };
})();
