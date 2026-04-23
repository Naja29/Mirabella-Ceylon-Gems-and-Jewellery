<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/site_settings.php';

$storeEmail  = get_site_setting('store_email',      'info@mirabelaceylon.com');
$storePhone  = get_site_setting('store_phone',      '+94 77 123 4567');
$waNumber    = get_site_setting('whatsapp_number',  '+94771234567');
$waLink      = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $waNumber);

$social = [
    'facebook'  => ['url' => get_site_setting('social_facebook'),  'icon' => 'fab fa-facebook-f',  'label' => 'Facebook'],
    'instagram' => ['url' => get_site_setting('social_instagram'), 'icon' => 'fab fa-instagram',   'label' => 'Instagram'],
    'youtube'   => ['url' => get_site_setting('social_youtube'),   'icon' => 'fab fa-youtube',     'label' => 'YouTube'],
    'linkedin'  => ['url' => get_site_setting('social_linkedin'),  'icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn'],
    'whatsapp'  => ['url' => $waLink,                              'icon' => 'fab fa-whatsapp',    'label' => 'WhatsApp', 'style' => 'color:#25d366;border-color:rgba(37,211,102,0.3);'],
    'pinterest' => ['url' => get_site_setting('social_pinterest'), 'icon' => 'fab fa-pinterest-p', 'label' => 'Pinterest'],
];
$activeSocial = array_filter($social, fn($s) => !empty($s['url']));

$pageTitle   = 'Contact Us | Mirabella Ceylon';
$pageDesc    = 'Contact Mirabella Ceylon — reach our gemstone specialists via WhatsApp, email or visit our Colombo showroom.';
$activePage  = 'contact';
$headerClass = 'is-solid';
$extraCSS    = ['assets/css/contact.css'];
include 'includes/header.php';
?>

<!-- HERO -->
<section class="contact-hero">
  <div class="container">
    <div class="contact-hero__eyebrow">Get in Touch</div>
    <h1 class="contact-hero__title">We'd love to hear<br /><em>from you</em></h1>
    <p class="contact-hero__sub">
      Whether you're looking for a specific gemstone, need certification advice,
      or want to place a custom order - our specialists are ready to help.
    </p>
    <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="contact-hero__wa-btn">
      <i class="fab fa-whatsapp"></i>
      Chat on WhatsApp — fastest response
    </a>
  </div>
</section>


