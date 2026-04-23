<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/mailer.php';

// Already logged in
if (customer_logged_in()) {
    header('Location: account.php');
    exit;
}

$error  = '';
$fields = ['first_name'=>'','last_name'=>'','email'=>'','phone'=>''];

// Process registration 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName  = trim($_POST['first_name']  ?? '');
    $lastName   = trim($_POST['last_name']   ?? '');
    $email      = trim($_POST['email']       ?? '');
    $phone      = trim($_POST['phone']       ?? '');
    $password   = $_POST['password']         ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $agreedTerms= isset($_POST['agree_terms']);
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    // Keep values for re-fill on error
    $fields = compact('firstName','lastName','email','phone');

    if (!$firstName || !$lastName) {
        $error = 'Please enter your first and last name.';
    } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$agreedTerms) {
        $error = 'You must agree to the Terms & Conditions to create an account.';
    } else {
        $db = db();

        // Check email already exists
        $check = $db->prepare('SELECT id FROM customers WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with that email already exists. <a href="login.php" style="color:inherit;text-decoration:underline;">Sign in instead?</a>';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $st   = $db->prepare("INSERT INTO customers
                (first_name, last_name, email, phone, password_hash, is_active, email_verified, created_at)
                VALUES (?, ?, ?, ?, ?, 1, 0, NOW())");
            $st->execute([$firstName, $lastName, $email, $phone ?: null, $hash]);

            // Newsletter signup
            if ($newsletter) {
                try {
                    $db->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)")->execute([$email]);
                } catch (PDOException $e) { /* ignore */ }
            }

            // Welcome email
            $proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl  = $proto . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/');
            $welcomeBody = '
<p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#444;">
  Hi <strong>' . htmlspecialchars($firstName) . '</strong>, welcome to Mirabella Ceylon!
  We\'re thrilled to have you as part of our community.
</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#444;">
  Your account has been created successfully. You can now browse our collections, save your favourite pieces, and track your orders — all from your personal account.
</p>
<p style="margin:0 0 28px;font-size:14px;color:#888;line-height:1.7;">
  If you have any questions, we\'re always happy to help via WhatsApp or email.
</p>
<a href="' . $baseUrl . '/shop.php" style="display:inline-block;background:#c8a84b;color:#fff;text-decoration:none;padding:13px 28px;border-radius:5px;font-size:14px;font-weight:700;letter-spacing:.5px;">Explore Collections</a>';
            send_mail($email, $firstName . ' ' . $lastName, 'Welcome to Mirabella Ceylon!', mail_wrap('Welcome!', $welcomeBody));

            // Redirect to login with success message
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Create a free Mirabella Ceylon account and get access to exclusive gemstone collections, wishlist, and order tracking." />
  <title>Create Account | Mirabella Ceylon</title>
  <link rel="icon" type="image/png" href="assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Lato:wght@300;400;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
  <style>
    .auth-error{display:flex;align-items:flex-start;gap:10px;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);border-radius:8px;padding:12px 16px;font-size:13px;color:#e74c3c;margin-bottom:20px;line-height:1.5;}
    .auth-error i{flex-shrink:0;margin-top:1px;}
  </style>
</head>
<body>

<div class="auth-page">

  <!-- LEFT: Brand Panel -->
  <div class="auth-brand">
    <div class="auth-brand__slides" aria-hidden="true">
      <div class="auth-brand__slide" style="background-image: url('assets/images/hero-2.jpg');"></div>
      <div class="auth-brand__slide" style="background-image: url('assets/images/hero-3.jpg');"></div>
      <div class="auth-brand__slide" style="background-image: url('assets/images/hero-1.jpg');"></div>
    </div>
    <div class="auth-brand__top">
      <a href="index.php" class="auth-brand__logo">
        <img src="assets/images/logo.png" alt="Mirabella Ceylon" />
        <div class="auth-brand__logo-text">
          <span class="auth-brand__logo-name">Mirabella Ceylon</span>
          <span class="auth-brand__logo-tag">Gems &amp; Jewellery</span>
        </div>
      </a>
      <h1 class="auth-brand__headline">Join a world of<br /><em>extraordinary</em><br />gemstones.</h1>
      <p class="auth-brand__sub">Create your free account and get exclusive early access to new arrivals, wishlist management, and personalised recommendations.</p>
    </div>
    <div class="auth-brand__middle">
      <div class="auth-brand__divider"></div>
      <div class="auth-brand__trust">
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-bell"></i></div>
          <div class="auth-brand__trust-text"><strong>New Arrival Alerts</strong><span>Be first to see rare finds</span></div>
        </div>
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="far fa-heart"></i></div>
          <div class="auth-brand__trust-text"><strong>Personal Wishlist</strong><span>Save your favourite gems</span></div>
        </div>
        <div class="auth-brand__trust-item">
          <div class="auth-brand__trust-icon"><i class="fas fa-box-open"></i></div>
          <div class="auth-brand__trust-text"><strong>Order Tracking</strong><span>Track every shipment live</span></div>
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

      <div class="auth-eyebrow">New Account</div>
      <h2 class="auth-title">Create your account</h2>
      <p class="auth-subtitle">
        Already have an account?
        <a href="login.php">Sign in here</a>
      </p>

      <!-- Error -->
      <?php if ($error): ?>
      <div class="auth-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= $error ?></span>
      </div>
      <?php endif; ?>

      <!-- Social register (UI only) -->
      <div class="auth-social">
        <button class="auth-social-btn" type="button">
          <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/><path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/><path d="M3.964 10.706A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.706V4.962H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.038l3.007-2.332z" fill="#FBBC05"/><path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.962L3.964 7.294C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/></svg>
          Continue with Google
        </button>
        <button class="auth-social-btn" type="button">
          <i class="fab fa-facebook"></i>
          Continue with Facebook
        </button>
      </div>

      <div class="auth-divider">or register with email</div>

      <!-- Register form -->
      <form class="auth-form" id="registerForm" method="POST" novalidate>

        <div class="auth-row">
          <div class="auth-field">
            <label for="firstName">First Name</label>
            <div class="auth-field__wrap">
              <i class="fas fa-user auth-field__icon"></i>
              <input type="text" id="firstName" name="first_name"
                     placeholder="John" autocomplete="given-name"
                     value="<?= htmlspecialchars($fields['firstName'] ?? '') ?>" />
            </div>
          </div>
          <div class="auth-field">
            <label for="lastName">Last Name</label>
            <div class="auth-field__wrap">
              <i class="fas fa-user auth-field__icon"></i>
              <input type="text" id="lastName" name="last_name"
                     placeholder="Smith" autocomplete="family-name"
                     value="<?= htmlspecialchars($fields['lastName'] ?? '') ?>" />
            </div>
          </div>
        </div>

        <div class="auth-field">
          <label for="regEmail">Email Address</label>
          <div class="auth-field__wrap">
            <i class="fas fa-envelope auth-field__icon"></i>
            <input type="email" id="regEmail" name="email"
                   placeholder="you@example.com" autocomplete="email"
                   value="<?= htmlspecialchars($fields['email'] ?? '') ?>" />
          </div>
        </div>

        <div class="auth-field">
          <label for="regPhone">Phone Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
          <div class="auth-field__wrap">
            <i class="fas fa-phone auth-field__icon"></i>
            <input type="tel" id="regPhone" name="phone"
                   placeholder="+1 234 567 8900" autocomplete="tel"
                   value="<?= htmlspecialchars($fields['phone'] ?? '') ?>" />
          </div>
        </div>

        <div class="auth-field">
          <label for="regPassword">Password</label>
          <div class="auth-field__wrap">
            <i class="fas fa-lock auth-field__icon"></i>
            <input type="password" id="regPassword" name="password"
                   placeholder="Create a strong password" autocomplete="new-password" />
            <button type="button" class="auth-field__toggle" id="toggleRegPwd" aria-label="Toggle password">
              <i class="far fa-eye"></i>
            </button>
          </div>
          <div class="auth-strength"><div class="auth-strength__fill" id="strengthFill"></div></div>
          <span class="auth-field__hint" id="strengthHint"></span>
        </div>

        <div class="auth-field">
          <label for="confirmPassword">Confirm Password</label>
          <div class="auth-field__wrap">
            <i class="fas fa-lock auth-field__icon"></i>
            <input type="password" id="confirmPassword" name="confirm_password"
                   placeholder="Repeat your password" autocomplete="new-password" />
            <button type="button" class="auth-field__toggle" id="toggleConfirmPwd" aria-label="Toggle password">
              <i class="far fa-eye"></i>
            </button>
          </div>
          <span class="auth-field__hint" id="matchHint"></span>
        </div>

        <label class="auth-terms">
          <input type="checkbox" name="agree_terms" id="agreeTerms" />
          <span class="auth-terms__box"></span>
          <span class="auth-terms__label">
            I agree to Mirabella Ceylon's
            <a href="terms.php" target="_blank">Terms &amp; Conditions</a> and
            <a href="privacy-policy.php" target="_blank">Privacy Policy</a>
          </span>
        </label>

        <label class="auth-terms">
          <input type="checkbox" name="newsletter" id="agreeNewsletter" checked />
          <span class="auth-terms__box"></span>
          <span class="auth-terms__label">Send me new arrivals, exclusive offers, and gemstone insights</span>
        </label>

        <button type="submit" class="auth-submit">
          <i class="fas fa-gem"></i> Create Account
        </button>

      </form>

    </div>
  </div>

</div>

<script>
  // Password toggles
  function togglePwd(btnId, inputId) {
    document.getElementById(btnId)?.addEventListener('click', function () {
      const input = document.getElementById(inputId);
      const icon  = this.querySelector('i');
      const hide  = input.type === 'password';
      input.type  = hide ? 'text' : 'password';
      icon.classList.toggle('fa-eye',      !hide);
      icon.classList.toggle('fa-eye-slash', hide);
    });
  }
  togglePwd('toggleRegPwd',     'regPassword');
  togglePwd('toggleConfirmPwd', 'confirmPassword');

  // Password strength
  document.getElementById('regPassword')?.addEventListener('input', function () {
    const val  = this.value;
    const fill = document.getElementById('strengthFill');
    const hint = document.getElementById('strengthHint');
    if (!fill) return;
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;
    const levels = [
      {w:'0%',  c:'transparent', label:''},
      {w:'25%', c:'#e74c3c',     label:'Weak'},
      {w:'50%', c:'#e67e22',     label:'Fair'},
      {w:'75%', c:'#f1c40f',     label:'Good'},
      {w:'100%',c:'#27ae60',     label:'Strong'},
    ];
    const l = levels[score] || levels[0];
    fill.style.width      = val ? l.w : '0%';
    fill.style.background = l.c;
    hint.textContent      = val ? l.label : '';
  });

  // Password match
  document.getElementById('confirmPassword')?.addEventListener('input', function () {
    const pwd  = document.getElementById('regPassword')?.value;
    const hint = document.getElementById('matchHint');
    if (!hint) return;
    if (!this.value) { hint.textContent = ''; hint.className = 'auth-field__hint'; return; }
    const match = this.value === pwd;
    hint.textContent = match ? 'Passwords match' : 'Passwords do not match';
    hint.className   = 'auth-field__hint ' + (match ? 'success' : 'error');
  });

  // Client-side validation before submit
  document.getElementById('registerForm')?.addEventListener('submit', function (e) {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName  = document.getElementById('lastName').value.trim();
    const email     = document.getElementById('regEmail').value.trim();
    const pwd       = document.getElementById('regPassword').value;
    const confirm   = document.getElementById('confirmPassword').value;
    const agreed    = document.getElementById('agreeTerms').checked;

    if (!firstName || !lastName) { e.preventDefault(); alert('Please enter your full name.'); return; }
    if (!email)                  { e.preventDefault(); alert('Please enter your email address.'); return; }
    if (pwd.length < 8)          { e.preventDefault(); alert('Password must be at least 8 characters.'); return; }
    if (pwd !== confirm)         { e.preventDefault(); alert('Passwords do not match.'); return; }
    if (!agreed)                 { e.preventDefault(); alert('Please agree to the Terms & Conditions.'); return; }
  });
</script>
</body>
</html>
