<?php
/**
 * Mirabella Ceylon — Shared Footer
 * includes/footer.php
 *
 * Set these variables BEFORE including this file (optional):
 *   $extraJS (array) — extra JS files, e.g. ['assets/js/shop.js']
 *
 * Example:
 *   <?php
 *     $extraJS = ['assets/js/shop.js'];
 *     include 'includes/footer.php';
 *   ?>
 */

$extraJS = $extraJS ?? [];
require_once __DIR__ . '/site_settings.php';

$social = [
    'facebook'  => ['url' => get_site_setting('social_facebook'),  'icon' => 'fab fa-facebook-f',  'label' => 'Facebook'],
    'instagram' => ['url' => get_site_setting('social_instagram'), 'icon' => 'fab fa-instagram',   'label' => 'Instagram'],
    'whatsapp'  => ['url' => get_site_setting('social_whatsapp'),  'icon' => 'fab fa-whatsapp',    'label' => 'WhatsApp'],
    'linkedin'  => ['url' => get_site_setting('social_linkedin'),  'icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn'],
    'youtube'   => ['url' => get_site_setting('social_youtube'),   'icon' => 'fab fa-youtube',     'label' => 'YouTube'],
    'pinterest' => ['url' => get_site_setting('social_pinterest'), 'icon' => 'fab fa-pinterest-p', 'label' => 'Pinterest'],
];
$hasSocial = array_filter($social, fn($s) => !empty($s['url']));
?>

<!-- ══════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════ -->
<footer class="site-footer" role="contentinfo">
  <div class="container">
    <div class="footer__grid">

      <div>
        <div class="footer__logo">
          <img src="assets/images/logo.png" alt="Mirabella Ceylon" onerror="this.style.display='none'" />
          <div>
            <div class="footer__brand-name">Mirabella Ceylon</div>
            <div class="footer__brand-tagline">Gems &amp; Jewellery Worldwide</div>
          </div>
        </div>
        <p class="footer__brand-desc">Bringing the world's finest certified Ceylon gemstones and handcrafted jewellery to collectors and connoisseurs everywhere.</p>
        <?php if ($hasSocial): ?>
        <div class="footer__social">
          <?php foreach ($hasSocial as $s): ?>
          <a href="<?= htmlspecialchars($s['url']) ?>" aria-label="<?= htmlspecialchars($s['label']) ?>" target="_blank" rel="noopener noreferrer">
            <i class="<?= $s['icon'] ?>"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div>
        <h4 class="footer__col-title">Quick Links</h4>
        <ul class="footer__links">
          <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
          <li><a href="shop.php"><i class="fas fa-chevron-right"></i> Collections</a></li>
          <li><a href="index.php#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
          <li><a href="index.php#contact"><i class="fas fa-chevron-right"></i> Contact</a></li>
          <li><a href="cart.php"><i class="fas fa-chevron-right"></i> Shopping Cart</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer__col-title">Gemstones</h4>
        <ul class="footer__links">
          <li><a href="shop.php?cat=blue-sapphire"><i class="fas fa-chevron-right"></i> Blue Sapphires</a></li>
          <li><a href="shop.php?cat=padparadscha"><i class="fas fa-chevron-right"></i> Padparadscha</a></li>
          <li><a href="shop.php?cat=cats-eye"><i class="fas fa-chevron-right"></i> Cat's Eye</a></li>
          <li><a href="shop.php?cat=ruby"><i class="fas fa-chevron-right"></i> Natural Rubies</a></li>
          <li><a href="shop.php?cat=jewellery"><i class="fas fa-chevron-right"></i> Jewellery</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer__col-title">Get in Touch</h4>
        <div class="footer__contact-item">
          <i class="fas fa-map-marker-alt"></i>
          <span>Ratnapura, Sri Lanka<br />(Gem Capital of the World)</span>
        </div>
        <div class="footer__contact-item">
          <i class="fas fa-phone"></i>
          <span><a href="tel:+94771234567">+94 77 123 4567</a></span>
        </div>
        <div class="footer__contact-item">
          <i class="fas fa-envelope"></i>
          <span><a href="mailto:info@mirabelaceylon.com">info@mirabelaceylon.com</a></span>
        </div>
        <div class="footer__contact-item">
          <i class="fab fa-whatsapp"></i>
          <span><a href="#">Chat on WhatsApp</a></span>
        </div>
      </div>

    </div>

    <div class="footer__bottom">
      <p class="footer__copy">
        &copy; <?= date('Y') ?> Mirabella Ceylon. All rights reserved.
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Designed by <a href="https://www.asseminate.com/" target="_blank" rel="noopener noreferrer">Asseminate</a>
      </p>
      <div class="footer__legal">
        <a href="privacy-policy.php">Privacy Policy</a>
        <span class="footer__legal-sep">|</span>
        <a href="terms.php">Terms &amp; Conditions</a>
        <span class="footer__legal-sep">|</span>
        <a href="refund-policy.php">Refund &amp; Returns</a>
      </div>
    </div>
  </div>
</footer>


<!-- ══════════════════════════════════════════════════════
     COOKIE CONSENT BANNER
