'use strict';

const ProfilePage = (() => {

  /* Tab navigation */
  function initNav() {
    const navItems = document.querySelectorAll('.profile-nav__item[data-panel]');
    navItems.forEach(btn => {
      btn.addEventListener('click', () => {
        // Active nav item
        navItems.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        // Active panel
        const panelId = 'panel' + btn.dataset.panel.charAt(0).toUpperCase() + btn.dataset.panel.slice(1);
        document.querySelectorAll('.profile-panel').forEach(p => p.classList.remove('active'));
        document.getElementById(panelId)?.classList.add('active');
        // Scroll to top of content on mobile
        if (window.innerWidth < 960) {
          document.querySelector('.profile-content')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }


  /* Simulate save → show confirmation */
  function simulateSave(btn, msgId) {
    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> &nbsp;Saving…';
    // TODO: replace with fetch() call to admin API
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = orig;
      const msg = document.getElementById(msgId);
      if (!msg) return;
      msg.classList.add('show');
      setTimeout(() => msg.classList.remove('show'), 3000);
    }, 900);
  }


  /* Personal information form */
  function initPersonalForm() {
    const form = document.getElementById('personalForm');
    if (!form) return;
    form.addEventListener('submit', e => {
      e.preventDefault();
      const firstName = document.getElementById('pf-firstName')?.value.trim();
      const email     = document.getElementById('pf-email')?.value.trim();
      if (!firstName) { alert('Please enter your first name.'); return; }
      if (!email)     { alert('Please enter your email address.'); return; }
      // Update sidebar display name
      const initials = (firstName.charAt(0) + (document.getElementById('pf-lastName')?.value.trim().charAt(0) || '')).toUpperCase();
      document.querySelector('.profile-avatar')?.childNodes[0] && (document.querySelector('.profile-avatar').childNodes[0].textContent = initials);
      document.querySelector('.profile-name') && (document.querySelector('.profile-name').textContent = firstName + ' ' + (document.getElementById('pf-lastName')?.value.trim() || ''));
      simulateSave(form.querySelector('[type="submit"]'), 'personalSavedMsg');
    });
  }


  /* Password form */
  function initPasswordForm() {
    const form = document.getElementById('passwordForm');
    if (!form) return;

    // Password toggles
    form.querySelectorAll('.auth-field__toggle[data-toggle]').forEach(btn => {
      btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.toggle);
        const icon  = this.querySelector('i');
        const hide  = input.type === 'password';
        input.type  = hide ? 'text' : 'password';
        icon.classList.toggle('fa-eye',      !hide);
        icon.classList.toggle('fa-eye-slash', hide);
      });
    });

    // Strength meter
    document.getElementById('pf-newPwd')?.addEventListener('input', function () {
      const val  = this.value;
      const fill = document.getElementById('pwdStrengthFill');
      const hint = document.getElementById('pwdStrengthHint');
      if (!fill) return;
      let score = 0;
      if (val.length >= 8)          score++;
      if (/[A-Z]/.test(val))        score++;
      if (/[0-9]/.test(val))        score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;
      const levels = [
        { w:'0%',   c:'transparent', label:'' },
        { w:'25%',  c:'#e74c3c',     label:'Weak' },
        { w:'50%',  c:'#e67e22',     label:'Fair' },
        { w:'75%',  c:'#f1c40f',     label:'Good' },
        { w:'100%', c:'#27ae60',     label:'Strong' },
      ];
      const l = levels[score] || levels[0];
      fill.style.width      = val ? l.w : '0%';
      fill.style.background = l.c;
      hint.textContent      = val ? l.label : '';
      hint.className = 'auth-field__hint';
    });

    // Match hint
    document.getElementById('pf-confirmPwd')?.addEventListener('input', function () {
      const pwd  = document.getElementById('pf-newPwd')?.value;
      const hint = document.getElementById('pwdMatchHint');
      if (!hint) return;
      if (!this.value) { hint.textContent = ''; hint.className = 'auth-field__hint'; return; }
      const match = this.value === pwd;
      hint.textContent = match ? 'Passwords match' : 'Passwords do not match';
      hint.className   = 'auth-field__hint ' + (match ? 'success' : 'error');
    });

    form.addEventListener('submit', e => {
      e.preventDefault();
      const cur     = document.getElementById('pf-curPwd')?.value;
      const newPwd  = document.getElementById('pf-newPwd')?.value;
      const confirm = document.getElementById('pf-confirmPwd')?.value;
      if (!cur)                   { alert('Please enter your current password.'); return; }
      if (newPwd.length < 8)      { alert('New password must be at least 8 characters.'); return; }
      if (newPwd !== confirm)     { alert('Passwords do not match.'); return; }
      form.reset();
      document.getElementById('pwdStrengthFill') && (document.getElementById('pwdStrengthFill').style.width = '0%');
      document.getElementById('pwdStrengthHint') && (document.getElementById('pwdStrengthHint').textContent = '');
      document.getElementById('pwdMatchHint')    && (document.getElementById('pwdMatchHint').textContent = '');
      simulateSave(form.querySelector('[type="submit"]'), 'passwordSavedMsg');
    });
  }


  /* Sign out */
  function initSignOut() {
    document.getElementById('signOutBtn')?.addEventListener('click', () => {
      if (confirm('Are you sure you want to sign out?')) {
        window.location.href = 'login.php';
      }
    });
  }


  /* Delete account */
  function initDeleteAccount() {
    document.getElementById('deleteAccountBtn')?.addEventListener('click', () => {
      if (confirm('Are you sure you want to permanently delete your account? This cannot be undone.')) {
        alert('Account deletion will be available once the admin panel is set up.');
      }
    });
  }


  /* Init */
  function init() {
    initNav();
    initPersonalForm();
    initPasswordForm();
    initSignOut();
    initDeleteAccount();
  }

  return { init };
})();

document.addEventListener('mc:ready', () => {
  ProfilePage.init();
});
