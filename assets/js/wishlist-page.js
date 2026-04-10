'use strict';

const WishlistPage = (() => {

  function updateCount() {
    const cards = document.querySelectorAll('.wishlist-card:not(.removing)');
    const countEl = document.getElementById('wishlistCount');
    const toolbar = document.getElementById('wishlistToolbar');
    const empty   = document.getElementById('wishlistEmpty');
    const grid    = document.getElementById('wishlistGrid');
    const n = cards.length;
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
    // Remove via heart button click
    document.querySelectorAll('.wishlist-card .product-card__wishlist').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        const card = btn.closest('.wishlist-card');
        if (card) removeCard(card);
      });
    });

    // Clear all
    document.getElementById('clearWishlist')?.addEventListener('click', () => {
      document.querySelectorAll('.wishlist-card').forEach(card => removeCard(card));
    });

    // Add all to cart (visual feedback only)
    document.getElementById('addAllToCart')?.addEventListener('click', () => {
      const cards = document.querySelectorAll('.wishlist-card:not(.removing)');
      cards.forEach(card => {
        const btn = card.querySelector('.btn-cart');
        if (btn) {
          btn.innerHTML = '<i class="fas fa-check"></i> Added';
          btn.style.background = '#27ae60';
          setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-shopping-bag"></i> Add';
            btn.style.background = '';
          }, 2000);
        }
      });
      if (typeof Toast !== 'undefined') {
        Toast.show(`${cards.length} item${cards.length > 1 ? 's' : ''} added to cart`, 'fas fa-shopping-bag');
      }
    });

    updateCount();
  }

  return { init };
})();

document.addEventListener('mc:ready', () => {
  WishlistPage.init();
});