═══════════════════════════════════════════════════════════ -->
<div class="cookie-banner" id="cookieBanner" role="dialog" aria-label="Cookie consent" aria-live="polite">
  <div class="cookie-banner__inner">
    <div class="cookie-banner__icon"><i class="fas fa-cookie-bite"></i></div>
    <div class="cookie-banner__content">
      <p class="cookie-banner__title">We value your privacy</p>
      <p class="cookie-banner__text">
        We use cookies to enhance your browsing experience, personalise content, and analyse our traffic.
        By clicking <strong>"Accept All"</strong> you consent to our use of cookies.
        Read our <a href="cookies-policy.php">Cookies Policy</a> for more information.
      </p>
    </div>
    <div class="cookie-banner__actions">
      <button class="cookie-btn cookie-btn--outline" id="cookieCustomise">Manage Preferences</button>
      <button class="cookie-btn cookie-btn--secondary" id="cookieReject">Reject All</button>
      <button class="cookie-btn cookie-btn--primary" id="cookieAccept">Accept All</button>
    </div>
  </div>
</div>

<!-- Cookie Preferences Modal -->
<div class="cookie-modal" id="cookieModal" role="dialog" aria-modal="true" aria-label="Cookie preferences">
  <div class="cookie-modal__overlay" id="cookieModalOverlay"></div>
  <div class="cookie-modal__box">
    <div class="cookie-modal__header">
      <h3>Cookie Preferences</h3>
      <button class="cookie-modal__close" id="cookieModalClose" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="cookie-modal__body">
      <p>Manage your cookie preferences below. Necessary cookies are always enabled as they are required for the website to function correctly.</p>
      <div class="cookie-pref">
        <div class="cookie-pref__info">
          <div class="cookie-pref__name">Necessary Cookies</div>
          <div class="cookie-pref__desc">Essential for the website to function. Cannot be disabled.</div>
        </div>
        <div class="cookie-toggle cookie-toggle--locked"><span>Always On</span></div>
      </div>
      <div class="cookie-pref">
        <div class="cookie-pref__info">
          <div class="cookie-pref__name">Analytics Cookies</div>
          <div class="cookie-pref__desc">Help us understand how visitors interact with our website.</div>
        </div>
        <label class="cookie-toggle">
          <input type="checkbox" id="analyticsToggle" checked />
          <span class="cookie-toggle__slider"></span>
        </label>
      </div>
      <div class="cookie-pref">
        <div class="cookie-pref__info">
          <div class="cookie-pref__name">Marketing Cookies</div>
          <div class="cookie-pref__desc">Used to deliver personalised advertisements relevant to you.</div>
        </div>
        <label class="cookie-toggle">
          <input type="checkbox" id="marketingToggle" />
          <span class="cookie-toggle__slider"></span>
        </label>
      </div>
      <div class="cookie-pref">
        <div class="cookie-pref__info">
          <div class="cookie-pref__name">Functional Cookies</div>
          <div class="cookie-pref__desc">Enable enhanced functionality such as live chat and videos.</div>
        </div>
        <label class="cookie-toggle">
          <input type="checkbox" id="functionalToggle" checked />
          <span class="cookie-toggle__slider"></span>
        </label>
      </div>
    </div>
    <div class="cookie-modal__footer">
      <button class="cookie-btn cookie-btn--outline" id="cookieSavePrefs">Save My Preferences</button>
      <button class="cookie-btn cookie-btn--primary" id="cookieAcceptAll">Accept All</button>
    </div>
  </div>
</div>

<button id="scrollToTop" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>


<!-- ══════════════════════════════════════════════════════
     SEARCH OVERLAY
═══════════════════════════════════════════════════════════ -->
<div class="search-overlay" id="searchOverlay" role="dialog" aria-label="Search" aria-modal="true" aria-hidden="true">
  <div class="search-overlay__backdrop" id="searchBackdrop"></div>
  <div class="search-overlay__box">
    <div class="search-overlay__header">
      <div class="search-overlay__input-wrap">
        <i class="fas fa-search search-overlay__icon"></i>
        <input type="text" id="searchInput" class="search-overlay__input"
               placeholder="Search gemstones, jewellery…" autocomplete="off" spellcheck="false" />
        <button class="search-overlay__clear" id="searchClear" aria-label="Clear search" style="display:none;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <button class="search-overlay__close" id="searchClose" aria-label="Close search">
        <i class="fas fa-times"></i><span>ESC</span>
      </button>
    </div>
    <div class="search-overlay__body">
      <div class="search-default" id="searchDefault">
        <p class="search-default__label">Browse by category</p>
        <div class="search-default__tags">
          <a href="shop.php?cat=blue-sapphire"><i class="fas fa-gem"></i> Blue Sapphires</a>
          <a href="shop.php?cat=padparadscha"><i class="fas fa-gem"></i> Padparadscha</a>
          <a href="shop.php?cat=cats-eye"><i class="fas fa-eye"></i> Cat's Eye</a>
          <a href="shop.php?cat=ruby"><i class="fas fa-gem"></i> Rubies</a>
          <a href="shop.php?cat=jewellery"><i class="fas fa-ring"></i> Jewellery</a>
          <a href="shop.php?cat=loose-gemstones"><i class="fas fa-star"></i> Loose Gems</a>
        </div>
      </div>
      <div class="search-results-list" id="searchResultsList" style="display:none;"></div>
      <div class="search-no-results" id="searchNoResults" style="display:none;">
        <i class="fas fa-gem"></i>
        <p>No results for "<span id="searchNoResultsTerm"></span>"</p>
        <a href="shop.php">Browse all collections <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>


<!-- Core JS -->
<script src="assets/js/main.js"></script>

<!-- Page-specific JS -->
<?php foreach ($extraJS as $js): ?>
<script src="<?= htmlspecialchars($js) ?>"></script>
<?php endforeach; ?>

</body>
</html>
