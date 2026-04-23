'use strict';

const WishlistPage = (() => {

  function updateHeaderBadge(count) {
    document.querySelectorAll('.cart-badge').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? '' : 'none';
    });
  }

  function updateCount() {
    const cards   = document.querySelectorAll('.wishlist-card:not(.removing)');
    const countEl = document.getElementById('wishlistCount');
    const toolbar = document.getElementById('wishlistToolbar');
    const grid    = document.getElementById('wishlistGrid');
    const empty   = document.getElementById('wishlistEmptyDynamic');
    const n       = cards.length;

    if (countEl) countEl.textContent = n;
    const hasItems = n > 0;
    if (toolbar) toolbar.style.display = hasItems ? '' : 'none';
    if (grid)    grid.style.display    = hasItems ? '' : 'none';
    if (empty)   empty.style.display   = hasItems ? 'none' : '';
  }

  function removeCard(card) {
    card.classList.add('removing');
    setTimeout(() => {
      card.remove();
      updateCount();
    }, 320);
  }

  function init() {

    // Remove via heart button — AJAX
    document.addEventListener('click', e => {
      const btn = e.target.closest('.js-wl-remove');
      if (!btn) return;
      e.stopPropagation();
      const productId = btn.dataset.id;
      const card      = btn.closest('.wishlist-card');
      if (!card) return;

      removeCard(card);

      const body = new URLSearchParams({ action: 'remove', product_id: productId });
      fetch('ajax/wishlist.php', { method: 'POST', body }).catch(() => {});
    });

    // Add to cart
    document.addEventListener('click', e => {
      const btn = e.target.closest('.js-wl-add-cart');
      if (!btn) return;
      const productId = btn.dataset.id;
      if (!productId) return;

      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

      const body = new URLSearchParams({ action: 'add', product_id: productId, qty: 1 });
      fetch('ajax/cart.php', { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            btn.innerHTML = '<i class="fas fa-check"></i> Added';
            btn.style.background = '#27ae60';
            btn.style.borderColor = '#27ae60';
            if (res.count !== undefined) updateHeaderBadge(res.count);
            if (typeof Toast !== 'undefined') Toast.show('Added to cart', 'fas fa-shopping-bag');
            setTimeout(() => {
              btn.disabled = false;
              btn.innerHTML = orig;
              btn.style.background = '';
              btn.style.borderColor = '';
            }, 2200);
          } else {
            btn.disabled = false;
            btn.innerHTML = orig;
          }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = orig; });
    });

    // Add all to cart
    document.getElementById('addAllToCart')?.addEventListener('click', () => {
      const cartBtns = document.querySelectorAll('.wishlist-card:not(.removing) .js-wl-add-cart');
      if (!cartBtns.length) return;

      let done = 0;
      cartBtns.forEach(btn => {
        const productId = btn.dataset.id;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const body = new URLSearchParams({ action: 'add', product_id: productId, qty: 1 });
        fetch('ajax/cart.php', { method: 'POST', body })
          .then(r => r.json())
          .then(res => {
            btn.innerHTML = res.ok ? '<i class="fas fa-check"></i> Added' : '<i class="fas fa-shopping-bag"></i> Add';
            if (res.ok && res.count !== undefined) updateHeaderBadge(res.count);
            done++;
            if (done === cartBtns.length && typeof Toast !== 'undefined') {
              Toast.show(`${done} item${done > 1 ? 's' : ''} added to cart`, 'fas fa-shopping-bag');
            }
          })
          .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-shopping-bag"></i> Add';
          });
      });
    });

    // Clear all — AJAX
    document.getElementById('clearWishlist')?.addEventListener('click', () => {
      if (!confirm('Remove all items from your wishlist?')) return;

      fetch('ajax/wishlist.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'clear' }),
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            document.querySelectorAll('.wishlist-card').forEach(card => removeCard(card));
            if (typeof Toast !== 'undefined') Toast.show('Wishlist cleared', 'far fa-heart');
          }
        })
        .catch(() => {});
    });

    updateCount();
  }

  return { init };
})();


function bootWishlist() {
  WishlistPage.init();
}

document.addEventListener('mc:ready',        bootWishlist);
document.addEventListener('DOMContentLoaded', bootWishlist);
