'use strict';

(function () {

  /* Shared Header HTML */
  var HEADER_HTML = `
<!-- TOP BAR -->
<div class="top-bar">
  <div class="container top-bar__inner">
    <div class="top-bar__contact">
      <a href="tel:+94718456999"><i class="fas fa-phone"></i>+94 71 845 6999</a>
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

<!-- HEADER -->
<header class="site-header" role="banner">
  <div class="header__inner">

    <a href="index.html" class="header__logo" aria-label="Mirabella Ceylon home">
      <img src="assets/images/logo.png" alt="Mirabella Ceylon" onerror="this.style.display='none';" />
      <div class="header__logo-text">
        <span class="brand-name">Mirabella Ceylon</span>
        <span class="brand-tagline">Gems &amp; Jewellery Worldwide</span>
      </div>
    </a>

    <div class="header__divider" aria-hidden="true"></div>

    <nav class="header__nav" aria-label="Main navigation">
      <ul>
        <li><a href="index.html"         data-nav="home">Home</a></li>
        <li><a href="shop.html"          data-nav="shop">Collections</a></li>
        <li><a href="index.html#about"   data-nav="about">About Us</a></li>
        <li><a href="contact.html" data-nav="contact">Contact</a></li>
      </ul>
    </nav>

    <div class="header__actions">
      <button class="header__action-btn" id="searchBtn" aria-label="Search"><i class="fas fa-search"></i></button>
      <div class="account-wrap" id="accountWrap">
        <button class="header__action-btn" id="accountBtn" aria-label="Account" aria-expanded="false" aria-controls="accountDropdown"><i class="far fa-user"></i></button>
        <div class="account-dropdown" id="accountDropdown" aria-hidden="true">
          <div class="account-dropdown__arrow"></div>
          <a href="login.html" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-sign-in-alt"></i></div>
            <div class="account-dropdown__text"><span>Sign In</span><small>Access your account</small></div>
          </a>
          <a href="register.html" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-user-plus"></i></div>
            <div class="account-dropdown__text"><span>Register</span><small>Create a new account</small></div>
          </a>
          <div class="account-dropdown__divider"></div>
          <a href="wishlist.html" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="far fa-heart"></i></div>
            <div class="account-dropdown__text"><span>My Wishlist</span><small>Saved items</small></div>
          </a>
          <a href="orders.html" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-box-open"></i></div>
            <div class="account-dropdown__text"><span>My Orders</span><small>Track your purchases</small></div>
          </a>
          <a href="profile.html" class="account-dropdown__item">
            <div class="account-dropdown__icon"><i class="fas fa-user-cog"></i></div>
            <div class="account-dropdown__text"><span>My Profile</span><small>Settings &amp; preferences</small></div>
          </a>
        </div>
      </div>
      <a href="cart.html" class="header__action-btn" aria-label="Cart">
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
      <li><a href="index.html"         data-nav="home">Home</a></li>
      <li><a href="shop.html"          data-nav="shop">Collections</a></li>
      <li><a href="index.html#about"   data-nav="about">About Us</a></li>
      <li><a href="index.html#contact" data-nav="contact">Contact</a></li>
    </ul>
  </nav>
</header>
`;

  /* Shared Footer HTML */
  var FOOTER_HTML = `
<!-- FOOTER -->
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
        <div class="footer__social">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
          <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
          <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest-p"></i></a>
        </div>
      </div>

      <div>
        <h4 class="footer__col-title">Quick Links</h4>
        <ul class="footer__links">
          <li><a href="index.html"><i class="fas fa-chevron-right"></i> Home</a></li>
          <li><a href="shop.html"><i class="fas fa-chevron-right"></i> Collections</a></li>
          <li><a href="about.html"><i class="fas fa-chevron-right"></i> About Us</a></li>
          <li><a href="contact.html"><i class="fas fa-chevron-right"></i> Contact</a></li>
          <li><a href="refund-policy.html"><i class="fas fa-chevron-right"></i> Refund &amp; Returns</a></li>
          <li><a href="cart.html"><i class="fas fa-chevron-right"></i> Shopping Cart</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer__col-title">Gemstones</h4>
        <ul class="footer__links">
          <li><a href="shop.html?cat=sapphire"><i class="fas fa-chevron-right"></i> Blue Sapphires</a></li>
          <li><a href="shop.html?cat=padparadscha"><i class="fas fa-chevron-right"></i> Padparadscha</a></li>
          <li><a href="shop.html?cat=cats-eye"><i class="fas fa-chevron-right"></i> Cat's Eye</a></li>
          <li><a href="shop.html?cat=ruby"><i class="fas fa-chevron-right"></i> Natural Rubies</a></li>
          <li><a href="shop.html?cat=star-sapphire"><i class="fas fa-chevron-right"></i> Star Sapphires</a></li>
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
        &copy; <span class="footer__year"></span> Mirabella Ceylon. All rights reserved.
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Designed by <a href="https://www.asseminate.com/" target="_blank" rel="noopener noreferrer">Asseminate</a>
      </p>
      <div class="footer__legal">
        <a href="privacy-policy.html">Privacy Policy</a>
        <span class="footer__legal-sep">|</span>
        <a href="terms.html">Terms &amp; Conditions</a>
        <span class="footer__legal-sep">|</span>
        <a href="refund-policy.html">Refund &amp; Returns</a>
      </div>
    </div>
  </div>
</footer>


<!-- COOKIE CONSENT BANNER -->
<div class="cookie-banner" id="cookieBanner" role="dialog" aria-label="Cookie consent" aria-live="polite">
  <div class="cookie-banner__inner">
    <div class="cookie-banner__icon"><i class="fas fa-cookie-bite"></i></div>
    <div class="cookie-banner__content">
      <p class="cookie-banner__title">We value your privacy</p>
      <p class="cookie-banner__text">
        We use cookies to enhance your browsing experience, personalise content, and analyse our traffic.
        By clicking <strong>"Accept All"</strong> you consent to our use of cookies.
        Read our <a href="cookies-policy.html">Cookies Policy</a> for more information.
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

<!-- SEARCH OVERLAY -->
<div class="search-overlay" id="searchOverlay" role="dialog" aria-label="Search" aria-modal="true" aria-hidden="true">
  <div class="search-overlay__backdrop" id="searchBackdrop"></div>
  <div class="search-overlay__box">
    <div class="search-overlay__header">
      <div class="search-overlay__input-wrap">
        <i class="fas fa-search search-overlay__icon"></i>
        <input type="text" id="searchInput" class="search-overlay__input" placeholder="Search gemstones, jewellery…" autocomplete="off" spellcheck="false" />
        <button class="search-overlay__clear" id="searchClear" aria-label="Clear search" style="display:none;"><i class="fas fa-times"></i></button>
      </div>
      <button class="search-overlay__close" id="searchClose" aria-label="Close search">
        <i class="fas fa-times"></i><span>ESC</span>
      </button>
    </div>
    <div class="search-overlay__body">
      <div class="search-default" id="searchDefault">
        <p class="search-default__label">Browse by category</p>
        <div class="search-default__tags">
          <a href="shop.html?cat=sapphire"><i class="fas fa-gem"></i> Blue Sapphires</a>
          <a href="shop.html?cat=padparadscha"><i class="fas fa-gem"></i> Padparadscha</a>
          <a href="shop.html?cat=cats-eye"><i class="fas fa-eye"></i> Cat's Eye</a>
          <a href="shop.html?cat=ruby"><i class="fas fa-gem"></i> Rubies</a>
          <a href="shop.html?cat=star-sapphire"><i class="fas fa-star"></i> Star Sapphires</a>
          <a href="shop.html?cat=jewellery"><i class="fas fa-ring"></i> Jewellery</a>
        </div>
      </div>
      <div class="search-results-list" id="searchResultsList" style="display:none;"></div>
      <div class="search-no-results" id="searchNoResults" style="display:none;">
        <i class="fas fa-gem"></i>
        <p>No results for "<span id="searchNoResultsTerm"></span>"</p>
        <a href="shop.html">Browse all collections <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>
`;

  /* Boot on DOMContentLoaded */
  document.addEventListener('DOMContentLoaded', function () {
    injectHTML('mc-header', HEADER_HTML);
    injectHTML('mc-footer', FOOTER_HTML);

    setActiveNav();
    setHeaderClass();
    setFooterYear();

    /* Signal main.js that the DOM is fully ready */
    document.dispatchEvent(new CustomEvent('mc:ready'));
  });


  /* Replace placeholder div with HTML string */
  function injectHTML(id, html) {
    var el = document.getElementById(id);
    if (!el) return;
    var temp = document.createElement('div');
    temp.innerHTML = html;
    while (temp.firstChild) {
      el.parentNode.insertBefore(temp.firstChild, el);
    }
    el.parentNode.removeChild(el);
  }


  /* Mark the correct nav link as active */
  function setActiveNav() {
    var page  = document.body.dataset.page || 'home';
    var links = document.querySelectorAll('[data-nav]');
    links.forEach(function (link) {
      link.classList.remove('active');
      if (link.dataset.nav === page) {
        link.classList.add('active');
      }
    });
  }


  /* Apply initial header class from data-header */
  function setHeaderClass() {
    var header = document.querySelector('.site-header');
    if (!header) return;
    var mode = document.body.dataset.header || 'transparent';
    if (mode === 'solid') {
      header.classList.add('is-solid');
      header.classList.remove('is-transparent');
    } else {
      header.classList.add('is-transparent');
      header.classList.remove('is-solid');
    }
  }


  /* Set the copyright year automatically */
  function setFooterYear() {
    var el = document.querySelector('.footer__year');
    if (el) el.textContent = new Date().getFullYear();
  }

})();
