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
</head>
<body class="page-loading">

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
    <div class="top-bar__contact">
      <a href="tel:+94771234567"><i class="fas fa-phone"></i>+94 77 123 4567</a>
      <a href="mailto:info@mirabelaceylon.com"><i class="fas fa-envelope"></i>info@mirabelaceylon.com</a>
    </div>
    <span class="top-bar__notice">
      <i class="fas fa-shipping-fast"></i>Free Worldwide Shipping on Orders Over $500
    </span>
    <div class="top-bar__social">
      <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
      <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
      <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
    </div>
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
        <li><a href="index.php#contact" data-section="contact"<?= $activePage==='contact' ? 'class="active"' : '' ?>>Contact</a></li>
      </ul>
    </nav>

    <div class="header__actions">
      <button class="header__action-btn" aria-label="Search"><i class="fas fa-search"></i></button>
      <button class="header__action-btn" aria-label="Account"><i class="far fa-user"></i></button>
      <a href="cart.php" class="header__action-btn" aria-label="Cart">
        <i class="fas fa-shopping-bag"></i>
        <span class="cart-badge">0</span>
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
      <li><a href="index.php#contact" <?= $activePage==='contact' ? 'class="active"' : '' ?>>Contact</a></li>
    </ul>
  </nav>
</header>
