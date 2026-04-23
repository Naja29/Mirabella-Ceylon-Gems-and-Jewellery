<?php

session_start();

require_once __DIR__ . '/includes/db.php';

/* Already logged in → go to dashboard */
if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

/* Handle POST */
$error   = '';
$notice  = '';
$email   = '';
$login   = '';

/* Session / timeout notices */
if (isset($_GET['reason'])) {
    if ($_GET['reason'] === 'timeout')  $notice = 'Your session expired. Please sign in again.';
    if ($_GET['reason'] === 'session')  $notice = 'Please sign in to access the admin panel.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login    = trim($_POST['email']    ?? '');
    $email    = $login;
    $password = trim($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    /* Basic validation */
    if (empty($login) || empty($password)) {
        $error = 'Please enter your username/email and password.';

    } else {
        try {
            $stmt = db()->prepare(
                'SELECT id, name, email, password_hash, role
                 FROM admin_users
                 WHERE (email = :login OR name = :login2) AND is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([':login' => $login, ':login2' => $login]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {

                /* Regenerate session ID to prevent fixation */
                session_regenerate_id(true);

                $_SESSION['admin_logged_in']  = true;
                $_SESSION['admin_id']         = $user['id'];
                $_SESSION['admin_name']        = $user['name'];
                $_SESSION['admin_email']       = $user['email'];
                $_SESSION['admin_role']        = $user['role'];
                $_SESSION['admin_last_active'] = time();

                /* Remember me — 30 day cookie */
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('mc_admin_remember', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    /* TODO: store $token in admin_remember_tokens table linked to $user['id'] */
                }

                /* Log the login */
                /* TODO: INSERT INTO admin_activity_log (admin_id, action, ip, created_at) VALUES (...) */

                header('Location: dashboard.php');
                exit;

            } else {
                /* Generic message — don't reveal whether email exists */
                $error = 'Invalid email or password. Please try again.';
            }

        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'A server error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Admin Login | Mirabella Ceylon</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/login.css" />
</head>
<body>

<div class="admin-login">

  <!-- LEFT — Visual Panel -->
  <div class="login-visual">
    <div class="login-visual__orb login-visual__orb--1"></div>
    <div class="login-visual__orb login-visual__orb--2"></div>
    <div class="login-visual__orb login-visual__orb--3"></div>
    <div class="login-visual__grid"></div>

    <div class="login-visual__brand">
      <img src="../assets/images/logo.png" alt="Mirabella Ceylon"
           class="login-visual__brand-logo" onerror="this.style.display='none'" />
      <div>
        <div class="login-visual__brand-name">Mirabella Ceylon</div>
        <div class="login-visual__brand-tag">Admin Panel</div>
      </div>
    </div>

    <div class="login-visual__centre">
      <div class="login-visual__gem-wrap">
        <div class="login-visual__gem-ring"></div>
        <div class="login-visual__gem-ring"></div>
        <div class="login-visual__gem-ring"></div>
        <div class="login-visual__gem-icon"><i class="fas fa-gem"></i></div>
      </div>
      <h1 class="login-visual__headline">
        Manage Your<br /><em>Gem Empire</em>
      </h1>
      <p class="login-visual__sub">
        Your command centre for orders, inventory, customers, and everything that keeps Mirabella Ceylon running beautifully.
      </p>
    </div>

    <div class="login-visual__stats">
      <div class="login-visual__stat">
        <div class="login-visual__stat-num">2,500+</div>
        <div class="login-visual__stat-lbl">Gems Sold</div>
      </div>
      <div class="login-visual__stat">
        <div class="login-visual__stat-num">60+</div>
        <div class="login-visual__stat-lbl">Countries</div>
      </div>
      <div class="login-visual__stat">
        <div class="login-visual__stat-num">100%</div>
        <div class="login-visual__stat-lbl">Certified</div>
      </div>
    </div>
  </div>


  <!-- RIGHT — Form Panel -->
  <div class="login-form-panel">
    <div class="login-form-wrap">

      <div class="login-form__header">
        <div class="login-form__eyebrow">Secure Access</div>
        <h2 class="login-form__title">Welcome back</h2>
        <p class="login-form__sub">Sign in to your admin account to continue.</p>
      </div>

      <?php if ($notice): ?>
      <div class="lf-alert lf-alert--notice show" role="alert">
        <i class="fas fa-info-circle"></i>
        <span><?= htmlspecialchars($notice) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="lf-alert lf-alert--error show" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form id="adminLoginForm" method="POST" action="login.php" novalidate>

        <!-- CSRF token — TODO: implement proper CSRF validation -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(bin2hex(random_bytes(16))) ?>" />

        <!-- Email -->
        <div class="lf-field" id="fieldEmail">
          <label for="lfEmail">Username or Email</label>
          <div class="lf-field__wrap">
            <i class="fas fa-user lf-field__icon"></i>
            <input
              type="text"
              id="lfEmail"
              name="email"
              value="<?= htmlspecialchars($login) ?>"
              placeholder="admin or admin@example.com"
              autocomplete="username"
              required
            />
          </div>
          <div class="lf-field__error-msg" id="emailErr">Please enter your username or email.</div>
        </div>

        <!-- Password -->
        <div class="lf-field" id="fieldPassword">
          <label for="lfPassword">Password</label>
          <div class="lf-field__wrap">
            <i class="fas fa-lock lf-field__icon"></i>
            <input
              type="password"
              id="lfPassword"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            />
            <button type="button" class="lf-toggle-pass" id="togglePass" aria-label="Toggle password visibility">
              <i class="far fa-eye" id="togglePassIcon"></i>
            </button>
          </div>
          <div class="lf-field__error-msg" id="passErr">Password is required.</div>
        </div>

        <!-- Remember / Forgot -->
        <div class="lf-meta">
          <label class="lf-remember">
            <input type="checkbox" name="remember" id="lfRemember" />
            Remember me for 30 days
          </label>
          <a href="forgot-password.php" class="lf-forgot">Forgot password?</a>
        </div>

        <!-- Submit -->
        <button type="submit" class="lf-submit" id="lfSubmit">
          <span class="lf-submit-text">
            <i class="fas fa-sign-in-alt"></i> &nbsp;Sign In
          </span>
          <span class="lf-spinner"></span>
        </button>

      </form>

      <div class="login-form__footer">
        <p>
          <i class="fas fa-shield-alt" style="color:var(--gold);margin-right:5px;font-size:11px;"></i>
          Restricted to authorised personnel only.<br />
          <a href="../index.php">← Back to Mirabella Ceylon</a>
        </p>
      </div>

    </div>

    <div class="login-version">v1.0.0</div>
  </div>

</div>


<script>
(function () {
  'use strict';

  const form       = document.getElementById('adminLoginForm');
  const emailEl    = document.getElementById('lfEmail');
  const passEl     = document.getElementById('lfPassword');
  const submitBtn  = document.getElementById('lfSubmit');
  const toggleBtn  = document.getElementById('togglePass');
  const toggleIcon = document.getElementById('togglePassIcon');

  /* Password toggle */
  toggleBtn.addEventListener('click', function () {
    const show = passEl.type === 'password';
    passEl.type = show ? 'text' : 'password';
    toggleIcon.className = show ? 'far fa-eye-slash' : 'far fa-eye';
  });

  /* Client-side validation before submit */
  function setError(fieldId, errId, show) {
    document.getElementById(fieldId).classList.toggle('lf-field--error', show);
    document.getElementById(errId).style.display = show ? 'block' : 'none';
  }

  emailEl.addEventListener('input', () => setError('fieldEmail', 'emailErr', false));
  passEl.addEventListener('input',  () => setError('fieldPassword', 'passErr', false));

  form.addEventListener('submit', function () {
    let ok = true;
    if (!emailEl.value.trim()) { setError('fieldEmail', 'emailErr', true); ok = false; }
    if (!passEl.value) { setError('fieldPassword', 'passErr', true); ok = false; }

    if (ok) {
      /* Show loading spinner while PHP processes */
      submitBtn.classList.add('loading');
      submitBtn.disabled = true;
    } else {
      return false;
    }
  });

})();
</script>

</body>
</html>
