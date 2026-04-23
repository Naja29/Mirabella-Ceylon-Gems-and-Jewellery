'use strict';

const ContactPage = (() => {

  const WA_NUMBER = window.MC_WA_NUMBER || '94771234567';


  /* Collect & validate form values */
  function collectForm() {
    const name    = document.getElementById('cf-name')?.value.trim();
    const email   = document.getElementById('cf-email')?.value.trim();
    const phone   = document.getElementById('cf-phone')?.value.trim();
    const subject = document.getElementById('cf-subject')?.value;
    const message = document.getElementById('cf-message')?.value.trim();
    return { name, email, phone, subject, message };
  }

  function validate({ name, email, subject, message }) {
    if (!name)    { alert('Please enter your name.');    return false; }
    if (!email)   { alert('Please enter your email.');   return false; }
    if (!subject) { alert('Please select a subject.');   return false; }
    if (!message) { alert('Please enter your message.'); return false; }
    return true;
  }


  /* Show success state  */
  function showSuccess() {
    const form    = document.getElementById('contactForm');
    const success = document.getElementById('cfSuccess');
    if (form)    form.style.display    = 'none';
    if (success) success.style.display = 'block';
  }


  /* Submit to backend */
  function simulateSubmit(data, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> &nbsp;Sending…';

    const body = new URLSearchParams(data);
    fetch('ajax/contact.php', { method: 'POST', body })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          showSuccess();
        } else {
          alert(res.error || 'Something went wrong. Please try again.');
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-paper-plane"></i> &nbsp;Send Message';
        }
      })
      .catch(() => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> &nbsp;Send Message';
      });
  }


  /* Build WhatsApp message text */
  function buildWaText({ name, email, phone, subject, message }) {
    return [
      '\uD83D\uDCEC *Contact Form \u2014 Mirabella Ceylon*',
      '\u2501'.repeat(24),
      '',
      '*Name:*    ' + name,
      '*Email:*   ' + email,
      '*Phone:*   ' + (phone || '\u2014'),
      '*Subject:* ' + subject,
      '',
      '*Message:*',
      message,
      '',
      '\u2501'.repeat(24),
      '_Sent from mirabelaceylon.com_',
    ].join('\n');
  }


  /* Form init */
  function initForm() {
    const form    = document.getElementById('contactForm');
    const submitBtn = document.getElementById('cfSubmitBtn');
    const waBtn   = document.getElementById('cfWaBtn');
    if (!form) return;

    // Primary: Send Message (→ admin panel)
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const data = collectForm();
      if (!validate(data)) return;
      simulateSubmit(data, submitBtn);
    });

    // Secondary: Send via WhatsApp
    if (waBtn) {
      waBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const data = collectForm();
        if (!validate(data)) return;
        const url = 'https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(buildWaText(data));
        window.open(url, '_blank');
        setTimeout(showSuccess, 800);
      });
    }
  }


  /* FAQ accordion */
  function initFAQ() {
    document.querySelectorAll('.contact-faq__q').forEach(btn => {
      btn.addEventListener('click', () => {
        const item   = btn.closest('.contact-faq__item');
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.contact-faq__item').forEach(i => i.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
      });
    });
  }


  /*  Highlight today in opening hours */
  function highlightToday() {
    const table = document.getElementById('contactHours');
    if (!table) return;
    const day  = new Date().getDay(); // 0=Sun, 6=Sat
    const rows = table.querySelectorAll('tr');
    if (day >= 1 && day <= 5) rows[0]?.classList.add('today');
    else if (day === 6)        rows[1]?.classList.add('today');
    else if (day === 0)        rows[2]?.classList.add('today');
  }


  /* Init */
  function init() {
    initForm();
    initFAQ();
    highlightToday();
  }

  return { init };
})();

document.addEventListener('mc:ready',       () => ContactPage.init());
document.addEventListener('DOMContentLoaded', () => ContactPage.init());
