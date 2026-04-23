<?php
/**
 * Mirabella Ceylon — Shared Header
 * includes/header.php
 *
 * Set these variables BEFORE including this file:
 *
 *   $pageTitle   (string) — browser tab title
 *   $pageDesc    (string) — meta description
 *   $activePage  (string) — 'home' | 'shop' | 'about' | 'contact'
 *   $extraCSS    (array)  — extra CSS files, e.g. ['assets/css/shop.css']
 *   $headerClass (string) — initial header state: 'is-transparent' (default) | 'is-solid'
 *
 * Example:
 *   <?php
 *     $pageTitle   = 'Shop — Mirabella Ceylon';
 *     $pageDesc    = 'Browse our full collection...';
 *     $activePage  = 'shop';
 *     $extraCSS    = ['assets/css/shop.css'];
 *     $headerClass = 'is-solid';
 *     include 'includes/header.php';
 *   ?>
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/site_settings.php';

// Maintenance mode gate — bypass for logged-in admins and admin panel
if (get_site_setting('maintenance_mode') === '1' && empty($_SESSION['admin_id'])) {
    $reqFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
    $isAdmin = strpos($reqFile, DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR) !== false;
    $isMaint = basename($reqFile) === 'maintenance.php';
    if (!$isAdmin && !$isMaint) {
        header('HTTP/1.1 503 Service Unavailable');
        header('Retry-After: 3600');
        include __DIR__ . '/../maintenance.php';
        exit;
    }
}

$social = [
    'facebook'  => ['url' => get_site_setting('social_facebook'),  'icon' => 'fab fa-facebook-f',  'label' => 'Facebook'],
    'instagram' => ['url' => get_site_setting('social_instagram'), 'icon' => 'fab fa-instagram',   'label' => 'Instagram'],
    'whatsapp'  => ['url' => get_site_setting('social_whatsapp'),  'icon' => 'fab fa-whatsapp',    'label' => 'WhatsApp'],
    'linkedin'  => ['url' => get_site_setting('social_linkedin'),  'icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn'],
    'youtube'   => ['url' => get_site_setting('social_youtube'),   'icon' => 'fab fa-youtube',     'label' => 'YouTube'],
    'pinterest' => ['url' => get_site_setting('social_pinterest'), 'icon' => 'fab fa-pinterest-p', 'label' => 'Pinterest'],
];
$hasSocial = array_filter($social, fn($s) => !empty($s['url']));

$pageTitle   = $pageTitle   ?? 'Mirabella Ceylon – Gems &amp; Jewellery Worldwide';
$pageDesc    = $pageDesc    ?? 'Discover the world\'s finest certified Ceylon sapphires, rubies and handcrafted jewellery. Delivered worldwide with elegance and trust.';
$activePage  = $activePage  ?? 'home';
$extraCSS    = $extraCSS    ?? [];
$headerClass = $headerClass ?? 'is-transparent';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>" />
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link rel="icon" type="image/png" href="assets/images/favicon.png" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Lato:wght@300;400;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&display=swap" rel="stylesheet" />

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Core stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css" />

  <!-- Page-specific stylesheets -->
  <?php foreach ($extraCSS as $css): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>" />
  <?php endforeach; ?>

  <script>window.MC_LOGGED_IN = <?= !empty($_SESSION['customer_id']) ? 'true' : 'false' ?>;</script>
</head>
<body class="page-loading" data-page="<?= htmlspecialchars($activePage ?? 'home') ?>">

<!-- ── PAGE LOADER ──────────────────────────────────────────── -->
<div class="page-loader" id="pageLoader">
  <div class="loader-logo">
    <img src="assets/images/logo.png" alt="Mirabella Ceylon" />
  </div>
  <div class="loader-bar"><div class="loader-bar__fill"></div></div>
</div>


<!-- ══════════════════════════════════════════════════════
     TOP BAR
═══════════════════════════════════════════════════════════ -->
<div class="top-bar">
  <div class="container top-bar__inner">
    <?php
      $_hPhone = get_site_setting('store_phone', '');
      $_hEmail = get_site_setting('store_email', '');
    ?>
    <div class="top-bar__contact">
      <?php if ($_hPhone): ?>
      <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/','',$_hPhone)) ?>"><i class="fas fa-phone"></i><?= htmlspecialchars($_hPhone) ?></a>
      <?php endif; ?>
      <?php if ($_hEmail): ?>
      <a href="mailto:<?= htmlspecialchars($_hEmail) ?>"><i class="fas fa-envelope"></i><?= htmlspecialchars($_hEmail) ?></a>
      <?php endif; ?>
    </div>
    <span class="top-bar__notice">
      <i class="fas fa-shipping-fast"></i>Free Worldwide Shipping on Orders Over $500
    </span>
    <?php if ($hasSocial): ?>
    <div class="top-bar__social">
      <?php foreach ($hasSocial as $s): ?>
      <a href="<?= htmlspecialchars($s['url']) ?>" aria-label="<?= htmlspecialchars($s['label']) ?>" target="_blank" rel="noopener noreferrer">
        <i class="<?= $s['icon'] ?>"></i>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════
     HEADER
═══════════════════════════════════════════════════════════ -->
<header class="site-header <?= htmlspecialchars($headerClass) ?>" role="banner">
  <div class="header__inner">

    <a href="index.php" class="header__logo" aria-label="Mirabella Ceylon home">
      <img src="assets/images/logo.png" alt="Mirabella Ceylon" onerror="this.style.display='none';" />
      <div class="header__logo-text">
        <span class="brand-name">Mirabella Ceylon</span>
        <span class="brand-tagline">Gems &amp; Jewellery Worldwide</span>
      </div>
    </a>

    <div class="header__divider" aria-hidden="true"></div>

    <nav class="header__nav" aria-label="Main navigation">
      <ul>
        <li><a href="index.php"    data-section="hero"        <?= $activePage==='home'    ? 'class="active"' : '' ?>>Home</a></li>
        <li><a href="shop.php"                                <?= $activePage==='shop'    ? 'class="active"' : '' ?>>Collections</a></li>
        <li><a href="index.php#about"   data-section="about" <?= $activePage==='about'   ? 'class="active"' : '' ?>>About Us</a></li>
        <li><a href="contact.php" <?= $activePage==='contact' ? 'class="active"' : '' ?>>Contact</a></li>
      </ul>
    </nav>

    <div class="header__actions">
      <button class="header__action-btn" id="searchBtn" aria-label="Search"><i class="fas fa-search"></i></button>

      <div class="account-wrap" id="accountWrap">
        <button class="header__action-btn" id="accountBtn" aria-label="Account"
                aria-expanded="false" aria-controls="accountDropdown">
          <i class="far fa-user"></i>
        </button>
        <div class="account-dropdown" id="accountDropdown" aria-hidden="true">
          <div class="account-dropdown__arrow"></div>
          <?php if (!empty($_SESSION['customer_id'])): ?>
          <a href="account.php" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-user-circle"></i></div>
            <div class="account-dropdown__text">
              <span>My Account</span>
              <small><?= htmlspecialchars(($_SESSION['customer_fname'] ?? '') . ' ' . ($_SESSION['customer_lname'] ?? '')) ?></small>
            </div>
          </a>
          <div class="account-dropdown__divider"></div>
          <a href="wishlist.php" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="far fa-heart"></i></div>
            <div class="account-dropdown__text"><span>My Wishlist</span><small>Saved items</small></div>
          </a>
          <a href="account.php?tab=orders" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-box-open"></i></div>
            <div class="account-dropdown__text"><span>My Orders</span><small>Track your purchases</small></div>
          </a>
          <div class="account-dropdown__divider"></div>
          <a href="logout.php" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-sign-out-alt"></i></div>
            <div class="account-dropdown__text"><span>Sign Out</span><small>See you soon</small></div>
          </a>
          <?php else: ?>
          <a href="login.php" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-sign-in-alt"></i></div>
            <div class="account-dropdown__text"><span>Sign In</span><small>Access your account</small></div>
          </a>
          <a href="register.php" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-user-plus"></i></div>
            <div class="account-dropdown__text"><span>Register</span><small>Create a new account</small></div>
          </a>
          <div class="account-dropdown__divider"></div>
          <a href="wishlist.php" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="far fa-heart"></i></div>
            <div class="account-dropdown__text"><span>My Wishlist</span><small>Sign in to view</small></div>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <a href="cart.php" class="header__action-btn" aria-label="Cart">
        <i class="fas fa-shopping-bag"></i>
        <span class="cart-badge" style="display:none;">0</span>
      </a>
      <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false" aria-controls="mobileNav">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>

  <nav class="mobile-nav" id="mobileNav" aria-label="Mobile navigation">
    <ul>
      <li><a href="index.php"         <?= $activePage==='home'    ? 'class="active"' : '' ?>>Home</a></li>
      <li><a href="shop.php"          <?= $activePage==='shop'    ? 'class="active"' : '' ?>>Collections</a></li>
      <li><a href="index.php#about"   <?= $activePage==='about'   ? 'class="active"' : '' ?>>About Us</a></li>
      <li><a href="contact.php" <?= $activePage==='contact' ? 'class="active"' : '' ?>>Contact</a></li>
    </ul>
  </nav>
</header>
