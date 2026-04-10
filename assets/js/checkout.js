'use strict';

const CheckoutPage = (() => {

  const WA_NUMBER = '94718456999'; // demo — replace via admin
  const LKR_RATE  = 320;          // 1 USD = Rs. 320 indicative

  let activeCurrency = 'USD';


  /* Currency helpers */
  function fmtUSD(v) {
    return '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 0 });
  }
  function fmtLKR(v) {
    return 'Rs.\u00a0' + Math.round(v * LKR_RATE).toLocaleString('en-US');
  }
  function fmt(v) { return activeCurrency === 'LKR' ? fmtLKR(v) : fmtUSD(v); }

  function updateAllPrices() {
    document.querySelectorAll('[data-usd]').forEach(el => {
      const v = parseFloat(el.dataset.usd);
      if (!isNaN(v)) el.textContent = fmt(v);
    });
    document.querySelectorAll('[data-usd-neg]').forEach(el => {
      const v = parseFloat(el.dataset.usdNeg);
      if (!isNaN(v)) el.textContent = '\u2212' + fmt(v);
    });
    // Refresh active paid shipping in sidebar
    const shippingEl = document.getElementById('summaryShipping');
    if (shippingEl && shippingEl.dataset.usd) {
      const v = parseFloat(shippingEl.dataset.usd);
      if (!isNaN(v) && v > 0) shippingEl.textContent = fmt(v);
    }
  }

  function initCurrency() {
    const toggle   = document.getElementById('currencyToggle');
    const rateNote = document.getElementById('lkrRateNote');
    if (!toggle) return;
    toggle.querySelectorAll('.currency-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        toggle.querySelectorAll('.currency-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeCurrency = btn.dataset.currency;
        updateAllPrices();
        if (rateNote) rateNote.style.display = activeCurrency === 'LKR' ? 'flex' : 'none';
      });
    });
  }


  /* Address fields + shipping/payment zone swap */
  function initAddressFields() {
    const countryEl     = document.getElementById('co-country');
    const fieldDistrict = document.getElementById('fieldDistrict');
    const fieldState    = document.getElementById('fieldState');
    const cityInput     = document.getElementById('co-city');
    const phoneInput    = document.getElementById('co-phone');
    const zipOptional   = document.getElementById('zipOptional');
    const zipInput      = document.getElementById('co-zip');
    if (!countryEl) return;

    function applyCountry(val) {
      const isSL = val === 'LK';

      // Address fields
      if (fieldDistrict) fieldDistrict.style.display = isSL ? '' : 'none';
      if (fieldState)    fieldState.style.display    = isSL ? 'none' : '';
      if (cityInput)     cityInput.placeholder = isSL ? 'e.g. Colombo' : 'e.g. New York';
      if (phoneInput)    phoneInput.placeholder = isSL ? '+94 77 123 4567' : '+1 234 567 8900';
      if (zipOptional)   zipOptional.style.display = isSL ? '' : 'none';
      if (zipInput)      zipInput.required = !isSL;

      // Shipping options
      document.querySelectorAll('[data-zone="local"]').forEach(el => {
        el.style.display = isSL ? '' : 'none';
      });
      document.querySelectorAll('[data-zone="intl"]').forEach(el => {
        el.style.display = isSL ? 'none' : '';
      });

      // Select first visible shipping option
      const firstVisible = document.querySelector('.shipping-option:not([style*="display: none"]):not([style*="display:none"])');
      document.querySelectorAll('.shipping-option').forEach(o => o.classList.remove('selected'));
      if (firstVisible) {
        firstVisible.classList.add('selected');
        const radio = firstVisible.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        updateShippingSidebar(firstVisible);
      }

      // Payment method buttons
      document.querySelectorAll('#paymentMethods .payment-method[data-zone="local"]').forEach(b => {
        b.style.display = isSL ? '' : 'none';
      });
      document.querySelectorAll('#paymentMethods .payment-method[data-zone="intl"]').forEach(b => {
        b.style.display = isSL ? 'none' : '';
      });

      // Activate first visible payment method
      const firstPayBtn = document.querySelector('#paymentMethods .payment-method:not([style*="display: none"]):not([style*="display:none"])');
      document.querySelectorAll('#paymentMethods .payment-method').forEach(b => b.classList.remove('active'));
      if (firstPayBtn) {
        firstPayBtn.classList.add('active');
        showPayPanel(firstPayBtn.dataset.method);
      }
    }

    countryEl.addEventListener('change', () => applyCountry(countryEl.value));
    applyCountry(countryEl.value);
  }


  /* Payment method panels */
  const panelMap = {
    bank:    'panelBank',
    friMi:   'panelFriMi',
    ezCash:  'panelEzCash',
    card:    null,        // uses cardPreview + paymentForm
    paypal:  'panelPaypal',
  };

  function showPayPanel(method) {
    // Hide all panels
    ['panelBank','panelFriMi','panelEzCash','panelPaypal'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = 'none';
    });
    const cardPreview = document.getElementById('cardPreview');
    const paymentForm = document.getElementById('paymentForm');
    if (cardPreview) cardPreview.style.display = 'none';
    if (paymentForm) paymentForm.style.display = 'none';

    if (method === 'card') {
      if (cardPreview) cardPreview.style.display = '';
      if (paymentForm) paymentForm.style.display = '';
    } else if (panelMap[method]) {
      const panel = document.getElementById(panelMap[method]);
      if (panel) panel.style.display = '';
    }
  }

  function initPaymentMethods() {
    const btns = document.querySelectorAll('#paymentMethods .payment-method');
    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        showPayPanel(btn.dataset.method);
      });
    });
  }


  /* Shipping option selection */
  function updateShippingSidebar(opt) {
    const summaryShipping = document.getElementById('summaryShipping');
    if (!summaryShipping) return;
    const priceEl = opt.querySelector('.shipping-option__price');
    if (!priceEl) return;

    if (priceEl.classList.contains('free')) {
      summaryShipping.textContent = 'Free';
      summaryShipping.classList.add('co-total-free');
      delete summaryShipping.dataset.usd;
    } else if (priceEl.dataset.usd) {
      const v = parseFloat(priceEl.dataset.usd);
      summaryShipping.textContent = fmt(v);
      summaryShipping.dataset.usd = priceEl.dataset.usd;
      summaryShipping.classList.remove('co-total-free');
    } else if (priceEl.dataset.lkr) {
      // Local LKR shipping — always show in LKR regardless of toggle
      summaryShipping.textContent = 'Rs.\u00a0' + Number(priceEl.dataset.lkr).toLocaleString('en-US');
      delete summaryShipping.dataset.usd;
      summaryShipping.classList.remove('co-total-free');
    }
  }

  function initShipping() {
    const options = document.querySelectorAll('.shipping-option');
    options.forEach(opt => {
      opt.addEventListener('click', () => {
        options.forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        const radio = opt.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        updateShippingSidebar(opt);
      });
    });
  }


  /*  Live card preview */
  function initCardPreview() {
    const numInput    = document.getElementById('co-cardNum');
    const nameInput   = document.getElementById('co-cardName');
    const expiryInput = document.getElementById('co-cardExpiry');
    const previewNum    = document.getElementById('previewNumber');
    const previewName   = document.getElementById('previewName');
    const previewExpiry = document.getElementById('previewExpiry');
    const previewBrand  = document.getElementById('previewBrand');

    if (numInput) {
      numInput.addEventListener('input', function () {
        let val = this.value.replace(/\D/g, '').slice(0, 16);
        this.value = val.replace(/(.{4})/g, '$1 ').trim();
        if (previewNum) {
          const p = val.padEnd(16, '\u2022');
          previewNum.innerHTML = p.slice(0,4)+'\u00a0\u00a0'+p.slice(4,8)+'\u00a0\u00a0'+p.slice(8,12)+'\u00a0\u00a0'+p.slice(12,16);
        }
        if (previewBrand) {
          if (/^4/.test(val))           previewBrand.className = 'fab fa-cc-visa';
          else if (/^5[1-5]/.test(val)) previewBrand.className = 'fab fa-cc-mastercard';
          else if (/^3[47]/.test(val))  previewBrand.className = 'fab fa-cc-amex';
          else                          previewBrand.className = 'fab fa-cc-visa';
        }
      });
    }
    if (nameInput && previewName) {
      nameInput.addEventListener('input', function () {
        previewName.textContent = this.value.toUpperCase() || 'FULL NAME';
      });
    }
    if (expiryInput) {
      expiryInput.addEventListener('input', function () {
        let val = this.value.replace(/\D/g, '').slice(0, 4);
        if (val.length >= 3) val = val.slice(0,2) + ' / ' + val.slice(2);
        this.value = val;
        if (previewExpiry) previewExpiry.textContent = this.value || 'MM / YY';
      });
    }
  }


  /* Build WhatsApp message */
  function buildWhatsAppMessage(f) {
    const totalLKR = 'Rs.\u00a0' + Math.round(f.totalUSD * LKR_RATE).toLocaleString('en-US');
    const lines = [
      '\uD83D\uDED4 *New Order \u2014 Mirabella Ceylon*',
      '\u2501'.repeat(24),
      '',
      '*\uD83D\uDC64 Customer*',
      'Name:    ' + f.firstName + ' ' + f.lastName,
      'Email:   ' + f.email,
      'Phone:   ' + (f.phone || '\u2014'),
      '',
      '*\uD83D\uDCE6 Shipping Address*',
      f.address1 + (f.address2 ? ', ' + f.address2 : ''),
      [f.city, f.district, f.state].filter(Boolean).join(', ') + (f.zip ? ' ' + f.zip : ''),
      f.country,
      '',
      '*\uD83D\uDE9A Shipping Method*',
      f.shipping,
      '',
      '*\uD83D\uDCB3 Payment Method*',
      f.payment + ' _(pending \u2014 awaiting confirmation)_',
      '',
      '*\uD83D\uDED2 Items Ordered*',
      f.items,
      '',
      '\u2501'.repeat(24),
      '*Total: $' + f.totalUSD.toLocaleString('en-US') + ' (approx. ' + totalLKR + ')*',
      '',
      '_Please confirm and arrange payment before dispatch._',
    ];
    return lines.join('\n');
  }

  function collectItems() {
    const rows = document.querySelectorAll('.co-item');
    if (!rows.length) return 'See order details';
    return Array.from(rows).map(row => {
      const name  = row.querySelector('.co-item__name')?.innerText.replace(/\n/g, ' \u2014 ').trim() || '';
      const usd   = row.querySelector('.co-item__price')?.dataset.usd || '';
      const price = usd ? fmt(parseFloat(usd)) : (row.querySelector('.co-item__price')?.textContent.trim() || '');
      const qty   = row.querySelector('.co-item__qty-badge')?.textContent.trim() || '1';
      return '  \u2022 ' + name + '  \u00d7' + qty + '  ' + price;
    }).join('\n');
  }


  /* Place order → WhatsApp */
  function initPlaceOrder() {
    const btn = document.getElementById('placeOrderBtn');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const firstName = document.getElementById('co-firstName')?.value.trim();
      const lastName  = document.getElementById('co-lastName')?.value.trim();
      const email     = document.getElementById('co-email')?.value.trim();
      const phone     = document.getElementById('co-phone')?.value.trim();
      const address1  = document.getElementById('co-address1')?.value.trim();
      const address2  = document.getElementById('co-address2')?.value.trim();
      const city      = document.getElementById('co-city')?.value.trim();
      const zip       = document.getElementById('co-zip')?.value.trim();
      const countryEl = document.getElementById('co-country');
      const country   = countryEl?.options[countryEl.selectedIndex]?.text || '';
      const isSL      = countryEl?.value === 'LK';
      const district  = isSL ? (document.getElementById('co-district')?.value || '') : '';
      const state     = !isSL ? (document.getElementById('co-state')?.value.trim() || '') : '';

      if (!firstName || !lastName)   { alert('Please enter your full name.');            return; }
      if (!email)                    { alert('Please enter your email address.');        return; }
      if (!address1 || !city)        { alert('Please complete your shipping address.'); return; }
      if (!countryEl?.value)         { alert('Please select your country.');             return; }
      if (!zip && !isSL)             { alert('Please enter your postal / ZIP code.');   return; }

      const selectedShipping = document.querySelector('.shipping-option.selected .shipping-option__name')?.textContent.trim() || 'Standard';
      const paymentLabel     = document.querySelector('#paymentMethods .payment-method.active')?.textContent.trim() || 'Not specified';
      const totalUSD = parseFloat(document.querySelector('.checkout-summary__grand [data-usd]')?.dataset.usd || '0');

      const message = buildWhatsAppMessage({
        firstName, lastName, email, phone,
        address1, address2, city, district, state, zip, country,
        shipping: selectedShipping,
        payment: paymentLabel,
        items: collectItems(),
        totalUSD,
      });

      btn.disabled = true;
      btn.innerHTML = '<i class="fab fa-whatsapp" style="font-size:16px;"></i> &nbsp;Opening WhatsApp\u2026';

      window.open('https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(message), '_blank');
      setTimeout(() => { window.location.href = 'order-confirmation.html'; }, 1500);
    });
  }


  /* Init */
  function init() {
    initCurrency();
    initAddressFields(); // also sets initial shipping + payment zone
    initShipping();
    initPaymentMethods();
    initCardPreview();
    initPlaceOrder();
  }

  return { init };
})();

document.addEventListener('mc:ready', () => {
  CheckoutPage.init();
});