<!-- MAIN SECTION -->
<section class="contact-section">
  <div class="container">
    <div class="contact-layout">

      <!-- LEFT: Form -->
      <div>

        <div class="contact-form-wrap">
          <div class="contact-form-wrap__head">
            <i class="fas fa-paper-plane"></i>
            <div>
              <div class="contact-form-wrap__title">Send us a Message</div>
              <div class="contact-form-wrap__sub">We typically reply within 2 - 4 hours during business hours</div>
            </div>
          </div>
          <div class="contact-form-wrap__body">

            <!-- Success state (hidden by default) -->
            <div class="cf-success" id="cfSuccess">
              <div class="cf-success__check"><i class="fas fa-check"></i></div>
              <div class="cf-success__title">Message received!</div>
              <p class="cf-success__sub">
                Thank you for reaching out. Our team will get back to you
                within 2 - 4 hours during business hours.
              </p>
            </div>

            <!-- Form -->
            <form class="cf-form" id="contactForm" novalidate>

              <div class="cf-row">
                <div class="cf-field">
                  <label for="cf-name">Your Name</label>
                  <div class="cf-field__wrap">
                    <i class="fas fa-user cf-field__icon"></i>
                    <input type="text" id="cf-name" name="name" placeholder="Kasun Perera" autocomplete="name" required />
                  </div>
                </div>
                <div class="cf-field">
                  <label for="cf-email">Email Address</label>
                  <div class="cf-field__wrap">
                    <i class="fas fa-envelope cf-field__icon"></i>
                    <input type="email" id="cf-email" name="email" placeholder="you@example.com" autocomplete="email" required />
                  </div>
                </div>
              </div>

              <div class="cf-row">
                <div class="cf-field">
                  <label for="cf-phone">Phone / WhatsApp <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                  <div class="cf-field__wrap">
                    <i class="fas fa-phone cf-field__icon"></i>
                    <input type="tel" id="cf-phone" name="phone" placeholder="+94 77 123 4567" autocomplete="tel" />
                  </div>
                </div>
                <div class="cf-field">
                  <label for="cf-subject">Subject</label>
                  <div class="cf-field__wrap">
                    <i class="fas fa-tag cf-field__icon"></i>
                    <select id="cf-subject" name="subject" required>
                      <option value="" disabled selected>Select a topic</option>
                      <option value="General Inquiry">General Inquiry</option>
                      <option value="Order Support">Order Support</option>
                      <option value="Custom Order / Bespoke">Custom Order / Bespoke</option>
                      <option value="Gem Certification (NGJA / GIA)">Gem Certification (NGJA / GIA)</option>
                      <option value="Wholesale Inquiry">Wholesale Inquiry</option>
                      <option value="Export Documentation">Export Documentation</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="cf-field">
                <label for="cf-message">Message</label>
                <div class="cf-field__wrap" style="align-items:flex-start;">
                  <i class="fas fa-comment-alt cf-field__icon" style="top:13px;position:absolute;"></i>
                  <textarea id="cf-message" name="message" placeholder="Tell us what you're looking for, your budget, preferred gemstone, or any other details…" required></textarea>
                </div>
              </div>

              <div class="cf-submit-row">
                <button type="submit" class="btn btn-primary" id="cfSubmitBtn">
                  <i class="fas fa-paper-plane"></i> &nbsp;Send Message
                </button>
                <div class="cf-wa-alt">
                  or&nbsp;
                  <a href="#" id="cfWaBtn">
                    <i class="fab fa-whatsapp"></i> Send via WhatsApp
                  </a>
                </div>
              </div>

            </form>

          </div>
        </div>

        <!-- FAQ -->
        <div class="contact-faq">
          <div class="contact-faq__head">
            <i class="fas fa-question-circle"></i>
            Frequently Asked Questions
          </div>

          <div class="contact-faq__item">
            <button class="contact-faq__q" type="button">
              Are all your gemstones certified?
              <i class="fas fa-chevron-down"></i>
            </button>
            <div class="contact-faq__a">
              Yes. Every gemstone sold by Mirabella Ceylon carries certification from the
              <strong>National Gem &amp; Jewellery Authority of Sri Lanka (NGJA)</strong> and/or
              <strong>GIA</strong>. Certificates are included with every order.
            </div>
          </div>

          <div class="contact-faq__item">
            <button class="contact-faq__q" type="button">
              Do you ship internationally?
              <i class="fas fa-chevron-down"></i>
            </button>
            <div class="contact-faq__a">
              Yes — we ship worldwide with full insurance and tracking. Free standard shipping
              is available on orders over $500. Express and overnight courier options are also available.
            </div>
          </div>

          <div class="contact-faq__item">
            <button class="contact-faq__q" type="button">
              Can I place a custom or bespoke order?
              <i class="fas fa-chevron-down"></i>
            </button>
            <div class="contact-faq__a">
              Absolutely. We specialise in sourcing specific gemstones by colour, carat, cut,
              and origin. Contact us with your requirements and our team will find the best match
              from our Ratnapura sourcing network.
            </div>
          </div>

          <div class="contact-faq__item">
            <button class="contact-faq__q" type="button">
              What payment methods do you accept?
              <i class="fas fa-chevron-down"></i>
            </button>
            <div class="contact-faq__a">
              We currently accept <strong>Bank Transfer</strong>, <strong>FriMi</strong>, and
              <strong>eZ Cash</strong> for local orders, and <strong>Credit Card</strong> /
              <strong>PayPal</strong> for international orders. Online card payment integration
              is coming soon.
            </div>
          </div>

          <div class="contact-faq__item">
            <button class="contact-faq__q" type="button">
              What is your return policy?
              <i class="fas fa-chevron-down"></i>
            </button>
            <div class="contact-faq__a">
              We offer a <strong>14-day no-questions-asked return policy</strong> on all
              certified gemstones. The item must be returned in its original condition with
              the certificate. Contact us via WhatsApp to initiate a return.
            </div>
          </div>

        </div>

      </div>


      <!-- RIGHT: Info Sidebar -->
      <div class="contact-info">

        <?php if ($waNumber): ?>
        <div class="contact-card">
          <div class="contact-card__inner">
            <div class="contact-card__icon contact-card__icon--green">
              <i class="fab fa-whatsapp"></i>
            </div>
            <div>
              <div class="contact-card__label">WhatsApp (Fastest)</div>
              <div class="contact-card__value">
                <a href="<?= htmlspecialchars($waLink) ?>" target="_blank"><?= htmlspecialchars($waNumber) ?></a>
              </div>
              <div class="contact-card__sub">Typically replies within 1 hour<br />Available 7 days a week</div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($storeEmail): ?>
        <div class="contact-card">
          <div class="contact-card__inner">
            <div class="contact-card__icon contact-card__icon--blue">
              <i class="fas fa-envelope"></i>
            </div>
            <div>
              <div class="contact-card__label">Email</div>
              <div class="contact-card__value">
                <a href="mailto:<?= htmlspecialchars($storeEmail) ?>"><?= htmlspecialchars($storeEmail) ?></a>
              </div>
              <div class="contact-card__sub">For detailed inquiries &amp; documentation<br />Response within 24 hours</div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="contact-card">
          <div class="contact-card__inner">
            <div class="contact-card__icon contact-card__icon--gold">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div>
              <div class="contact-card__label">Colombo Showroom</div>
              <div class="contact-card__value">42 Galle Road, Colombo 03<br />Sri Lanka</div>
              <div class="contact-card__sub">Near Kollupitiya Junction<br />Appointments recommended</div>
            </div>
          </div>
          <div class="contact-map__frame">
            <i class="fas fa-map-marked-alt"></i>
            <span>42 Galle Road, Colombo 03</span>
            <a href="https://maps.google.com/?q=42+Galle+Road+Colombo+03+Sri+Lanka" target="_blank">
              Open in Google Maps <i class="fas fa-external-link-alt" style="font-size:10px;"></i>
            </a>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-card__inner">
            <div class="contact-card__icon contact-card__icon--purple">
              <i class="fas fa-clock"></i>
            </div>
            <div style="width:100%;">
              <div class="contact-card__label">Opening Hours</div>
              <table class="contact-hours" id="contactHours">
                <tr><td>Monday – Friday</td><td>9:00 AM – 6:00 PM</td></tr>
                <tr><td>Saturday</td><td>9:00 AM – 4:00 PM</td></tr>
                <tr><td>Sunday</td><td>Closed</td></tr>
                <tr><td>Public Holidays</td><td>Closed</td></tr>
              </table>
            </div>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-card__inner">
            <div class="contact-card__icon contact-card__icon--gold">
              <i class="fas fa-gem"></i>
            </div>
            <div>
              <div class="contact-card__label">Gem Sourcing Office</div>
              <div class="contact-card__value">Ratnapura, Sabaragamuwa<br />Sri Lanka</div>
              <div class="contact-card__sub">The Gem Capital of the World<br />Wholesale &amp; sourcing inquiries welcome</div>
            </div>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-card__inner">
            <div class="contact-card__icon contact-card__icon--gold">
              <i class="fas fa-share-alt"></i>
            </div>
            <div>
              <div class="contact-card__label">Follow Us</div>
              <div class="contact-card__sub" style="margin-top:4px;">Stay updated with new arrivals &amp; gem insights</div>
            </div>
          </div>
          <?php if ($activeSocial): ?>
          <div class="contact-socials">
            <?php foreach ($activeSocial as $s): ?>
            <a href="<?= htmlspecialchars($s['url']) ?>" target="_blank" rel="noopener noreferrer"
               class="contact-social-btn" aria-label="<?= htmlspecialchars($s['label']) ?>"
               <?= isset($s['style']) ? 'style="' . $s['style'] . '"' : '' ?>>
              <i class="<?= $s['icon'] ?>"></i>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      </div>

    </div>
  </div>
</section>

<script>window.MC_WA_NUMBER = '<?= preg_replace('/[^0-9]/', '', $waNumber) ?>';</script>
<?php
$extraJS = ['assets/js/contact.js'];
include 'includes/footer.php';
?>
