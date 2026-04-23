<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (customer_logged_in()) {
    header('Location: account.php');
    exit;
}

$error   = '';
$success = false;
$devLink = '';   // shown only when mail() fails in local dev

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = db();
        $st = $db->prepare('SELECT id, first_name FROM customers WHERE email = ? AND is_active = 1 LIMIT 1');
        $st->execute([$email]);
        $customer = $st->fetch();

        // Always show success to avoid email enumeration
        $success = true;

        if ($customer) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $db->prepare('UPDATE customers SET reset_token = ?, reset_expires = ? WHERE id = ?')
               ->execute([$token, $expires, $customer['id']]);

            $proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl   = $proto . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/');
            $resetUrl  = $baseUrl . '/reset-password.php?token=' . $token;
            $firstName = $customer['first_name'];

            $resetBody = '
<p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#444;">
  Hi <strong>' . htmlspecialchars($firstName) . '</strong>, we received a request to reset the password for your Mirabella Ceylon account.
</p>
<p style="margin:0 0 28px;font-size:15px;line-height:1.7;color:#444;">
  Click the button below to set a new password. This link is valid for <strong>1 hour</strong>.
</p>
<a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background:#c8a84b;color:#fff;text-decoration:none;padding:13px 28px;border-radius:5px;font-size:14px;font-weight:700;letter-spacing:.5px;">Reset My Password</a>
<p style="margin:24px 0 0;font-size:13px;color:#aaa;line-height:1.6;">
  If you did not request a password reset, you can safely ignore this email — your password will not be changed.
</p>';

            $sent = send_mail($email, $firstName, 'Reset your Mirabella Ceylon password', mail_wrap('Password Reset', $resetBody));

            if (!$sent) {
                // Local dev fallback — show the link directly (no SMTP configured)
                $devLink = $resetUrl;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Reset your Mirabella Ceylon account password." />
  <title>Forgot Password | Mirabella Ceylon</title>
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
    .auth-dev-link{ display:flex; flex-direction:column; gap:6px; background:rgba(180,150,80,.1); border:1px solid rgba(180,150,80,.4); border-radius:8px; padding:14px 16px; font-size:12px; color:var(--text-soft,#888); margin-top:12px; word-break:break-all; }
    .auth-dev-link strong { color:var(--gold-dark,#a07830); font-size:11px; text-transform:uppercase; letter-spacing:.05em; }
    .auth-dev-link a { color:var(--gold-dark,#a07830); }
    .auth-back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-soft,#888); text-decoration:none; margin-bottom:24px; }
    .auth-back-link:hover { color:var(--gold-dark,#a07830); }
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
      <h1 class="auth-brand__headline">Don't worry,<br />we'll get you<br /><em>back in.</em></h1>
      <p class="auth-brand__sub">Enter your email address and we'll send you a link to reset your password.</p>
    </div>
    <div class="auth-brand__middle">
      <div class="auth-brand__divider"></div>
      <div class="auth-brand__trust">
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-shield-alt"></i></div>
          <div class="auth-brand__trust-text"><strong>Secure Reset</strong><span>Links expire after 1 hour</span></div>
        </div>
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-lock"></i></div>
          <div class="auth-brand__trust-text"><strong>Your Data is Safe</strong><span>256-bit encrypted passwords</span></div>
        </div>
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-headset"></i></div>
          <div class="auth-brand__trust-text"><strong>Need Help?</strong><span>Contact our support team</span></div>
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

      <a href="login.php" class="auth-back-link">
        <i class="fas fa-arrow-left"></i> Back to Sign In
      </a>

      <div class="auth-eyebrow">Account Recovery</div>
      <h2 class="auth-title">Forgot your password?</h2>
      <p class="auth-subtitle">
        Enter the email address linked to your account and we'll send you a reset link.
      </p>

      <?php if ($error): ?>
      <div class="auth-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="auth-success">
        <i class="fas fa-check-circle"></i>
        If that email is registered, you'll receive a reset link shortly. Check your inbox (and spam folder).
      </div>

      <?php if ($devLink): ?>
      <div class="auth-dev-link">
        <strong>&#9888; Dev Only — email not sent (no mail server)</strong>
        <span>Use this link to reset the password:</span>
        <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a>
      </div>
      <?php endif; ?>

      <div style="margin-top:24px;">
        <a href="login.php" class="auth-submit" style="display:flex;justify-content:center;text-decoration:none;">
          <i class="fas fa-sign-in-alt"></i> &nbsp;Return to Sign In
        </a>
      </div>

      <?php else: ?>

      <form class="auth-form" id="forgotForm" method="POST" novalidate>

        <div class="auth-field">
          <label for="forgotEmail">Email Address</label>
          <div class="auth-field__wrap">
            <i class="fas fa-envelope auth-field__icon"></i>
            <input type="email" id="forgotEmail" name="email"
                   placeholder="you@example.com" autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
          </div>
        </div>

        <button type="submit" class="auth-submit" id="forgotSubmit">
          <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>

      </form>

      <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-soft,#888);">
        Remember your password? <a href="login.php">Sign in</a>
      </p>
      <p style="text-align:center;margin-top:8px;font-size:13px;color:var(--text-soft,#888);">
        Don't have an account? <a href="register.php">Create one free</a>
      </p>

      <?php endif; ?>

    </div>
  </div>

</div>

<script>
  document.getElementById('forgotForm')?.addEventListener('submit', function (e) {
    const email = document.getElementById('forgotEmail').value.trim();
    if (!email) {
      e.preventDefault();
      alert('Please enter your email address.');
      return;
    }
    const btn = document.getElementById('forgotSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending\u2026';
  });
</script>
</body>
</html>
