<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';

if (customer_logged_in()) {
    header('Location: account.php');
    exit;
}

$token    = trim($_GET['token'] ?? '');
$error    = '';
$success  = false;
$customer = null;

if (!$token) {
    header('Location: forgot-password.php');
    exit;
}

$db = db();
$st = $db->prepare('SELECT id, first_name, email FROM customers WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1 LIMIT 1');
$st->execute([$token]);
$customer = $st->fetch();

if (!$customer) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $customer) {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('UPDATE customers SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?')
           ->execute([$hash, $customer['id']]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Set a new password for your Mirabella Ceylon account." />
  <title>Reset Password | Mirabella Ceylon</title>
  <link rel="icon" type="image/png" href="assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Lato:wght@300;400;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
  <style>
    .auth-error   { display:flex; align-items:center; gap:10px; background:rgba(231,76,60,.1); border:1px solid rgba(231,76,60,.3); border-radius:8px; padding:12px 16px; font-size:13px; color:#e74c3c; margin-bottom:20px; }
    .auth-success { display:flex; align-items:center; gap:10px; background:rgba(46,204,113,.1); border:1px solid rgba(46,204,113,.3); border-radius:8px; padding:12px 16px; font-size:13px; color:#2ecc71; margin-bottom:20px; }
    .pwd-strength { margin-top:6px; height:4px; border-radius:2px; background:#e8e0d4; overflow:hidden; }
    .pwd-strength__bar { height:100%; width:0; border-radius:2px; transition:width .3s, background .3s; }
    .pwd-hint { font-size:11px; margin-top:4px; }
  </style>
</head>
<body>

<div class="auth-page">

  <!-- LEFT: Brand Panel -->
  <div class="auth-brand">
    <div class="auth-brand__slides" aria-hidden="true">
      <div class="auth-brand__slide" style="background-image: url('assets/images/hero-1.jpg');"></div>
      <div class="auth-brand__slide" style="background-image: url('assets/images/hero-2.jpg');"></div>
      <div class="auth-brand__slide" style="background-image: url('assets/images/hero-3.jpg');"></div>
    </div>
    <div class="auth-brand__top">
      <a href="index.php" class="auth-brand__logo">
        <img src="assets/images/logo.png" alt="Mirabella Ceylon" />
        <div class="auth-brand__logo-text">
          <span class="auth-brand__logo-name">Mirabella Ceylon</span>
          <span class="auth-brand__logo-tag">Gems &amp; Jewellery</span>
        </div>
      </a>
      <h1 class="auth-brand__headline">Create a new<br />password and<br /><em>get back in.</em></h1>
      <p class="auth-brand__sub">Choose a strong password to keep your account secure.</p>
    </div>
    <div class="auth-brand__middle">
      <div class="auth-brand__divider"></div>
      <div class="auth-brand__trust">
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-lock"></i></div>
          <div class="auth-brand__trust-text"><strong>Strong Passwords</strong><span>Use 8+ characters with a mix of types</span></div>
        </div>
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-clock"></i></div>
          <div class="auth-brand__trust-text"><strong>Link Expires</strong><span>Reset links are valid for 1 hour only</span></div>
        </div>
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-shield-alt"></i></div>
          <div class="auth-brand__trust-text"><strong>Encrypted</strong><span>Passwords are never stored in plain text</span></div>
        </div>
      </div>
    </div>
    <div class="auth-brand__bottom">
      &copy; <?= date('Y') ?> Mirabella Ceylon &nbsp;·&nbsp;
      <a href="privacy-policy.php">Privacy</a> &nbsp;·&nbsp;
      <a href="terms.php">Terms</a>
    </div>
  </div>

  <!-- RIGHT: Form Panel -->
  <div class="auth-form-panel">
    <div class="auth-form-panel__inner">

      <a href="index.php" class="auth-mobile-logo">
        <img src="assets/images/logo.png" alt="Mirabella Ceylon" />
        <span>Mirabella Ceylon</span>
      </a>

      <div class="auth-eyebrow">Account Recovery</div>
      <h2 class="auth-title">Set a new password</h2>
      <?php if ($customer && !$success): ?>
      <p class="auth-subtitle">
        Setting new password for <strong><?= htmlspecialchars($customer['email']) ?></strong>
      </p>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="auth-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
        <?php if (!$customer): ?>
        <a href="forgot-password.php" style="margin-left:8px;color:inherit;text-decoration:underline;">Request a new link</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="auth-success">
        <i class="fas fa-check-circle"></i>
        Your password has been updated successfully!
      </div>
      <div style="margin-top:24px;">
        <a href="login.php" class="auth-submit" style="display:flex;justify-content:center;text-decoration:none;">
          <i class="fas fa-sign-in-alt"></i> &nbsp;Sign In Now
        </a>
      </div>

      <?php elseif ($customer): ?>

      <form class="auth-form" id="resetForm" method="POST" novalidate>

        <div class="auth-field">
          <label for="newPassword">New Password</label>
          <div class="auth-field__wrap">
            <i class="fas fa-lock auth-field__icon"></i>
            <input type="password" id="newPassword" name="password"
                   placeholder="At least 8 characters" autocomplete="new-password" />
            <button type="button" class="auth-field__toggle" id="togglePwd1" aria-label="Toggle password">
              <i class="far fa-eye"></i>
            </button>
          </div>
          <div class="pwd-strength"><div class="pwd-strength__bar" id="pwdBar"></div></div>
          <div class="pwd-hint" id="pwdHint" style="color:#aaa;"></div>
        </div>

        <div class="auth-field">
          <label for="confirmPassword">Confirm New Password</label>
          <div class="auth-field__wrap">
            <i class="fas fa-lock auth-field__icon"></i>
            <input type="password" id="confirmPassword" name="password2"
                   placeholder="Repeat your new password" autocomplete="new-password" />
            <button type="button" class="auth-field__toggle" id="togglePwd2" aria-label="Toggle password">
              <i class="far fa-eye"></i>
            </button>
          </div>
          <div class="pwd-hint" id="matchHint" style="color:#aaa;"></div>
        </div>

        <button type="submit" class="auth-submit" id="resetSubmit">
          <i class="fas fa-key"></i> Update Password
        </button>

      </form>

      <?php else: ?>
      <div style="margin-top:24px;">
        <a href="forgot-password.php" class="auth-submit" style="display:flex;justify-content:center;text-decoration:none;">
          <i class="fas fa-paper-plane"></i> &nbsp;Request a New Reset Link
        </a>
      </div>
      <?php endif; ?>

      <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-soft,#888);">
        <a href="login.php">Back to Sign In</a>
      </p>

    </div>
  </div>

</div>

<script>
  // Password visibility toggles
  function bindToggle(btnId, inputId) {
    document.getElementById(btnId)?.addEventListener('click', function () {
      const input = document.getElementById(inputId);
      const icon  = this.querySelector('i');
      const hide  = input.type === 'password';
      input.type  = hide ? 'text' : 'password';
      icon.classList.toggle('fa-eye',       !hide);
      icon.classList.toggle('fa-eye-slash',  hide);
    });
  }
  bindToggle('togglePwd1', 'newPassword');
  bindToggle('togglePwd2', 'confirmPassword');

  // Password strength meter
  const pwdInput  = document.getElementById('newPassword');
  const pwdBar    = document.getElementById('pwdBar');
  const pwdHint   = document.getElementById('pwdHint');
  const matchHint = document.getElementById('matchHint');
  const confInput = document.getElementById('confirmPassword');

  function pwdStrength(v) {
    let score = 0;
    if (v.length >= 8)                           score++;
    if (v.length >= 12)                          score++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v))     score++;
    if (/\d/.test(v))                            score++;
    if (/[^A-Za-z0-9]/.test(v))                 score++;
    return score;
  }

  const levels = [
    { pct: 0,   color: '#e8e0d4', label: '' },
    { pct: 20,  color: '#e74c3c', label: 'Very weak' },
    { pct: 40,  color: '#e67e22', label: 'Weak' },
    { pct: 60,  color: '#f1c40f', label: 'Fair' },
    { pct: 80,  color: '#2ecc71', label: 'Strong' },
    { pct: 100, color: '#27ae60', label: 'Very strong' },
  ];

  pwdInput?.addEventListener('input', function () {
    const score = pwdStrength(this.value);
    const lv    = levels[score] || levels[0];
    if (pwdBar)  { pwdBar.style.width = lv.pct + '%'; pwdBar.style.background = lv.color; }
    if (pwdHint) { pwdHint.textContent = lv.label; pwdHint.style.color = lv.color; }
    checkMatch();
  });

  confInput?.addEventListener('input', checkMatch);

  function checkMatch() {
    if (!confInput || !matchHint) return;
    if (!confInput.value) { matchHint.textContent = ''; return; }
    const match = pwdInput.value === confInput.value;
    matchHint.textContent = match ? 'Passwords match' : 'Passwords do not match';
    matchHint.style.color = match ? '#2ecc71' : '#e74c3c';
  }

  // Form validation before submit
  document.getElementById('resetForm')?.addEventListener('submit', function (e) {
    const pwd  = document.getElementById('newPassword').value;
    const pwd2 = document.getElementById('confirmPassword').value;
    if (pwd.length < 8) {
      e.preventDefault();
      alert('Password must be at least 8 characters.');
      return;
    }
    if (pwd !== pwd2) {
      e.preventDefault();
      alert('Passwords do not match.');
      return;
    }
    const btn = document.getElementById('resetSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating\u2026';
  });
</script>
</body>
</html>
