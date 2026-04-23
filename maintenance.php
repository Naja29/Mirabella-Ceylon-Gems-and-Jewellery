<?php

$_maintDirect = (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'maintenance.php');

if ($_maintDirect) {
    require_once __DIR__ . '/admin/includes/db.php';
    require_once __DIR__ . '/includes/site_settings.php';
    if (session_status() === PHP_SESSION_NONE) session_start();
    // If maintenance is off or admin is logged in, go home
    if (get_site_setting('maintenance_mode') !== '1' || !empty($_SESSION['admin_id'])) {
        header('Location: index.php'); exit;
    }
    header('HTTP/1.1 503 Service Unavailable');
    header('Retry-After: 3600');
}

header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 3600');

$storeName = get_site_setting('store_name', 'Mirabella Ceylon');
$msg       = get_site_setting('maintenance_msg', 'We are currently performing scheduled maintenance. We\'ll be back shortly.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Under Maintenance | <?= htmlspecialchars($storeName) ?></title>
  <link rel="icon" type="image/png" href="assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:      #c8a84b;
      --gold-pale: rgba(200,168,75,.10);
      --dark:      #0d0d0d;
      --text:      #1a1a1a;
      --text-soft: #6b6b6b;
    }

    body {
      font-family: 'Lato', sans-serif;
      background: #faf8f4;
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 40px 24px;
      position: relative;
      overflow: hidden;
    }

    /* Background gem watermark */
    body::before {
      content: '\f3a5';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      font-size: clamp(300px, 50vw, 600px);
      color: rgba(200,168,75,.05);
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
      user-select: none;
    }

    .maint-wrap {
      position: relative;
      max-width: 560px;
      width: 100%;
    }

    .maint-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 14px;
      margin-bottom: 48px;
    }
    .maint-logo img {
      height: 56px;
      width: auto;
    }
    .maint-logo__text { text-align: left; }
    .maint-logo__name {
      font-family: 'Playfair Display', serif;
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
      line-height: 1.1;
    }
    .maint-logo__tag {
      font-size: 10px;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--gold);
      margin-top: 3px;
    }

    .maint-icon {
      width: 80px; height: 80px;
      background: var(--gold-pale);
      border: 1px solid rgba(200,168,75,.25);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 28px;
      font-size: 30px;
      color: var(--gold);
    }

    .maint-divider {
      width: 60px; height: 1.5px;
      background: var(--gold);
      margin: 0 auto 24px;
    }

    .maint-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(28px, 5vw, 40px);
      font-weight: 700;
      color: var(--text);
      line-height: 1.2;
      margin-bottom: 18px;
    }

    .maint-msg {
      font-size: 15px;
      color: var(--text-soft);
      line-height: 1.75;
      margin-bottom: 40px;
      max-width: 440px;
      margin-left: auto;
      margin-right: auto;
    }

    .maint-features {
      display: flex;
      justify-content: center;
      gap: 32px;
      flex-wrap: wrap;
      margin-bottom: 48px;
    }
    .maint-feature {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--text-soft);
    }
    .maint-feature i {
      font-size: 18px;
      color: var(--gold);
    }

    .maint-footer {
      font-size: 12px;
      color: var(--text-soft);
      letter-spacing: .5px;
    }
    .maint-footer a {
      color: var(--gold);
      text-decoration: none;
    }
    .maint-footer a:hover { text-decoration: underline; }

    @media (max-width: 480px) {
      .maint-features { gap: 20px; }
      .maint-logo img { height: 44px; }
    }
  </style>
</head>
<body>
  <div class="maint-wrap">

    <div class="maint-logo">
      <img src="assets/images/logo.png" alt="<?= htmlspecialchars($storeName) ?>"
           onerror="this.style.display='none'">
      <div class="maint-logo__text">
        <div class="maint-logo__name"><?= htmlspecialchars($storeName) ?></div>
        <div class="maint-logo__tag">Gems &amp; Jewellery Worldwide</div>
      </div>
    </div>

    <div class="maint-icon">
      <i class="fas fa-tools"></i>
    </div>

    <div class="maint-divider"></div>

    <h1 class="maint-title">We'll be back soon</h1>

    <p class="maint-msg"><?= nl2br(htmlspecialchars($msg)) ?></p>

    <div class="maint-features">
      <div class="maint-feature">
        <i class="fas fa-gem"></i>
        <span>Certified Gems</span>
      </div>
      <div class="maint-feature">
        <i class="fas fa-shipping-fast"></i>
        <span>Worldwide Shipping</span>
      </div>
      <div class="maint-feature">
        <i class="fas fa-shield-alt"></i>
        <span>Secure & Trusted</span>
      </div>
    </div>

    <div class="maint-footer">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?> &nbsp;&middot;&nbsp;
      <?php $waNum = preg_replace('/[^0-9]/', '', get_site_setting('whatsapp_number', '')); ?>
      <?php if ($waNum): ?>
        Need help? <a href="https://wa.me/<?= $waNum ?>"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
      <?php else: ?>
        Thank you for your patience.
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
