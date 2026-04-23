'use strict';

/* Thumbnail Gallery */
const Gallery = (() => {
  function init() {
    const mainImg = document.getElementById('pdMainImg');
    const thumbs  = document.querySelectorAll('.pd-thumb');
    if (!mainImg || !thumbs.length) return;

    mainImg.style.transition = 'opacity 0.2s ease';

    thumbs.forEach(thumb => {
      thumb.addEventListener('click', () => {
        const src = thumb.dataset.src;
        if (!src) return;
        mainImg.style.opacity = '0';
        setTimeout(() => {
          mainImg.src = src;
          mainImg.style.opacity = '1';
        }, 200);
        thumbs.forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
      });
    });
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
        tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
        panels.forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        document.getElementById('tab-' + tab.dataset.tab)?.classList.add('active');
      });
    });

    // Open reviews tab from anchor links or #pd-reviews hash
    const hash = window.location.hash;
    if (hash === '#pd-reviews') {
      document.querySelector('[data-tab="reviews"]')?.click();
    }

    // "Write a Review" link — switch to reviews tab and scroll to form
    document.getElementById('openReviewTab')?.addEventListener('click', e => {
      e.preventDefault();
      document.querySelector('[data-tab="reviews"]')?.click();
      setTimeout(() => {
        const form = document.getElementById('reviewFormWrap');
        if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 80);
    });
  }
  return { init };
})();


/* Quantity */
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


/* Add to Cart */
const PDCart = (() => {
  function updateBadge(count) {
    document.querySelectorAll('.cart-badge').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? '' : 'none';
    });
  }

  function init() {
    const btn = document.getElementById('pdAddCart');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const productId = btn.dataset.id;
      const qty       = parseInt(document.getElementById('pdQtyInput')?.value || '1', 10);
      if (!productId) return;

      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding…';

      const body = new URLSearchParams({ action: 'add', product_id: productId, qty });
      fetch('ajax/cart.php', { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            btn.innerHTML = '<i class="fas fa-check"></i> Added to Cart!';
            if (res.count !== undefined) updateBadge(res.count);
            if (typeof Toast !== 'undefined') Toast.show('Added to cart', 'fas fa-shopping-bag');
            setTimeout(() => { btn.disabled = false; btn.innerHTML = orig; }, 2500);
          } else {
            btn.disabled = false;
            btn.innerHTML = orig;
            alert(res.error || 'Could not add to cart.');
          }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = orig; });
    });
  }
  return { init };
})();


/* WhatsApp Enquiry */
const Enquire = (() => {
  function init() {
    const btn = document.getElementById('pdEnquire');
    if (!btn || !window.PD) return;
    btn.addEventListener('click', () => {
      const msg = `Hi, I'm interested in *${window.PD.productName}*${window.PD.productSku ? ' (SKU: ' + window.PD.productSku + ')' : ''}. Could you please provide more details?\n\n${window.PD.pageUrl}`;
      window.open('https://wa.me/' + window.PD.waNumber + '?text=' + encodeURIComponent(msg), '_blank');
    });
  }
  return { init };
})();


/* Copy Link */
const ShareLink = (() => {
  function init() {
    const btn = document.getElementById('pdCopyLink');
    if (!btn) return;
    btn.addEventListener('click', () => {
      navigator.clipboard?.writeText(window.location.href).then(() => {
        if (typeof Toast !== 'undefined') Toast.show('Link copied!', 'fas fa-link');
      }).catch(() => {
        prompt('Copy this link:', window.location.href);
      });
    });
  }
  return { init };
})();


/* Star Rating Selector */
const StarSelect = (() => {
  function init() {
    const wrap   = document.getElementById('starSelect');
    const hidden = document.getElementById('ratingInput');
    if (!wrap || !hidden) return;

    const btns = wrap.querySelectorAll('.pd-star-btn');
    let selected = 5;

    function highlight(n) {
      btns.forEach((b, i) => {
        const icon = b.querySelector('i');
        icon.className = i < n ? 'fas fa-star' : 'far fa-star';
        icon.style.color = i < n ? 'var(--gold, #c9a84c)' : '';
      });
    }

    highlight(selected);

    btns.forEach((btn, i) => {
      btn.addEventListener('mouseenter', () => highlight(i + 1));
      btn.addEventListener('mouseleave', () => highlight(selected));
      btn.addEventListener('click', () => {
        selected = i + 1;
        hidden.value = selected;
        highlight(selected);
      });
    });
  }
  return { init };
})();


/* Review Submission */
const ReviewForm = (() => {
  function init() {
    const form = document.getElementById('reviewForm');
    if (!form) return;

    form.addEventListener('submit', e => {
      e.preventDefault();
      const body   = document.getElementById('reviewBody').value.trim();
      const rating = document.getElementById('ratingInput').value;
      const msg    = document.getElementById('reviewMsg');

      if (!body) { showMsg('Please write your review.', false); return; }

      const btn = document.getElementById('reviewSubmit');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';

      const data = new URLSearchParams({
        product_id: form.querySelector('[name=product_id]').value,
        rating,
        title: document.getElementById('reviewTitle').value.trim(),
        body,
      });

      fetch('ajax/submit_review.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showMsg('Thank you! Your review has been submitted for approval.', true);
            form.reset();
            btn.style.display = 'none';
          } else {
            showMsg(res.error || 'Could not submit review. Please try again.', false);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Review';
          }
        })
        .catch(() => {
          showMsg('Network error. Please try again.', false);
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Review';
        });
    });

    function showMsg(text, success) {
      const msg = document.getElementById('reviewMsg');
      if (!msg) return;
      msg.textContent = text;
      msg.style.display = '';
      msg.style.color = success ? '#2ecc71' : '#e74c3c';
    }
  }
  return { init };
})();


/* Boot */
function bootPD() {
  Gallery.init();
  Lightbox.init();
  Tabs.init();
  Quantity.init();
  PDCart.init();
  Enquire.init();
  ShareLink.init();
  StarSelect.init();
  ReviewForm.init();
}

document.addEventListener('mc:ready',        bootPD);
document.addEventListener('DOMContentLoaded', bootPD);
