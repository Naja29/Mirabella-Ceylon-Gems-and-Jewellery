<?php
require_once __DIR__ . '/includes/site_settings.php';
$pageTitle   = 'Refund & Return Policy | Mirabella Ceylon';
$pageDesc    = 'Refund & Return Policy — Mirabella Ceylon. 14-day hassle-free returns on certified gemstones.';
$activePage  = '';
$headerClass = 'is-solid';
$extraCSS    = ['assets/css/policy.css'];
include 'includes/header.php';
?>

<div class="policy-hero">
  <div class="container policy-hero__inner">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
        <span>Refund &amp; Return Policy</span>
      </nav>
      <div class="policy-hero__eyebrow">Customer Protection</div>
      <h1 class="policy-hero__title">Refund &amp; Return Policy</h1>
      <p class="policy-hero__meta">Last updated: <strong>1 January 2025</strong></p>
    </div>
    <div class="policy-hero__icon" aria-hidden="true"><i class="fas fa-undo-alt"></i></div>
  </div>
</div>

<div class="policy-body">
  <div class="container policy-layout">

    <nav class="policy-toc" aria-label="Table of contents">
      <div class="policy-toc__title">Contents</div>
      <div class="policy-toc__list">
        <a href="#rp-overview"      class="policy-toc__link">1. Overview</a>
        <a href="#rp-eligible"      class="policy-toc__link">2. Eligible Items</a>
        <a href="#rp-not-eligible"  class="policy-toc__link">3. Non-Eligible Items</a>
        <a href="#rp-how"           class="policy-toc__link">4. How to Initiate a Return</a>
        <a href="#rp-process"       class="policy-toc__link">5. Refund Process</a>
        <a href="#rp-exchange"      class="policy-toc__link">6. Exchange Policy</a>
        <a href="#rp-international" class="policy-toc__link">7. International Returns</a>
        <a href="#rp-damaged"       class="policy-toc__link">8. Damaged or Incorrect Items</a>
        <a href="#rp-contact"       class="policy-toc__link">9. Contact Us</a>
      </div>
    </nav>

    <div class="policy-content">

      <div class="policy-section" id="rp-overview">
        <div class="policy-section__num">Section 01</div>
        <h2 class="policy-section__title">Overview</h2>
        <p>At Mirabella Ceylon, your satisfaction is our highest priority. We stand behind every certified gemstone we sell and offer a straightforward, no-hassle return process to give you complete confidence in your purchase.</p>
        <p>You may return any eligible item within <strong>14 days</strong> of the delivery date for a full refund or exchange — no questions asked.</p>
        <div class="policy-highlight">
          <strong><i class="fas fa-gem" style="color:var(--gold);margin-right:8px;"></i>Our Promise:</strong>
          Every gemstone sold by Mirabella Ceylon is certified by the National Gem &amp; Jewellery Authority of Sri Lanka (NGJA) and/or GIA. If your item arrives in a condition that does not match its certification or description, we will arrange a full refund and cover all return shipping costs.
        </div>
      </div>

      <div class="policy-section" id="rp-eligible">
        <div class="policy-section__num">Section 02</div>
        <h2 class="policy-section__title">Eligible Items</h2>
        <p>The following items are eligible for return and refund:</p>
        <ul class="policy-list">
          <li>Loose certified gemstones returned in original condition with the gemstone certificate intact</li>
          <li>Jewellery items that are unworn, unaltered, and returned with original packaging and documentation</li>
          <li>Items damaged during shipping or that do not match the product page description</li>
          <li>Items where a material error was made in the listing (e.g. incorrect weight, origin, or treatment status)</li>
        </ul>
        <p>To qualify, the item must be returned within <strong>14 calendar days</strong> of delivery, in original unused condition, with the original gemstone certificate.</p>
      </div>

      <div class="policy-section" id="rp-not-eligible">
        <div class="policy-section__num">Section 03</div>
        <h2 class="policy-section__title">Non-Eligible Items</h2>
        <p>The following items are <strong>not</strong> eligible for return or refund:</p>
        <ul class="policy-list">
          <li><strong>Custom and bespoke orders</strong> — gemstones sourced or mounted to your specific requirements</li>
          <li><strong>Items returned after 14 days</strong> from the delivery date</li>
          <li><strong>Items that have been worn, set, drilled, resized, or altered</strong> in any way</li>
          <li><strong>Items with missing, altered, or tampered certificates</strong></li>
          <li><strong>Items damaged due to buyer mishandling</strong> or negligence</li>
          <li><strong>Sale or clearance items</strong> marked as final sale</li>
        </ul>
      </div>

      <div class="policy-section" id="rp-how">
        <div class="policy-section__num">Section 04</div>
        <h2 class="policy-section__title">How to Initiate a Return</h2>
        <ol class="policy-list">
          <?php
            $rpWa    = preg_replace('/[^0-9]/', '', get_site_setting('whatsapp_number', '94718456999'));
            $rpTel   = get_site_setting('whatsapp_number', '+94 71 845 6999');
            $rpEmail = get_site_setting('store_email', 'returns@mirabelaceylon.com');
          ?>
          <li><strong>Contact us within 14 days of delivery</strong> via WhatsApp at <a href="https://wa.me/<?= $rpWa ?>" style="color:var(--gold-dark);font-weight:600;"><?= htmlspecialchars($rpTel) ?></a> or email <a href="mailto:<?= htmlspecialchars($rpEmail) ?>" style="color:var(--gold-dark);font-weight:600;"><?= htmlspecialchars($rpEmail) ?></a></li>
          <li><strong>Provide your order number</strong> and photos of the item and certificate</li>
          <li>Once approved, we provide the <strong>return shipping address</strong> and packing instructions</li>
          <li>Ship using a <strong>trackable, insured courier</strong> and share the tracking number with us</li>
        </ol>
        <div class="policy-highlight">
          <strong>Important:</strong> Do not send returns without prior authorisation. Unauthorised returns may not be processed.
        </div>
      </div>

      <div class="policy-section" id="rp-process">
        <div class="policy-section__num">Section 05</div>
        <h2 class="policy-section__title">Refund Process</h2>
        <p>Once we receive and inspect the returned item, we will notify you within <strong>2 business days</strong>.</p>
        <ul class="policy-list">
          <li>Refund processed to the <strong>original payment method</strong></li>
          <li>Credit card refunds: <strong>5–10 business days</strong></li>
          <li>Bank transfer / PayPal: <strong>3–5 business days</strong></li>
          <li>No restocking fees — full purchase price refunded</li>
        </ul>
      </div>

      <div class="policy-section" id="rp-exchange">
        <div class="policy-section__num">Section 06</div>
        <h2 class="policy-section__title">Exchange Policy</h2>
        <p>Exchanges follow the same eligibility rules as returns. Contact us via WhatsApp within 14 days to discuss options. Price differences will be charged or refunded accordingly.</p>
      </div>

      <div class="policy-section" id="rp-international">
        <div class="policy-section__num">Section 07</div>
        <h2 class="policy-section__title">International Returns</h2>
        <ul class="policy-list">
          <li>Return shipping costs are the customer's responsibility unless the item was damaged or incorrectly described</li>
          <li>Use a trackable, insured courier such as DHL, FedEx, or UPS</li>
          <li>Mark the parcel <strong>"Returned Goods — No Commercial Value"</strong> on the customs declaration</li>
          <li>Contact us before sending so we can provide correct documentation and the return address</li>
        </ul>
      </div>

      <div class="policy-section" id="rp-damaged">
        <div class="policy-section__num">Section 08</div>
        <h2 class="policy-section__title">Damaged or Incorrect Items</h2>
        <h4>If your item arrives damaged:</h4>
        <ul class="policy-list">
          <li>Photograph the damaged packaging and item before opening fully</li>
          <li>Contact us within <strong>48 hours of delivery</strong> via WhatsApp with photos and your order number</li>
          <li>We will arrange a full replacement or refund and cover all return shipping costs</li>
        </ul>
        <h4>If you received the wrong item:</h4>
        <ul class="policy-list">
          <li>Contact us immediately with your order number and photos</li>
          <li>We will dispatch the correct item and provide a prepaid return label</li>
        </ul>
        <div class="policy-highlight">
          <strong><i class="fas fa-shield-alt" style="color:var(--gold);margin-right:8px;"></i>Fully Insured Shipping:</strong>
          All orders are shipped fully insured for their declared value. In the rare event of loss or damage in transit, you are fully covered.
        </div>
      </div>

      <div class="policy-section" id="rp-contact">
        <div class="policy-section__num">Section 09</div>
        <h2 class="policy-section__title">Contact Us</h2>
        <p>Our team is available 7 days a week to assist with any return or refund enquiry.</p>
        <div class="policy-contact-box">
          <p><strong>Mirabella Ceylon — Gems &amp; Jewellery Worldwide</strong></p>
          <p>Ratnapura, Sri Lanka</p>
          <p>WhatsApp: <a href="https://wa.me/<?= $rpWa ?>"><?= htmlspecialchars($rpTel) ?></a> &nbsp;·&nbsp; <a href="https://wa.me/<?= $rpWa ?>" style="color:#25d366;font-weight:700;"><i class="fab fa-whatsapp"></i> Chat Now</a></p>
          <p>Returns Email: <a href="mailto:<?= htmlspecialchars($rpEmail) ?>"><?= htmlspecialchars($rpEmail) ?></a></p>
        </div>
        <p style="margin-top:18px;">We aim to respond to all return requests within <strong>24 hours</strong> (Mon–Sat, 9 AM – 6 PM Sri Lanka time).</p>
      </div>

    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
