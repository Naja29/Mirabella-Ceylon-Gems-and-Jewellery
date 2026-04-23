<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/cart.php';

$items     = cart_items();
$count     = array_sum(array_column($items, 'quantity'));
$subtotal  = cart_subtotal($items);
$hasItems  = !empty($items);

// Load a few related products (not in cart)
$cartIds   = array_column($items, 'product_id') ?: [0];
$placeholders = implode(',', array_fill(0, count($cartIds), '?'));
$db        = db();
$stRel     = $db->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1 AND p.stock > 0 AND p.id NOT IN ($placeholders)
    ORDER BY p.is_featured DESC, RAND()
    LIMIT 4
");
$stRel->execute($cartIds);
$related = $stRel->fetchAll();

$pageTitle   = 'Shopping Cart | Mirabella Ceylon';
$pageDesc    = 'Review your selected gemstones and proceed to checkout.';
$headerClass = 'is-solid';
$extraCSS    = ['assets/css/cart.css'];
include 'includes/header.php';
?>

<!-- BREADCRUMB -->
<div class="cart-breadcrumb-bar">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <a href="shop.php">Collections</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <span>Shopping Cart</span>
    </nav>
  </div>
</div>


<!-- CART SECTION -->
<section class="cart-section">
  <div class="container">

    <div class="cart-page-header">
      <h1 class="cart-page-title">Shopping Cart</h1>
      <span class="cart-page-count" id="cartItemCount"><?= $count ?> <?= $count === 1 ? 'item' : 'items' ?></span>
    </div>

    <!-- CART LAYOUT -->
    <div class="cart-layout" id="cartLayout" <?= !$hasItems ? 'style="display:none;"' : '' ?>>

      <!-- LEFT: Items -->
      <div class="cart-items" id="cartItems">

        <?php foreach ($items as $item): ?>
        <?php $pid = $item['product_id']; $price = (int)round($item['price_usd']); ?>
        <div class="cart-item" data-id="<?= $pid ?>" data-price="<?= $price ?>">
          <a href="product.php?slug=<?= htmlspecialchars($item['slug']) ?>" class="cart-item__img">
            <?php if ($item['image_main']): ?>
            <img src="<?= htmlspecialchars($item['image_main']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
            <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:32px;background:#f8f6f0;">
              <i class="fas fa-gem"></i>
            </div>
            <?php endif; ?>
          </a>
          <div class="cart-item__body">
            <div class="cart-item__top">
              <div>
                <?php if ($item['category_name']): ?>
                <div class="cart-item__cat"><?= htmlspecialchars($item['category_name']) ?></div>
                <?php endif; ?>
                <a href="product.php?slug=<?= htmlspecialchars($item['slug']) ?>" class="cart-item__name">
                  <?= htmlspecialchars($item['name']) ?>
                </a>
                <div class="cart-item__specs">
                  <?php if ($item['weight_ct']): ?>
                  <span><i class="fas fa-gem"></i> <?= $item['weight_ct'] ?> ct</span>
                  <?php endif; ?>
                  <?php if ($item['cut']): ?>
                  <span><i class="fas fa-cut"></i> <?= htmlspecialchars($item['cut']) ?></span>
                  <?php endif; ?>
                  <?php if ($item['certification']): ?>
                  <span><i class="fas fa-certificate"></i> <?= htmlspecialchars($item['certification']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <button class="cart-item__remove" data-remove="<?= $pid ?>" aria-label="Remove item">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="cart-item__bottom">
              <div class="cart-item__qty">
                <button class="cart-qty__btn" data-action="minus" data-id="<?= $pid ?>" aria-label="Decrease"><i class="fas fa-minus"></i></button>
                <span class="cart-qty__val" id="qty-<?= $pid ?>"><?= $item['quantity'] ?></span>
                <button class="cart-qty__btn" data-action="plus"  data-id="<?= $pid ?>" aria-label="Increase"><i class="fas fa-plus"></i></button>
              </div>
              <div class="cart-item__price" id="price-<?= $pid ?>">$<?= number_format($item['price_usd'] * $item['quantity'], 0) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="cart-continue">
          <a href="shop.php" class="cart-continue__link">
            <i class="fas fa-arrow-left"></i> Continue Shopping
          </a>
        </div>

      </div>


      <!-- RIGHT: Summary -->
      <aside class="cart-summary" id="cartSummary">

        <h2 class="cart-summary__title">Order Summary</h2>

        <!-- Promo code -->
        <div class="cart-promo">
          <label class="cart-promo__label" for="promoCode">Promo Code</label>
          <div class="cart-promo__row">
            <input type="text" id="promoCode" class="cart-promo__input" placeholder="Enter code…" />
            <button class="cart-promo__btn" id="applyPromo">Apply</button>
          </div>
          <p class="cart-promo__msg" id="promoMsg"></p>
        </div>

        <!-- Totals -->
        <div class="cart-totals">
          <div class="cart-total-row">
            <span>Subtotal (<span id="summaryCount"><?= $count ?></span> items)</span>
            <span id="subtotalVal">$<?= number_format($subtotal, 0) ?></span>
          </div>
          <div class="cart-total-row" id="discountRow" style="display:none;">
            <span class="cart-total-row--discount">Discount</span>
            <span class="cart-total-row--discount" id="discountVal">−$0</span>
          </div>
          <div class="cart-total-row">
            <span>Shipping</span>
            <span class="cart-total-free">Free</span>
          </div>
          <div class="cart-total-row">
            <span>Estimated Tax</span>
            <span id="taxVal">$0</span>
          </div>
        </div>

        <div class="cart-grand-total">
          <span>Total</span>
          <span id="grandTotalVal">$<?= number_format($subtotal, 0) ?></span>
        </div>

        <a href="checkout.php" class="btn btn--gold cart-checkout-btn" id="checkoutBtn">
          <i class="fas fa-lock"></i> Proceed to Checkout
        </a>

        <div class="cart-payment-icons">
          <i class="fab fa-cc-visa" title="Visa"></i>
          <i class="fab fa-cc-mastercard" title="Mastercard"></i>
          <i class="fab fa-cc-paypal" title="PayPal"></i>
          <i class="fab fa-cc-amex" title="Amex"></i>
        </div>

        <div class="cart-summary-trust">
          <div class="cart-trust-item"><i class="fas fa-shield-alt"></i><span>Secure Checkout</span></div>
          <div class="cart-trust-item"><i class="fas fa-undo-alt"></i><span>14-Day Returns</span></div>
          <div class="cart-trust-item"><i class="fas fa-globe"></i><span>Free Worldwide Shipping</span></div>
          <div class="cart-trust-item"><i class="fas fa-certificate"></i><span>Certified Gemstones</span></div>
        </div>

      </aside>

    </div>


    <!-- EMPTY STATE -->
    <div class="cart-empty" id="cartEmpty" <?= $hasItems ? 'style="display:none;"' : '' ?>>
      <div class="cart-empty__icon"><i class="fas fa-shopping-bag"></i></div>
      <h2 class="cart-empty__title">Your cart is empty</h2>
      <p class="cart-empty__text">You haven't added any gemstones yet. Explore our collections and find something extraordinary.</p>
      <a href="shop.php" class="btn btn--gold">
        <i class="fas fa-gem"></i> Explore Collections
      </a>
    </div>

  </div>
</section>


<?php if (!empty($related)): ?>
<!-- YOU MAY ALSO LIKE -->
<section class="cart-related">
  <div class="container">
    <div class="section-header reveal">
      <div class="eyebrow">Handpicked for You</div>
      <h2 class="section-title">You May Also Like</h2>
    </div>
    <div class="products__grid cart-related__grid reveal">
      <?php foreach ($related as $p): ?>
      <article class="product-card" data-id="<?= $p['id'] ?>" data-price="<?= (int)round($p['price_usd']) ?>">
        <div class="product-card__img">
          <?php if ($p['image_main']): ?>
          <img src="<?= htmlspecialchars($p['image_main']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" />
          <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:40px;background:#f8f6f0;aspect-ratio:1;"><i class="fas fa-gem"></i></div>
          <?php endif; ?>
          <?php if ($p['is_featured']): ?><span class="product-badge product-badge--gold">Featured</span><?php endif; ?>
          <div class="product-card__overlay">
            <button class="btn-quickview"><i class="fas fa-eye"></i> Quick View</button>
          </div>
        </div>
        <div class="product-card__info">
          <?php if ($p['category_name']): ?>
          <div class="product-card__cat"><?= htmlspecialchars($p['category_name']) ?></div>
          <?php endif; ?>
          <h3 class="product-card__name"><?= htmlspecialchars($p['name']) ?></h3>
          <div class="product-card__footer">
            <div class="product-card__price">$<?= number_format($p['price_usd'], 0) ?></div>
            <button class="btn-cart" data-action="add-to-cart" data-id="<?= $p['id'] ?>">
              <i class="fas fa-shopping-bag"></i> Add
            </button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
$extraJS = ['assets/js/cart.js'];
include 'includes/footer.php';
?>
