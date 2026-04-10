'use strict';

/* Thumbnail Gallery */
const Gallery = (() => {
  function init() {
    const mainImg = document.getElementById('pdMainImg');
    const thumbs  = document.querySelectorAll('.pd-thumb');
    if (!mainImg || !thumbs.length) return;

    thumbs.forEach(thumb => {
      thumb.addEventListener('click', () => {
        const src = thumb.dataset.src;
        if (!src) return;

        // Fade swap
        mainImg.style.opacity = '0';
        setTimeout(() => {
          mainImg.src = src;
          mainImg.style.opacity = '1';
        }, 200);

        thumbs.forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
      });
    });

    mainImg.style.transition = 'opacity 0.2s ease';
  }
  return { init };
})();


/* Lightbox */
const Lightbox = (() => {
  let lightbox, lightboxImg;

  function createLightbox() {
    lightbox = document.createElement('div');
    lightbox.className = 'pd-lightbox';
    lightbox.innerHTML = `
      <button class="pd-lightbox__close" aria-label="Close"><i class="fas fa-times"></i></button>
      <img class="pd-lightbox__img" src="" alt="Enlarged view" />
    `;
    document.body.appendChild(lightbox);
    lightboxImg = lightbox.querySelector('.pd-lightbox__img');

    lightbox.querySelector('.pd-lightbox__close').addEventListener('click', close);
    lightbox.addEventListener('click', e => { if (e.target === lightbox) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
  }

  function open(src) {
    if (!lightbox) createLightbox();
    lightboxImg.src = src;
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  function init() {
    const zoomBtn = document.getElementById('pdZoomBtn');
    const mainImg = document.getElementById('pdMainImg');
    if (!zoomBtn || !mainImg) return;

    zoomBtn.addEventListener('click', () => open(mainImg.src));
    mainImg.addEventListener('dblclick', () => open(mainImg.src));
  }
  return { init };
})();


/* Tabs */
const Tabs = (() => {
  function init() {
    const tabs   = document.querySelectorAll('.pd-tab');
    const panels = document.querySelectorAll('.pd-tab-panel');
    if (!tabs.length) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;

        tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
        panels.forEach(p => p.classList.remove('active'));

        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        document.getElementById('tab-' + target)?.classList.add('active');
      });
    });
  }
  return { init };
})();


/* Wishlist */
const PDWishlist = (() => {
  function init() {
    const btn = document.getElementById('pdWishlist');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const active = btn.classList.toggle('active');
      const icon   = btn.querySelector('i');
      icon.classList.toggle('far', !active);
      icon.classList.toggle('fas',  active);

      if (typeof Toast !== 'undefined') {
        Toast.show(
          active ? 'Added to wishlist' : 'Removed from wishlist',
          active ? 'fas fa-heart'      : 'far fa-heart'
        );
      }
    });
  }
  return { init };
})();


/*  Quantity */
const Quantity = (() => {
  function init() {
    const input = document.getElementById('pdQtyInput');
    const minus = document.getElementById('pdQtyMinus');
    const plus  = document.getElementById('pdQtyPlus');
    if (!input) return;

    minus?.addEventListener('click', () => {
      const val = parseInt(input.value, 10);
      if (val > 1) input.value = val - 1;
    });
    plus?.addEventListener('click', () => {
      const val = parseInt(input.value, 10);
      const max = parseInt(input.max, 10) || 99;
      if (val < max) input.value = val + 1;
    });
  }
  return { init };
})();


/* Boot */
document.addEventListener('mc:ready', () => {
  Gallery.init();
  Lightbox.init();
  Tabs.init();
  PDWishlist.init();
  Quantity.init();
});
