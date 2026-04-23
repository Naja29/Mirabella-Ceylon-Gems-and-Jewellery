<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/site_settings.php';

$pay = [
    'bank_name'         => get_site_setting('bank_name',         'Bank of Ceylon'),
    'bank_branch'       => get_site_setting('bank_branch',       'Colombo 03'),
    'bank_account_name' => get_site_setting('bank_account_name', 'Mirabella Ceylon (Pvt) Ltd'),
    'bank_account_no'   => get_site_setting('bank_account_no',   '0072 1234 5678'),
    'bank_swift'        => get_site_setting('bank_swift',        'BCEYLKLX'),
    'frimi_number'      => get_site_setting('frimi_number',      '077 123 4567'),
    'frimi_name'        => get_site_setting('frimi_name',        'Mirabella Ceylon (Pvt) Ltd'),
    'ezcash_number'     => get_site_setting('ezcash_number',     '071 123 4567'),
    'ezcash_name'       => get_site_setting('ezcash_name',       'Mirabella Ceylon (Pvt) Ltd'),
    'whatsapp_number'   => get_site_setting('whatsapp_number',   '+94771234567'),
];

$items    = cart_items();
$subtotal = cart_subtotal($items);

// Redirect to cart if empty
if (empty($items)) {
    header('Location: cart.php');
    exit;
}

// Pre-fill from session
$prefill = [
    'first_name' => $_SESSION['customer_fname'] ?? '',
    'last_name'  => $_SESSION['customer_lname'] ?? '',
    'email'      => $_SESSION['customer_email'] ?? '',
    'phone'      => '',
];
// Load phone if logged in
if (customer_logged_in()) {
    $st = db()->prepare('SELECT phone FROM customers WHERE id = ?');
    $st->execute([$_SESSION['customer_id']]);
    $prefill['phone'] = $st->fetchColumn() ?: '';
}

$pageTitle   = 'Checkout | Mirabella Ceylon';
$pageDesc    = 'Complete your order securely — Mirabella Ceylon Gems & Jewellery.';
$headerClass = 'is-solid';
$extraCSS    = ['assets/css/checkout.css'];
include 'includes/header.php';
?>

<!-- BREADCRUMB -->
<div class="checkout-breadcrumb-bar">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <a href="shop.php">Collections</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <a href="cart.php">Shopping Cart</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <span>Checkout</span>
    </nav>
  </div>
</div>


