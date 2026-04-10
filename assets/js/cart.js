'use strict';

/* Cart State */
const CartPage = (() => {

  // Build state from DOM on load
  const items = {};

  function buildState() {
    document.querySelectorAll('.cart-item[data-id]').forEach(el => {
      const id    = el.dataset.id;
      const price = parseInt(el.dataset.price, 10) || 0;
      const qtyEl = document.getElementById('qty-' + id);
      items[id]   = { el, price, qty: qtyEl ? parseInt(qtyEl.textContent, 10) : 1 };
    });
  }

  function formatPrice(n) {
    return '$' + n.toLocaleString('en-US');
  }

  function updateItemPrice(id) {
    const item   = items[id];
    if (!item) return;
    const priceEl = document.getElementById('price-' + id);
    if (priceEl) priceEl.textContent = formatPrice(item.price * item.qty);
  }

  function recalcTotals() {
    let subtotal = 0;
    let count    = 0;
    Object.values(items).forEach(item => {
      subtotal += item.price * item.qty;
      count    += item.qty;
    });

    // Apply promo discount if any
    const discount = parseInt(document.getElementById('cartLayout')?.dataset.discount || '0', 10);
    const discounted = Math.round(subtotal * discount / 100);
    const total = subtotal - discounted;

    // Update DOM
    const subtotalEl = document.getElementById('subtotalVal');
    const taxEl      = document.getElementById('taxVal');
    const grandEl    = document.getElementById('grandTotalVal');
    const countEl    = document.getElementById('summaryCount');
    const pageCount  = document.getElementById('cartItemCount');
    const discRow    = document.getElementById('discountRow');
    const discVal    = document.getElementById('discountVal');

    if (subtotalEl) subtotalEl.textContent = formatPrice(subtotal);
    if (taxEl)      taxEl.textContent      = '$0';
    if (grandEl)    grandEl.textContent    = formatPrice(total);
    if (countEl)    countEl.textContent    = count;
    if (pageCount)  pageCount.textContent  = count + (count === 1 ? ' item' : ' items');

    if (discount > 0 && discRow && discVal) {
      discRow.style.display = '';
      discVal.textContent   = '−' + formatPrice(discounted);
    }

    // Show/hide empty state
    const hasItems = Object.keys(items).length > 0;
    const layout   = document.getElementById('cartLayout');
    const empty    = document.getElementById('cartEmpty');
    if (layout) layout.style.display = hasItems ? '' : 'none';
    if (empty)  empty.style.display  = hasItems ? 'none' : '';
  }

  function removeItem(id) {
    const item = items[id];
    if (!item) return;

    item.el.classList.add('removing');
    setTimeout(() => {
      item.el.remove();
      delete items[id];
      recalcTotals();
    }, 380);

    if (typeof Toast !== 'undefined') {
      Toast.show('Item removed from cart', 'fas fa-trash-alt');
    }
  }

  function init() {
    buildState();
    recalcTotals();

    // Quantity buttons
    document.addEventListener('click', e => {
      const btn = e.target.closest('.cart-qty__btn');
      if (!btn) return;
      const id     = btn.dataset.id;
      const action = btn.dataset.action;
      const item   = items[id];
      if (!item) return;

      const qtyEl = document.getElementById('qty-' + id);
      if (action === 'plus') {
        item.qty++;
      } else if (action === 'minus' && item.qty > 1) {
        item.qty--;
      }

      if (qtyEl) qtyEl.textContent = item.qty;
      updateItemPrice(id);
      recalcTotals();
    });

    // Remove buttons
    document.addEventListener('click', e => {
      const btn = e.target.closest('.cart-item__remove');
      if (!btn) return;
      removeItem(btn.dataset.remove);
    });

    // Checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
      checkoutBtn.addEventListener('click', () => {
        window.location.href = 'checkout.html';
      });
    }
  }

  return { init };
})();


/* Promo Code */
const PromoCode = (() => {
  const CODES = {
    'MIRABELLA10': 10,
    'CEYLON15':    15,
    'GEMS20':      20,
  };

  function init() {
    const btn   = document.getElementById('applyPromo');
    const input = document.getElementById('promoCode');
    const msg   = document.getElementById('promoMsg');
    const layout = document.getElementById('cartLayout');
    if (!btn || !input) return;

    btn.addEventListener('click', () => {
      const code    = input.value.trim().toUpperCase();
      const discount = CODES[code];
      msg.className = 'cart-promo__msg';

      if (!code) {
        msg.textContent = 'Please enter a promo code.';
        msg.classList.add('error');
        return;
      }

      if (discount) {
        if (layout) layout.dataset.discount = discount;
        msg.textContent = `Code applied — ${discount}% off!`;
        msg.classList.add('success');
        input.disabled = true;
        btn.disabled   = true;
        btn.textContent = 'Applied';
        CartPage.init && recalcAfterPromo();
        if (typeof Toast !== 'undefined') {
          Toast.show(`Promo code applied — ${discount}% off!`, 'fas fa-tag');
        }
      } else {
        msg.textContent = 'Invalid promo code. Please try again.';
        msg.classList.add('error');
      }
    });

    input.addEventListener('keydown', e => {
      if (e.key === 'Enter') btn.click();
    });
  }

  function recalcAfterPromo() {
    // Trigger a recalc by dispatching a synthetic click on a qty button (no-op amount)
    // Simpler: just call CartPage internals — but since they're scoped, fire a custom event
    document.dispatchEvent(new CustomEvent('cart:recalc'));
  }

  return { init };
})();


/* Boot */
document.addEventListener('mc:ready', () => {
  CartPage.init();
  PromoCode.init();
});

// Recalc totals when promo is applied
document.addEventListener('cart:recalc', () => {
  CartPage.init();
});
