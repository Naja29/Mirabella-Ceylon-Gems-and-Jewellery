<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/site_settings.php';

$orderNumber = trim($_GET['order'] ?? '');

if (!$orderNumber) {
    header('Location: index.php');
    exit;
}

$db = db();
$stOrder = $db->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
$stOrder->execute([$orderNumber]);
$order = $stOrder->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Load order items
$stItems = $db->prepare("
    SELECT oi.*, p.image_main, p.weight_ct, p.cut, c.name AS category_name
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$stItems->execute([$order['id']]);
$orderItems = $stItems->fetchAll();

$payLabels = [
    'bank_transfer' => 'Bank Transfer',
    'frimi'         => 'FriMi',
    'ezcash'        => 'eZ Cash',
    'card'          => 'Credit Card',
    'paypal'        => 'PayPal',
];
$payLabel = $payLabels[$order['payment_method']] ?? ucfirst($order['payment_method'] ?? 'Bank Transfer');

$placedDate = date('d M Y', strtotime($order['created_at']));

$pageTitle   = 'Order Confirmed | Mirabella Ceylon';
$pageDesc    = 'Your order has been placed successfully.';
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
      <a href="cart.php">Cart</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <a href="checkout.php">Checkout</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <span>Confirmation</span>
    </nav>
  </div>
</div>


<!-- CONFIRMATION SECTION -->
<section class="confirm-section">
  <div class="container">

    <!-- Step Indicator -->
    <div class="checkout-steps" role="list" style="margin-bottom:48px;">
      <div class="checkout-step done" role="listitem">
        <div class="checkout-step__num"><i class="fas fa-check" style="font-size:10px;"></i></div>
        <span class="checkout-step__label">CART</span>
      </div>
      <div class="checkout-step__line done"></div>
      <div class="checkout-step done" role="listitem">
        <div class="checkout-step__num"><i class="fas fa-check" style="font-size:10px;"></i></div>
        <span class="checkout-step__label">CHECKOUT</span>
      </div>
      <div class="checkout-step__line done"></div>
      <div class="checkout-step active" role="listitem">
        <div class="checkout-step__num">3</div>
        <span class="checkout-step__label">CONFIRMATION</span>
      </div>
    </div>

    <!-- Success Hero -->
    <div class="confirm-hero">
      <div class="confirm-hero__check"><i class="fas fa-check"></i></div>
      <div class="confirm-hero__eyebrow">Order Confirmed</div>
      <h1 class="confirm-hero__title">Thank you for your order!</h1>
      <p class="confirm-hero__sub">
        Your certified gemstones are being carefully prepared for shipment.
        A confirmation will be sent to <strong><?= htmlspecialchars($order['customer_email']) ?></strong>.
      </p>
      <div class="confirm-hero__order-num">
        <i class="fas fa-receipt"></i>
        Order Number: <span><?= htmlspecialchars($order['order_number']) ?></span>
      </div>
    </div>

    <!-- Main Layout -->
    <div class="confirm-layout">

      <!-- LEFT: Details -->
      <div>

        <!-- Payment Instructions -->
        <?php if (in_array($order['payment_method'], ['bank_transfer','frimi','ezcash','paypal'])): ?>
        <div class="confirm-card" style="border-left:3px solid var(--gold);">
          <div class="confirm-card__head">
            <i class="fas fa-exclamation-circle" style="color:var(--gold);"></i>
            Action Required — Complete Your Payment
          </div>
          <div class="confirm-card__body">
            <?php if ($order['payment_method'] === 'bank_transfer'): ?>
            <div class="bank-details">
              <div class="bank-details__header"><i class="fas fa-university"></i><span>Bank Transfer Details</span></div>
              <div class="bank-details__rows">
                <div class="bank-details__row"><span>Bank</span><strong>Bank of Ceylon</strong></div>
                <div class="bank-details__row"><span>Account Name</span><strong>Mirabella Ceylon (Pvt) Ltd</strong></div>
                <div class="bank-details__row"><span>Account No.</span><strong class="bank-details__acc">0072 1234 5678</strong></div>
                <div class="bank-details__row"><span>SWIFT Code</span><strong>BCEYLKLX</strong></div>
                <div class="bank-details__row"><span>Amount</span><strong>$<?= number_format($order['total_usd'], 2) ?></strong></div>
                <div class="bank-details__row"><span>Reference</span><strong style="color:var(--gold-dark);"><?= htmlspecialchars($order['order_number']) ?></strong></div>
              </div>
              <div class="bank-details__note">
                <i class="fab fa-whatsapp" style="color:#25d366;"></i>
                Please use your order number <strong><?= htmlspecialchars($order['order_number']) ?></strong> as the payment reference, then send your payment slip via WhatsApp.
              </div>
            </div>
            <?php elseif (in_array($order['payment_method'], ['frimi','ezcash'])): ?>
            <div class="bank-details__note">
              <i class="fab fa-whatsapp" style="color:#25d366;"></i>
              Please send your payment screenshot via WhatsApp with reference <strong><?= htmlspecialchars($order['order_number']) ?></strong>. Your order will be dispatched once payment is confirmed.
            </div>
            <?php elseif ($order['payment_method'] === 'paypal'): ?>
            <div class="bank-details__note">
              <i class="fab fa-whatsapp" style="color:#25d366;"></i>
              Please send your PayPal confirmation via WhatsApp with reference <strong><?= htmlspecialchars($order['order_number']) ?></strong>.
            </div>
            <?php endif; ?>
            <div style="margin-top:16px;">
              <?php $waNum = preg_replace('/[^0-9]/', '', get_site_setting('whatsapp_number', '94718456999')); ?>
              <a href="https://wa.me/<?= $waNum ?>?text=<?= urlencode('Hi, I just placed order ' . $order['order_number'] . ' and I am sending the payment confirmation.') ?>"
                 target="_blank" class="btn btn-primary"
                 style="background:#25d366;border-color:#25d366;display:inline-flex;align-items:center;gap:8px;padding:12px 24px;">
                <i class="fab fa-whatsapp" style="font-size:16px;"></i> Send Payment Confirmation
              </a>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Items Ordered -->
        <div class="confirm-card">
          <div class="confirm-card__head"><i class="fas fa-gem"></i> Items Ordered</div>
          <div class="confirm-card__body">
            <div class="confirm-items">
              <?php foreach ($orderItems as $item): ?>
              <div class="confirm-item">
                <div class="confirm-item__img">
                  <?php if ($item['image_main']): ?>
                  <img src="<?= htmlspecialchars($item['image_main']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" />
                  <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8f6f0;color:#ccc;font-size:22px;"><i class="fas fa-gem"></i></div>
                  <?php endif; ?>
                </div>
                <div>
                  <?php if ($item['category_name']): ?>
                  <div class="confirm-item__cat"><?= htmlspecialchars($item['category_name']) ?></div>
                  <?php endif; ?>
                  <div class="confirm-item__name"><?= htmlspecialchars($item['product_name']) ?></div>
                  <div class="confirm-item__specs">
                    <?= $item['weight_ct'] ? $item['weight_ct'] . ' ct' : '' ?>
                    <?= $item['cut'] ? ' &nbsp;·&nbsp; ' . htmlspecialchars($item['cut']) : '' ?>
                    &nbsp;·&nbsp; Qty: <?= $item['quantity'] ?>
                  </div>
                </div>
                <div class="confirm-item__price">$<?= number_format($item['line_total'], 0) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Shipping & Payment -->
        <div class="confirm-card">
          <div class="confirm-card__head"><i class="fas fa-map-marker-alt"></i> Shipping &amp; Payment Details</div>
          <div class="confirm-card__body">
            <div class="confirm-info-grid">
              <div>
                <div class="confirm-info__label">Ship To</div>
                <div class="confirm-info__val">
                  <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                  <?= htmlspecialchars($order['ship_address'] ?? '') ?><br />
                  <?= htmlspecialchars($order['ship_city'] ?? '') ?>
                  <?= $order['ship_state'] ? ', ' . htmlspecialchars($order['ship_state']) : '' ?>
                  <?= $order['ship_zip']   ? ' ' . htmlspecialchars($order['ship_zip']) : '' ?><br />
                  <?= htmlspecialchars($order['ship_country'] ?? '') ?>
                </div>
              </div>
              <div>
                <div class="confirm-info__label">Contact</div>
                <div class="confirm-info__val">
                  <strong><?= htmlspecialchars($order['customer_email']) ?></strong>
                  <?= $order['customer_phone'] ? '<br />' . htmlspecialchars($order['customer_phone']) : '' ?>
                </div>
              </div>
              <div>
                <div class="confirm-info__label">Payment Method</div>
                <div class="confirm-info__val">
                  <strong><?= htmlspecialchars($payLabel) ?></strong><br />
                  <span style="color:var(--gold-dark);font-size:12px;font-weight:700;">Awaiting Payment Confirmation</span>
                </div>
              </div>
              <div>
                <div class="confirm-info__label">Order Date</div>
                <div class="confirm-info__val">
                  <strong><?= $placedDate ?></strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Delivery Timeline -->
        <div class="confirm-card">
          <div class="confirm-card__head"><i class="fas fa-route"></i> Delivery Timeline</div>
          <div class="confirm-card__body">
            <div class="confirm-timeline">
              <div class="confirm-timeline__item done">
                <div class="confirm-timeline__dot"><i class="fas fa-check"></i></div>
                <div class="confirm-timeline__text">
                  <strong>Order Placed</strong>
                  <span><?= $placedDate ?> &nbsp;·&nbsp; Pending payment</span>
                </div>
              </div>
              <div class="confirm-timeline__item active">
                <div class="confirm-timeline__dot"><i class="fas fa-money-bill-wave"></i></div>
                <div class="confirm-timeline__text">
                  <strong>Awaiting Payment</strong>
                  <span>Send your payment confirmation via WhatsApp</span>
                </div>
              </div>
              <div class="confirm-timeline__item">
                <div class="confirm-timeline__dot"><i class="fas fa-box"></i></div>
                <div class="confirm-timeline__text">
                  <strong>Preparing Your Order</strong>
                  <span>Gemstones inspected, certified &amp; packaged</span>
                </div>
              </div>
              <div class="confirm-timeline__item">
                <div class="confirm-timeline__dot"><i class="fas fa-shipping-fast"></i></div>
                <div class="confirm-timeline__text">
                  <strong>Dispatched</strong>
                  <span>Tracked &amp; fully insured</span>
                </div>
              </div>
              <div class="confirm-timeline__item">
                <div class="confirm-timeline__dot"><i class="fas fa-home"></i></div>
                <div class="confirm-timeline__text">
                  <strong>Delivered</strong>
                  <span>Estimated delivery time varies by location</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>


      <!-- RIGHT: Sidebar -->
      <aside class="confirm-sidebar">

        <div class="confirm-summary">
          <div class="confirm-summary__head">Order Summary</div>
          <div class="confirm-summary__rows">
            <div class="confirm-summary__row">
              <span>Subtotal (<?= count($orderItems) ?> item<?= count($orderItems) != 1 ? 's' : '' ?>)</span>
              <span>$<?= number_format($order['subtotal_usd'], 2) ?></span>
            </div>
            <div class="confirm-summary__row">
              <span>Shipping</span>
              <?php if ($order['shipping_usd'] > 0): ?>
              <span>$<?= number_format($order['shipping_usd'], 2) ?></span>
              <?php else: ?>
              <span style="color:var(--gold-dark);">Free</span>
              <?php endif; ?>
            </div>
            <div class="confirm-summary__row total">
              <span>Total</span>
              <span>$<?= number_format($order['total_usd'], 2) ?></span>
            </div>
          </div>
        </div>

        <div class="confirm-actions">
          <?php if (customer_logged_in()): ?>
          <a href="account.php?tab=orders" class="btn btn-primary">
            <i class="fas fa-box-open"></i> &nbsp;View My Orders
          </a>
          <?php endif; ?>
          <a href="shop.php" class="btn btn-outline">
            <i class="fas fa-gem"></i> &nbsp;Continue Shopping
          </a>
          <button class="btn btn-outline" type="button" onclick="window.print()">
            <i class="fas fa-print"></i> &nbsp;Print Receipt
          </button>
        </div>

        <div style="margin-top:20px;padding:18px;background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);">
          <div style="font-family:var(--font-display);font-size:15px;color:var(--text);margin-bottom:10px;">
            <i class="fas fa-headset" style="color:var(--gold);margin-right:8px;"></i> Need Help?
          </div>
          <p style="font-size:13px;color:var(--text-soft);line-height:1.7;margin-bottom:12px;">
            Our gemstone specialists are available 7 days a week.
          </p>
          <a href="contact.php" class="btn btn-outline" style="width:100%;justify-content:center;height:40px;font-size:11px;">
            Contact Support
          </a>
        </div>

      </aside>

    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
