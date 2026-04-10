'use strict';

const OrdersPage = (() => {

  function filterOrders(status) {
    const cards  = document.querySelectorAll('.order-card');
    let visible  = 0;

    cards.forEach(card => {
      const match = status === 'all' || card.dataset.status === status;
      card.classList.toggle('hidden', !match);
      if (match) visible++;
    });

    const empty = document.getElementById('ordersEmpty');
    const list  = document.getElementById('ordersList');
    if (empty) empty.style.display = visible === 0 ? '' : 'none';
    if (list)  list.style.display  = visible === 0 ? 'none' : '';
  }

  function init() {
    // Tab filtering
    const tabs = document.querySelectorAll('.orders-tab');
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        filterOrders(tab.dataset.filter);
      });
    });

    // Cancel order button — remove card with confirmation
    document.querySelectorAll('.order-btn--danger').forEach(btn => {
      btn.addEventListener('click', () => {
        if (!confirm('Are you sure you want to cancel this order?')) return;
        const card = btn.closest('.order-card');
        if (!card) return;
        // Update status badge
        const badge = card.querySelector('.order-status');
        if (badge) {
          badge.className = 'order-status order-status--cancelled';
          badge.innerHTML = '<i class="fas fa-times-circle"></i> Cancelled';
        }
        card.classList.add('order-card--cancelled');
        card.dataset.status = 'cancelled';
        // Replace footer actions
        const actions = card.querySelector('.order-card__actions');
        if (actions) {
          actions.innerHTML = `
            <button class="order-btn order-btn--outline"><i class="fas fa-redo-alt"></i> Reorder</button>
          `;
        }
        if (typeof Toast !== 'undefined') {
          Toast.show('Order cancelled. Refund will be processed within 5 business days.', 'fas fa-info-circle');
        }
        // If filtered to "processing", hide it now
        const activeTab = document.querySelector('.orders-tab.active');
        if (activeTab && activeTab.dataset.filter === 'processing') {
          card.classList.add('hidden');
        }
      });
    });
  }

  return { init };
})();

document.addEventListener('mc:ready', () => {
  OrdersPage.init();
});
