<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';

require_customer_login('login.php');

$customerId = (int)$_SESSION['customer_id'];
$db = db();

// Ensure wishlists table exists
$db->exec("CREATE TABLE IF NOT EXISTS `wishlists` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `product_id`  INT UNSIGNED NOT NULL,
  `added_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wish` (`customer_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Load wishlist items
$st = $db->prepare("
    SELECT w.product_id, w.added_at,
           p.name, p.slug, p.price_usd, p.compare_price, p.image_main,
           p.weight_ct, p.certification, p.stock, p.is_featured,
           c.name AS category_name
    FROM wishlists w
    JOIN products p ON p.id = w.product_id AND p.is_active = 1
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE w.customer_id = ?
    ORDER BY w.added_at DESC
");
$st->execute([$customerId]);
$items = $st->fetchAll();

$pageTitle  = 'My Wishlist | Mirabella Ceylon';
$pageDesc   = 'Your saved gemstones and jewellery at Mirabella Ceylon.';
$activePage = 'shop';
$extraCSS   = ['assets/css/account.css'];
$extraJS    = ['assets/js/wishlist-page.js'];
include 'includes/header.php';
?>

<!-- BREADCRUMB -->
<div class="account-breadcrumb-bar">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <a href="account.php">My Account</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <span>My Wishlist</span>
    </nav>
  </div>
</div>


<!-- PAGE HEADER -->
<div class="account-page-hero">
  <div class="container account-page-hero__inner">
    <div>
      <div class="eyebrow" style="justify-content:flex-start;">Your Account</div>
      <h1 class="account-page-hero__title">My Wishlist</h1>
      <p class="account-page-hero__sub">Gems you've saved — ready whenever you are.</p>
    </div>
    <div class="account-page-hero__icon" aria-hidden="true"><i class="fas fa-heart"></i></div>
  </div>
</div>


<!-- WISHLIST CONTENT -->
<section class="account-section">
  <div class="container">

    <?php if ($items): ?>

    <!-- Toolbar -->
    <div class="wishlist-toolbar" id="wishlistToolbar">
      <p class="wishlist-toolbar__count">
        <strong id="wishlistCount"><?= count($items) ?></strong>
        saved item<?= count($items) != 1 ? 's' : '' ?>
      </p>
      <div class="wishlist-toolbar__actions">
        <button class="btn btn--outline-gold btn--sm" id="addAllToCart">
          <i class="fas fa-shopping-bag"></i> Add All to Cart
        </button>
        <button class="wishlist-clear-btn" id="clearWishlist">
          <i class="fas fa-times"></i> Clear All
        </button>
      </div>
    </div>

    <!-- Grid -->
    <div class="products__grid wishlist-grid" id="wishlistGrid">

      <?php foreach ($items as $item):
        $price   = number_format((float)$item['price_usd'], 0);
        $compare = $item['compare_price'] ? number_format((float)$item['compare_price'], 0) : null;
        $weight  = $item['weight_ct'] ? rtrim(rtrim(number_format((float)$item['weight_ct'], 2), '0'), '.') . 'ct' : null;
        $badge   = null;
        if ($item['is_featured'])      $badge = ['label' => 'Bestseller', 'class' => 'product-badge--gold'];
        if ($item['compare_price'])    $badge = ['label' => 'Sale',       'class' => 'product-badge--gold'];
        if ($item['stock'] == 0)       $badge = ['label' => 'Sold Out',   'class' => ''];
      ?>
      <article class="product-card wishlist-card"
               data-id="<?= $item['product_id'] ?>"
               data-price="<?= (int)$item['price_usd'] ?>">

        <div class="product-card__img">
          <?php if ($item['image_main']): ?>
          <img src="<?= htmlspecialchars($item['image_main']) ?>"
               alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy" />
          <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8f6f0;color:#ccc;font-size:40px;">
            <i class="fas fa-gem"></i>
          </div>
          <?php endif; ?>

          <?php if ($badge): ?>
          <span class="product-badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
          <?php endif; ?>

          <?php if ($item['certification']): ?>
          <span class="product-badge product-badge--outline" style="top:auto;bottom:10px;left:10px;right:auto;">
            <?= htmlspecialchars($item['certification']) ?>
          </span>
          <?php endif; ?>

          <button class="product-card__wishlist active js-wl-remove"
                  data-id="<?= $item['product_id'] ?>"
                  aria-label="Remove from wishlist">
            <i class="fas fa-heart"></i>
          </button>

          <div class="product-card__overlay">
            <a href="product-detail.php?slug=<?= urlencode($item['slug']) ?>"
               class="btn-quickview">
              <i class="fas fa-eye"></i> View Details
            </a>
          </div>
        </div>

        <div class="product-card__info">
          <?php if ($item['category_name']): ?>
          <div class="product-card__cat"><?= htmlspecialchars($item['category_name']) ?></div>
          <?php endif; ?>

          <h3 class="product-card__name">
            <a href="product-detail.php?slug=<?= urlencode($item['slug']) ?>"
               style="color:inherit;text-decoration:none;">
              <?= htmlspecialchars($item['name']) ?>
              <?= $weight ? ' — ' . $weight : '' ?>
            </a>
          </h3>

          <div class="product-card__footer">
            <div class="product-card__price">
              $<?= $price ?>
              <?php if ($compare): ?>
              <span class="price-old">$<?= $compare ?></span>
              <?php endif; ?>
            </div>
            <?php if ($item['stock'] > 0): ?>
            <button class="btn-cart js-wl-add-cart"
                    data-action="add-to-cart"
                    data-id="<?= $item['product_id'] ?>">
              <i class="fas fa-shopping-bag"></i> Add
            </button>
            <?php else: ?>
            <a href="product-detail.php?slug=<?= urlencode($item['slug']) ?>"
               class="btn-cart" style="background:var(--text-soft);border-color:var(--text-soft);">View</a>
            <?php endif; ?>
          </div>
        </div>

      </article>
      <?php endforeach; ?>

    </div>

    <?php else: ?>

    <!-- Empty state -->
    <div class="account-empty" id="wishlistEmpty">
      <div class="account-empty__icon"><i class="far fa-heart"></i></div>
      <h2 class="account-empty__title">Your wishlist is empty</h2>
      <p class="account-empty__text">Browse our collections and tap the <i class="fas fa-heart" style="color:var(--gold-dark);"></i> icon on any gemstone to save it here.</p>
      <a href="shop.php" class="btn btn--gold"><i class="fas fa-gem"></i> Explore Collections</a>
    </div>

    <?php endif; ?>

    <!-- Dynamic empty state (shown by JS when last item removed) -->
    <div class="account-empty" id="wishlistEmptyDynamic" style="display:none;">
      <div class="account-empty__icon"><i class="far fa-heart"></i></div>
      <h2 class="account-empty__title">Your wishlist is empty</h2>
      <p class="account-empty__text">Browse our collections and save gems you love.</p>
      <a href="shop.php" class="btn btn--gold"><i class="fas fa-gem"></i> Explore Collections</a>
    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