<!-- CHECKOUT SECTION -->
<section class="checkout-section">
  <div class="container">

    <!-- Step Indicator -->
    <div class="checkout-steps" role="list">
      <div class="checkout-step done" role="listitem">
        <div class="checkout-step__num"><i class="fas fa-check" style="font-size:10px;"></i></div>
        <span class="checkout-step__label">CART</span>
      </div>
      <div class="checkout-step__line done"></div>
      <div class="checkout-step active" role="listitem">
        <div class="checkout-step__num">2</div>
        <span class="checkout-step__label">CHECKOUT</span>
      </div>
      <div class="checkout-step__line"></div>
      <div class="checkout-step" role="listitem">
        <div class="checkout-step__num">3</div>
        <span class="checkout-step__label">CONFIRMATION</span>
      </div>
    </div>

    <div class="checkout-layout">

      <!-- LEFT: Form Panels -->
      <div class="checkout-form-wrap">

        <!-- 1. Contact Information -->
        <div class="checkout-card" id="cardContact">
          <div class="checkout-card__head">
            <div class="checkout-card__title"><i class="fas fa-user"></i> Contact Information</div>
          </div>
          <div class="checkout-card__body">
            <div class="co-form">
              <div class="co-row">
                <div class="co-field">
                  <label for="co-firstName">First Name</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-user co-field__icon"></i>
                    <input type="text" id="co-firstName" placeholder="John" autocomplete="given-name"
                           value="<?= htmlspecialchars($prefill['first_name']) ?>" required />
                  </div>
                </div>
                <div class="co-field">
                  <label for="co-lastName">Last Name</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-user co-field__icon"></i>
                    <input type="text" id="co-lastName" placeholder="Smith" autocomplete="family-name"
                           value="<?= htmlspecialchars($prefill['last_name']) ?>" required />
                  </div>
                </div>
              </div>
              <div class="co-row">
                <div class="co-field">
                  <label for="co-email">Email Address</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-envelope co-field__icon"></i>
                    <input type="email" id="co-email" placeholder="you@example.com" autocomplete="email"
                           value="<?= htmlspecialchars($prefill['email']) ?>" required />
                  </div>
                </div>
                <div class="co-field">
                  <label for="co-phone">Phone Number</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-phone co-field__icon"></i>
                    <input type="tel" id="co-phone" placeholder="+94 77 123 4567" autocomplete="tel"
                           value="<?= htmlspecialchars($prefill['phone']) ?>" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Shipping Address -->
        <div class="checkout-card" id="cardShippingAddr">
          <div class="checkout-card__head">
            <div class="checkout-card__title"><i class="fas fa-map-marker-alt"></i> Shipping Address</div>
          </div>
          <div class="checkout-card__body">
            <div class="co-form">
              <div class="co-field">
                <label for="co-address1">Street Address</label>
                <div class="co-field__wrap">
                  <i class="fas fa-home co-field__icon"></i>
                  <input type="text" id="co-address1" placeholder="123 Main Street" autocomplete="address-line1" required />
                </div>
              </div>
              <div class="co-field">
                <label for="co-address2">Apartment, Suite, etc. <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                <div class="co-field__wrap">
                  <i class="fas fa-building co-field__icon"></i>
                  <input type="text" id="co-address2" placeholder="Apt 4B" autocomplete="address-line2" />
                </div>
              </div>
              <div class="co-row">
                <div class="co-field">
                  <label for="co-city">City / Town</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-city co-field__icon"></i>
                    <input type="text" id="co-city" placeholder="Colombo" autocomplete="address-level2" required />
                  </div>
                </div>
                <div class="co-field">
                  <label for="co-country">Country</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-globe co-field__icon"></i>
                    <select id="co-country" autocomplete="country-name" required>
                      <option value="" disabled>Select country</option>
                      <option value="LK" selected>Sri Lanka</option>
                      <optgroup label="────────────────">
                        <option value="US">United States</option>
                        <option value="GB">United Kingdom</option>
                        <option value="AU">Australia</option>
                        <option value="CA">Canada</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="JP">Japan</option>
                        <option value="SG">Singapore</option>
                        <option value="AE">United Arab Emirates</option>
                        <option value="IN">India</option>
                        <option value="other">Other</option>
                      </optgroup>
                    </select>
                  </div>
                </div>
              </div>

              <div class="co-field" id="fieldDistrict">
                <label for="co-district">District</label>
                <div class="co-field__wrap">
                  <i class="fas fa-map-marker-alt co-field__icon"></i>
                  <select id="co-district">
                    <option value="" disabled selected>Select district</option>
                    <?php foreach(['Colombo','Gampaha','Kalutara','Kandy','Matale','Nuwara Eliya','Galle','Matara','Hambantota','Jaffna','Kilinochchi','Mannar','Vavuniya','Mullaitivu','Batticaloa','Ampara','Trincomalee','Kurunegala','Puttalam','Anuradhapura','Polonnaruwa','Badulla','Moneragala','Ratnapura','Kegalle'] as $d): ?>
                    <option><?= $d ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="co-field" id="fieldState" style="display:none;">
                <label for="co-state">State / Province</label>
                <div class="co-field__wrap">
                  <i class="fas fa-map co-field__icon"></i>
                  <input type="text" id="co-state" placeholder="e.g. New York" autocomplete="address-level1" />
                </div>
              </div>

              <div class="co-field">
                <label for="co-zip">Postal Code <span id="zipOptional" style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                <div class="co-field__wrap">
                  <i class="fas fa-envelope-open-text co-field__icon"></i>
                  <input type="text" id="co-zip" placeholder="e.g. 10001" autocomplete="postal-code" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Shipping Method -->
        <div class="checkout-card" id="cardShippingMethod">
          <div class="checkout-card__head">
            <div class="checkout-card__title"><i class="fas fa-shipping-fast"></i> Shipping Method</div>
          </div>
          <div class="checkout-card__body">
            <div class="shipping-options" id="shippingOptions">

              <label class="shipping-option selected" data-zone="local">
                <input type="radio" name="shipping" value="local-island" checked />
                <div class="shipping-option__radio"></div>
                <div class="shipping-option__icon"><i class="fas fa-box-open"></i></div>
                <div class="shipping-option__info">
                  <div class="shipping-option__name">Island-wide Courier</div>
                  <div class="shipping-option__desc">2 – 4 business days · Tracked &amp; insured</div>
                </div>
                <div class="shipping-option__price free">Free</div>
              </label>

              <label class="shipping-option" data-zone="local">
                <input type="radio" name="shipping" value="local-sameday" />
                <div class="shipping-option__radio"></div>
                <div class="shipping-option__icon"><i class="fas fa-bolt"></i></div>
                <div class="shipping-option__info">
                  <div class="shipping-option__name">Colombo Same-Day Delivery</div>
                  <div class="shipping-option__desc">Colombo district only · Order before 12 noon</div>
                </div>
                <div class="shipping-option__price" data-lkr="500">Rs. 500</div>
              </label>

              <label class="shipping-option" data-zone="local">
                <input type="radio" name="shipping" value="local-pickup" />
                <div class="shipping-option__radio"></div>
                <div class="shipping-option__icon"><i class="fas fa-store"></i></div>
                <div class="shipping-option__info">
                  <div class="shipping-option__name">Pick Up — Colombo Showroom</div>
                  <div class="shipping-option__desc">Ready within 24 hrs · 42 Galle Rd, Colombo 03</div>
                </div>
                <div class="shipping-option__price free">Free</div>
              </label>

              <label class="shipping-option" data-zone="intl" style="display:none;">
                <input type="radio" name="shipping" value="intl-standard" />
                <div class="shipping-option__radio"></div>
                <div class="shipping-option__icon"><i class="fas fa-box-open"></i></div>
                <div class="shipping-option__info">
                  <div class="shipping-option__name">Free Standard Shipping</div>
                  <div class="shipping-option__desc">7 – 14 business days · Tracked &amp; insured</div>
                </div>
                <div class="shipping-option__price free">Free</div>
              </label>

              <label class="shipping-option" data-zone="intl" style="display:none;">
                <input type="radio" name="shipping" value="intl-express" />
                <div class="shipping-option__radio"></div>
                <div class="shipping-option__icon"><i class="fas fa-shipping-fast"></i></div>
                <div class="shipping-option__info">
                  <div class="shipping-option__name">Express Shipping</div>
                  <div class="shipping-option__desc">3 – 5 business days · Priority handling</div>
                </div>
                <div class="shipping-option__price" data-usd="24">$24.00</div>
              </label>

              <label class="shipping-option" data-zone="intl" style="display:none;">
                <input type="radio" name="shipping" value="intl-overnight" />
                <div class="shipping-option__radio"></div>
                <div class="shipping-option__icon"><i class="fas fa-paper-plane"></i></div>
                <div class="shipping-option__info">
                  <div class="shipping-option__name">Overnight Courier</div>
                  <div class="shipping-option__desc">Next business day · White-glove delivery</div>
                </div>
                <div class="shipping-option__price" data-usd="58">$58.00</div>
              </label>

            </div>
          </div>
        </div>

        <!-- 4. Payment -->
        <div class="checkout-card" id="cardPayment">
          <div class="checkout-card__head">
            <div class="checkout-card__title"><i class="fas fa-credit-card"></i> Payment</div>
          </div>
          <div class="checkout-card__body">

            <div class="payment-methods" id="paymentMethods">
              <button class="payment-method active" data-method="bank" type="button" data-zone="local">
                <i class="fas fa-university"></i> Bank Transfer
              </button>
              <button class="payment-method" data-method="friMi" type="button" data-zone="local">
                <i class="fas fa-mobile-alt"></i> FriMi
              </button>
              <button class="payment-method" data-method="ezCash" type="button" data-zone="local">
                <i class="fas fa-mobile-alt"></i> eZ Cash
              </button>
              <button class="payment-method" data-method="card" type="button" data-zone="intl" style="display:none;">
                <i class="fab fa-cc-visa"></i> Credit Card
              </button>
              <button class="payment-method" data-method="paypal" type="button" data-zone="intl" style="display:none;">
                <i class="fab fa-cc-paypal"></i> PayPal
              </button>
            </div>

            <div class="pay-panel" id="panelBank">
              <div class="bank-details">
                <div class="bank-details__header"><i class="fas fa-university"></i><span>Bank Transfer Details</span></div>
                <div class="bank-details__rows">
                  <?php if ($pay['bank_name']): ?><div class="bank-details__row"><span>Bank</span><strong><?= htmlspecialchars($pay['bank_name']) ?></strong></div><?php endif; ?>
                  <?php if ($pay['bank_branch']): ?><div class="bank-details__row"><span>Branch</span><strong><?= htmlspecialchars($pay['bank_branch']) ?></strong></div><?php endif; ?>
                  <?php if ($pay['bank_account_name']): ?><div class="bank-details__row"><span>Account Name</span><strong><?= htmlspecialchars($pay['bank_account_name']) ?></strong></div><?php endif; ?>
                  <?php if ($pay['bank_account_no']): ?><div class="bank-details__row"><span>Account No.</span><strong class="bank-details__acc"><?= htmlspecialchars($pay['bank_account_no']) ?></strong></div><?php endif; ?>
                  <?php if ($pay['bank_swift']): ?><div class="bank-details__row"><span>SWIFT Code</span><strong><?= htmlspecialchars($pay['bank_swift']) ?></strong></div><?php endif; ?>
                </div>
                <div class="bank-details__note">
                  <i class="fab fa-whatsapp" style="color:#25d366;"></i>
                  <?php
                  $waNote = 'After transferring, please send the payment slip via WhatsApp';
                  if ($pay['whatsapp_number']) $waNote .= ' (' . htmlspecialchars($pay['whatsapp_number']) . ')';
                  $waNote .= '. Your order will be dispatched once payment is confirmed.';
                  echo $waNote;
                  ?>
                </div>
              </div>
            </div>

            <div class="pay-panel" id="panelFriMi" style="display:none;">
              <div class="mobile-pay-panel">
                <div class="mobile-pay-panel__icon" style="background:#5b2d8e;"><i class="fas fa-mobile-alt"></i></div>
                <div class="mobile-pay-panel__info">
                  <div class="mobile-pay-panel__label">Send payment to FriMi number</div>
                  <div class="mobile-pay-panel__number"><?= htmlspecialchars($pay['frimi_number']) ?></div>
                  <div class="mobile-pay-panel__name"><?= htmlspecialchars($pay['frimi_name']) ?></div>
                </div>
              </div>
              <div class="bank-details__note" style="margin-top:12px;"><i class="fab fa-whatsapp" style="color:#25d366;"></i> <?php $waSuffix = $pay['whatsapp_number'] ? ' (' . htmlspecialchars($pay['whatsapp_number']) . ')' : ''; echo 'Send the payment screenshot via WhatsApp' . $waSuffix . ' after completing your transfer.'; ?></div>
            </div>

            <div class="pay-panel" id="panelEzCash" style="display:none;">
              <div class="mobile-pay-panel">
                <div class="mobile-pay-panel__icon" style="background:#e31e24;"><i class="fas fa-mobile-alt"></i></div>
                <div class="mobile-pay-panel__info">
                  <div class="mobile-pay-panel__label">Send payment to eZ Cash number</div>
                  <div class="mobile-pay-panel__number"><?= htmlspecialchars($pay['ezcash_number']) ?></div>
                  <div class="mobile-pay-panel__name"><?= htmlspecialchars($pay['ezcash_name']) ?></div>
                </div>
              </div>
              <div class="bank-details__note" style="margin-top:12px;"><i class="fab fa-whatsapp" style="color:#25d366;"></i> <?php $waSuffix = $pay['whatsapp_number'] ? ' (' . htmlspecialchars($pay['whatsapp_number']) . ')' : ''; echo 'Send the payment screenshot via WhatsApp' . $waSuffix . ' after completing your transfer.'; ?></div>
            </div>

            <div class="card-preview" id="cardPreview" style="display:none;">
              <div class="card-preview__chip"></div>
              <div class="card-preview__number" id="previewNumber">•••• &nbsp;•••• &nbsp;•••• &nbsp;••••</div>
              <div class="card-preview__bottom">
                <div><div class="card-preview__label">Card Holder</div><div class="card-preview__value" id="previewName">FULL NAME</div></div>
                <div><div class="card-preview__label">Expires</div><div class="card-preview__value" id="previewExpiry">MM / YY</div></div>
                <div class="card-preview__logo"><i class="fab fa-cc-visa" id="previewBrand"></i></div>
              </div>
            </div>

            <form class="co-form" id="paymentForm" novalidate style="display:none;">
              <div class="co-field">
                <label for="co-cardName">Name on Card</label>
                <div class="co-field__wrap"><i class="fas fa-user co-field__icon"></i><input type="text" id="co-cardName" placeholder="John Smith" autocomplete="cc-name" /></div>
              </div>
              <div class="co-field">
                <label for="co-cardNum">Card Number</label>
                <div class="co-field__wrap"><i class="fas fa-credit-card co-field__icon"></i><input type="text" id="co-cardNum" placeholder="1234 5678 9012 3456" autocomplete="cc-number" maxlength="19" inputmode="numeric" /></div>
              </div>
              <div class="co-card-row">
                <div class="co-field">
                  <label for="co-cardExpiry">Expiry Date</label>
                  <div class="co-field__wrap"><i class="far fa-calendar co-field__icon"></i><input type="text" id="co-cardExpiry" placeholder="MM / YY" autocomplete="cc-exp" maxlength="7" /></div>
                </div>
                <div class="co-field">
                  <label for="co-cardCvc">CVC / CVV</label>
                  <div class="co-field__wrap"><i class="fas fa-lock co-field__icon"></i><input type="text" id="co-cardCvc" placeholder="•••" maxlength="4" inputmode="numeric" autocomplete="cc-csc" /></div>
                </div>
                <div class="co-field">
                  <label>&nbsp;</label>
                  <div style="display:flex;align-items:center;gap:6px;padding:11px 0;">
                    <i class="fab fa-cc-visa" style="font-size:24px;color:#1a1f71;"></i>
                    <i class="fab fa-cc-mastercard" style="font-size:24px;color:#eb001b;"></i>
                    <i class="fab fa-cc-amex" style="font-size:24px;color:#007bc1;"></i>
                  </div>
                </div>
              </div>
            </form>

            <div class="pay-panel" id="panelPaypal" style="display:none;">
              <div class="mobile-pay-panel">
                <div class="mobile-pay-panel__icon" style="background:#003087;"><i class="fab fa-paypal" style="font-size:20px;"></i></div>
                <div class="mobile-pay-panel__info">
                  <div class="mobile-pay-panel__label">Send PayPal payment to</div>
                  <div class="mobile-pay-panel__number">payments@mirabelaceylon.com</div>
                  <div class="mobile-pay-panel__name">Mirabella Ceylon (Pvt) Ltd</div>
                </div>
              </div>
              <div class="bank-details__note" style="margin-top:12px;"><i class="fab fa-whatsapp" style="color:#25d366;"></i> Send the PayPal confirmation via WhatsApp once payment is completed.</div>
            </div>

            <div class="secure-badge" style="flex-direction:column;align-items:flex-start;gap:6px;margin-top:16px;">
              <div style="display:flex;align-items:center;gap:8px;">
                <i class="fab fa-whatsapp" style="color:#25d366;font-size:16px;"></i>
                <strong style="color:var(--text);font-size:12px;">Orders are confirmed via WhatsApp</strong>
              </div>
              <span style="font-size:12px;color:var(--text-soft);line-height:1.5;">
                After placing your order, WhatsApp will open with your order details pre-filled. Our team will confirm and arrange payment before dispatch.
              </span>
            </div>

          </div>
        </div>

      </div>


      <!-- RIGHT: Order Summary -->
      <aside class="checkout-summary">
        <div class="checkout-summary__head">
          <span class="checkout-summary__title">Order Summary</span>
          <div class="currency-toggle" id="currencyToggle" aria-label="Select currency">
            <button class="currency-btn active" data-currency="USD" type="button">USD</button>
            <button class="currency-btn" data-currency="LKR" type="button">LKR</button>
          </div>
        </div>

        <div class="checkout-summary__items">
          <?php foreach ($items as $item): ?>
          <div class="co-item">
            <div class="co-item__img">
              <?php if ($item['image_main']): ?>
              <img src="<?= htmlspecialchars($item['image_main']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
              <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8f6f0;color:#ccc;font-size:18px;"><i class="fas fa-gem"></i></div>
              <?php endif; ?>
              <span class="co-item__qty-badge"><?= $item['quantity'] ?></span>
            </div>
            <span class="co-item__name">
              <?= htmlspecialchars($item['name']) ?>
              <?php if ($item['weight_ct'] || $item['cut']): ?>
              <br /><small style="font-weight:400;color:var(--text-soft);">
                <?= $item['weight_ct'] ? $item['weight_ct'] . ' ct' : '' ?>
                <?= $item['cut'] ? ' · ' . htmlspecialchars($item['cut']) : '' ?>
              </small>
              <?php endif; ?>
            </span>
            <span class="co-item__price" data-usd="<?= $item['price_usd'] * $item['quantity'] ?>">
              $<?= number_format($item['price_usd'] * $item['quantity'], 0) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="checkout-summary__totals">
          <div class="co-total-row">
            <span>Subtotal</span>
            <span data-usd="<?= $subtotal ?>">$<?= number_format($subtotal, 0) ?></span>
          </div>
          <div class="co-total-row">
            <span>Shipping</span>
            <span class="co-total-free" id="summaryShipping">Free</span>
          </div>
        </div>

        <div class="checkout-summary__grand">
          <span>Total</span>
          <span data-usd="<?= $subtotal ?>">$<?= number_format($subtotal, 0) ?></span>
        </div>
        <div class="lkr-rate-note" id="lkrRateNote" style="display:none;">
          <i class="fas fa-info-circle"></i> 1 USD ≈ Rs. 320 &nbsp;·&nbsp; indicative rate only
        </div>

        <div class="co-place-order-btn">
          <button class="btn btn-primary" id="placeOrderBtn" type="button" style="background:#25d366;border-color:#25d366;">
            <i class="fab fa-whatsapp" style="font-size:16px;"></i> &nbsp;Place Order via WhatsApp
          </button>
        </div>

        <div class="checkout-summary__secure">
          <i class="fab fa-whatsapp" style="color:#25d366;"></i>
          Order confirmed via WhatsApp · GIA Certified
        </div>
      </aside>

    </div>
  </div>
</section>

<?php
$extraJS = ['assets/js/checkout.js'];
include 'includes/footer.php';
?>
