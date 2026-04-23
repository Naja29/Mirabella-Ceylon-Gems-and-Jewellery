<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/cart.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: shop.php');
    exit;
}

$db = db();

// Load product 
$st = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.slug = ? AND p.is_active = 1
    LIMIT 1
");
$st->execute([$slug]);
$p = $st->fetch();

if (!$p) {
    header('Location: shop.php');
    exit;
}

// Gallery images 
$galleryRows = $db->prepare("SELECT image_path, alt_text FROM product_images WHERE product_id = ? ORDER BY sort_order");
$galleryRows->execute([$p['id']]);
$gallery = $galleryRows->fetchAll();

// Build full image list: main + gallery
$allImages = [];
if ($p['image_main']) $allImages[] = ['src' => $p['image_main'], 'alt' => $p['name']];
foreach ($gallery as $g)  $allImages[] = ['src' => $g['image_path'], 'alt' => $g['alt_text'] ?: $p['name']];

// Reviews 
$revSt = $db->prepare("
    SELECT r.*, c.first_name, c.last_name
    FROM reviews r
    LEFT JOIN customers c ON c.id = r.customer_id
    WHERE r.product_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC
");
$revSt->execute([$p['id']]);
$reviews    = $revSt->fetchAll();
$reviewCount = count($reviews);
$avgRating  = $reviewCount
    ? round(array_sum(array_column($reviews, 'rating')) / $reviewCount, 1)
    : 0;

// Rating distribution
$dist = array_fill(1, 5, 0);
foreach ($reviews as $r) {
    if ($r['rating'] >= 1 && $r['rating'] <= 5) $dist[(int)$r['rating']]++;
}

// Related products 
$relSt = $db->prepare("
    SELECT p2.id, p2.name, p2.slug, p2.price_usd, p2.image_main, p2.weight_ct, p2.stock,
           c.name AS category_name
    FROM products p2
    LEFT JOIN categories c ON c.id = p2.category_id
    WHERE p2.category_id = ? AND p2.id != ? AND p2.is_active = 1 AND p2.stock > 0
    ORDER BY p2.is_featured DESC, p2.created_at DESC
    LIMIT 4
");
$relSt->execute([$p['category_id'], $p['id']]);
$related = $relSt->fetchAll();

// Check if customer already reviewed 
$alreadyReviewed = false;
if (customer_logged_in()) {
    $chk = $db->prepare("SELECT id FROM reviews WHERE product_id = ? AND customer_id = ? LIMIT 1");
    $chk->execute([$p['id'], $_SESSION['customer_id']]);
    $alreadyReviewed = (bool)$chk->fetchColumn();
}

// Helpers 
function stars(float $r): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($r >= $i)        $out .= '<i class="fas fa-star"></i>';
        elseif ($r >= $i-.5) $out .= '<i class="fas fa-star-half-alt"></i>';
        else                 $out .= '<i class="far fa-star"></i>';
    }
    return $out;
}

// Page meta 
$weightLabel = $p['weight_ct'] ? ' — ' . rtrim(rtrim(number_format((float)$p['weight_ct'], 2), '0'), '.') . 'ct' : '';
$pageTitle   = htmlspecialchars($p['name'] . $weightLabel) . ' | Mirabella Ceylon';
$pageDesc    = $p['short_desc'] ?: 'Certified ' . $p['name'] . ' from Mirabella Ceylon. Ethically sourced from Sri Lanka. Ships worldwide.';
$activePage  = 'shop';
$extraCSS    = ['assets/css/product-detail.css'];
$extraJS     = ['assets/js/product-detail.js'];
include 'includes/header.php';
?>

<!-- BREADCRUMB BAR -->
<div class="pd-breadcrumb-bar">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <a href="shop.php">Collections</a>
      <?php if ($p['category_name']): ?>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <a href="shop.php?cat=<?= urlencode($p['category_slug'] ?? '') ?>"><?= htmlspecialchars($p['category_name']) ?></a>
      <?php endif; ?>
      <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
      <span><?= htmlspecialchars($p['name']) ?></span>
    </nav>
  </div>
</div>


<!-- PRODUCT DETAIL — MAIN -->
<section class="pd-section">
  <div class="container pd-grid">

    <!-- LEFT: Gallery -->
    <div class="pd-gallery">
      <div class="pd-gallery__main" id="pdMainImage">
        <?php $mainSrc = $allImages[0]['src'] ?? ''; ?>
        <?php if ($mainSrc): ?>
        <img src="<?= htmlspecialchars($mainSrc) ?>"
             alt="<?= htmlspecialchars($p['name']) ?>" id="pdMainImg" />
        <?php else: ?>
        <div id="pdMainImg" style="width:100%;height:100%;min-height:400px;display:flex;align-items:center;justify-content:center;background:#f8f6f0;color:#ccc;font-size:80px;">
          <i class="fas fa-gem"></i>
        </div>
        <?php endif; ?>

        <?php if ($p['certification']): ?>
        <span class="pd-badge pd-badge--cert"><?= htmlspecialchars($p['certification']) ?> Certified</span>
        <?php endif; ?>

        <?php if ($mainSrc): ?>
        <button class="pd-zoom-btn" id="pdZoomBtn" aria-label="Zoom image">
          <i class="fas fa-expand-alt"></i>
        </button>
        <?php endif; ?>
      </div>

      <?php if (count($allImages) > 1): ?>
      <div class="pd-gallery__thumbs" id="pdThumbs">
        <?php foreach ($allImages as $idx => $img): ?>
        <button class="pd-thumb <?= $idx === 0 ? 'active' : '' ?>"
                data-src="<?= htmlspecialchars($img['src']) ?>"
                aria-label="View image <?= $idx + 1 ?>">
          <img src="<?= htmlspecialchars($img['src']) ?>"
               alt="<?= htmlspecialchars($img['alt']) ?>" />
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Info -->
    <div class="pd-info">

      <div class="pd-info__top">
        <?php if ($p['category_name']): ?>
        <div class="pd-category"><?= htmlspecialchars($p['category_name']) ?></div>
        <?php endif; ?>
        <button class="pd-wishlist js-pd-wishlist" id="pdWishlist"
                data-id="<?= $p['id'] ?>" aria-label="Add to wishlist">
          <i class="far fa-heart"></i>
        </button>
      </div>

      <h1 class="pd-title">
        <?= htmlspecialchars($p['name']) ?><?= $p['weight_ct'] ? ' — ' . rtrim(rtrim(number_format((float)$p['weight_ct'], 2), '0'), '.') . 'ct' : '' ?>
      </h1>

      <div class="pd-rating">
        <?php if ($reviewCount > 0): ?>
        <div class="stars"><?= stars($avgRating) ?></div>
        <span class="pd-rating__count">(<?= $reviewCount ?> review<?= $reviewCount != 1 ? 's' : '' ?>)</span>
        <a href="#pd-reviews" class="pd-rating__link">Read reviews</a>
        <?php else: ?>
        <span class="pd-rating__count" style="color:var(--text-soft);">No reviews yet</span>
        <?php endif; ?>
        <?php if (customer_logged_in() && !$alreadyReviewed): ?>
        <a href="#pd-reviews" class="pd-rating__link pd-write-review-link" id="openReviewTab"
           style="margin-left:auto;color:var(--gold);font-weight:600;">
          <i class="fas fa-pen" style="font-size:11px;"></i> Write a Review
        </a>
        <?php elseif (!customer_logged_in()): ?>
        <a href="login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="pd-rating__link"
           style="margin-left:auto;color:var(--gold);">
          <i class="fas fa-sign-in-alt" style="font-size:11px;"></i> Sign in to Review
        </a>
        <?php elseif ($alreadyReviewed): ?>
        <span style="margin-left:auto;font-size:12px;color:#2ecc71;">
          <i class="fas fa-check-circle"></i> You reviewed this
        </span>
        <?php endif; ?>
      </div>

      <div class="pd-price-row">
        <div class="pd-price">$<?= number_format((float)$p['price_usd'], 0) ?></div>
        <?php if ($p['compare_price']): ?>
        <div class="pd-price-compare">$<?= number_format((float)$p['compare_price'], 0) ?></div>
        <?php endif; ?>
        <div class="pd-price-note">
          <?= (float)$p['price_usd'] >= 500 ? 'Free worldwide shipping' : 'Worldwide shipping available' ?>
        </div>
      </div>

      <?php if ($p['short_desc'] || $p['description']): ?>
      <p class="pd-desc"><?= nl2br(htmlspecialchars($p['short_desc'] ?: substr($p['description'] ?? '', 0, 300))) ?></p>
      <?php endif; ?>

      <!-- Spec Pills -->
      <div class="pd-specs">
        <?php if ($p['weight_ct']): ?>
        <div class="pd-spec">
          <i class="fas fa-gem"></i>
          <span class="pd-spec__label">Weight</span>
          <span class="pd-spec__val"><?= rtrim(rtrim(number_format((float)$p['weight_ct'], 2), '0'), '.') ?> ct</span>
        </div>
        <?php endif; ?>
        <?php if ($p['dimensions']): ?>
        <div class="pd-spec">
          <i class="fas fa-ruler-combined"></i>
          <span class="pd-spec__label">Dimensions</span>
          <span class="pd-spec__val"><?= htmlspecialchars($p['dimensions']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($p['colour']): ?>
        <div class="pd-spec">
          <i class="fas fa-palette"></i>
          <span class="pd-spec__label">Color</span>
          <span class="pd-spec__val"><?= htmlspecialchars($p['colour']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($p['cut']): ?>
        <div class="pd-spec">
          <i class="fas fa-cut"></i>
          <span class="pd-spec__label">Cut</span>
          <span class="pd-spec__val"><?= htmlspecialchars($p['cut']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($p['treatment']): ?>
        <div class="pd-spec">
          <i class="fas fa-fire-alt"></i>
          <span class="pd-spec__label">Treatment</span>
          <span class="pd-spec__val"><?= htmlspecialchars($p['treatment']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($p['origin']): ?>
        <div class="pd-spec">
          <i class="fas fa-map-marker-alt"></i>
          <span class="pd-spec__label">Origin</span>
          <span class="pd-spec__val"><?= htmlspecialchars($p['origin']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($p['clarity']): ?>
        <div class="pd-spec">
          <i class="fas fa-search"></i>
          <span class="pd-spec__label">Clarity</span>
          <span class="pd-spec__val"><?= htmlspecialchars($p['clarity']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Trust badges -->
      <div class="pd-trust">
        <?php if ($p['certification']): ?>
        <div class="pd-trust__item">
          <i class="fas fa-certificate"></i>
          <span><?= htmlspecialchars($p['certification']) ?> Certified</span>
        </div>
        <?php endif; ?>
        <div class="pd-trust__item">
          <i class="fas fa-globe"></i>
          <span>Ships Worldwide</span>
        </div>
        <div class="pd-trust__item">
          <i class="fas fa-shield-alt"></i>
          <span>Authenticity Guarantee</span>
        </div>
        <div class="pd-trust__item">
          <i class="fas fa-undo-alt"></i>
          <span>14-Day Returns</span>
        </div>
      </div>

      <!-- Actions -->
      <?php if ($p['stock'] > 0): ?>
      <div class="pd-actions">
        <div class="pd-qty">
          <button class="pd-qty__btn" id="pdQtyMinus" aria-label="Decrease quantity"><i class="fas fa-minus"></i></button>
          <input class="pd-qty__input" id="pdQtyInput" type="number"
                 value="1" min="1" max="<?= min($p['stock'], 10) ?>" readonly />
          <button class="pd-qty__btn" id="pdQtyPlus" aria-label="Increase quantity"><i class="fas fa-plus"></i></button>
        </div>
        <button class="btn btn--gold pd-add-cart" id="pdAddCart"
                data-action="add-to-cart" data-id="<?= $p['id'] ?>">
          <i class="fas fa-shopping-bag"></i> Add to Cart
        </button>
        <button class="pd-enquire" id="pdEnquire"
                data-name="<?= htmlspecialchars($p['name'] . $weightLabel) ?>"
                data-sku="<?= htmlspecialchars($p['sku'] ?? '') ?>">
          <i class="fab fa-whatsapp"></i> Enquire
        </button>
      </div>
      <?php else: ?>
      <div class="pd-actions">
        <button class="btn btn--gold pd-add-cart" disabled style="opacity:.5;cursor:not-allowed;">
          <i class="fas fa-times-circle"></i> Out of Stock
        </button>
        <button class="pd-enquire" id="pdEnquire"
                data-name="<?= htmlspecialchars($p['name'] . $weightLabel) ?>"
                data-sku="<?= htmlspecialchars($p['sku'] ?? '') ?>">
          <i class="fab fa-whatsapp"></i> Enquire
        </button>
      </div>
      <?php endif; ?>

      <!-- Share -->
      <div class="pd-share">
        <span class="pd-share__label">Share:</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
           target="_blank" rel="noopener" class="pd-share__btn" aria-label="Share on Facebook">
          <i class="fab fa-facebook-f"></i>
        </a>
        <a href="https://wa.me/?text=<?= urlencode($p['name'] . $weightLabel . ' — ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
           target="_blank" rel="noopener" class="pd-share__btn" aria-label="Share on WhatsApp">
          <i class="fab fa-whatsapp"></i>
        </a>
        <button class="pd-share__btn" id="pdCopyLink" aria-label="Copy link" title="Copy link">
          <i class="fas fa-link"></i>
        </button>
      </div>

    </div>

  </div>
</section>


<!-- TABS -->
<section class="pd-tabs-section">
  <div class="container">

    <div class="pd-tabs" role="tablist">
      <button class="pd-tab active" role="tab" data-tab="details"     aria-selected="true">Details</button>
      <?php if ($p['certification']): ?>
      <button class="pd-tab"        role="tab" data-tab="certificate" aria-selected="false">Certificate</button>
      <?php endif; ?>
      <button class="pd-tab"        role="tab" data-tab="shipping"    aria-selected="false">Shipping &amp; Returns</button>
      <button class="pd-tab"        role="tab" data-tab="reviews"     aria-selected="false" id="pd-reviews">
        Reviews<?= $reviewCount ? ' (' . $reviewCount . ')' : '' ?>
      </button>
    </div>

    <!-- Details tab -->
    <div class="pd-tab-panel active" id="tab-details">
      <div class="pd-details-grid">
        <div>
          <h3 class="pd-details__heading">Gemstone Details</h3>
          <table class="pd-table">
            <?php if ($p['gemstone_type'] || $p['category_name']): ?>
            <tr><th>Type</th><td><?= htmlspecialchars($p['gemstone_type'] ?: $p['category_name']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['weight_ct']): ?>
            <tr><th>Carat Weight</th><td><?= rtrim(rtrim(number_format((float)$p['weight_ct'], 3), '0'), '.') ?> ct</td></tr>
            <?php endif; ?>
            <?php if ($p['dimensions']): ?>
            <tr><th>Dimensions</th><td><?= htmlspecialchars($p['dimensions']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['cut']): ?>
            <tr><th>Shape &amp; Cut</th><td><?= htmlspecialchars($p['cut']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['colour']): ?>
            <tr><th>Color</th><td><?= htmlspecialchars($p['colour']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['clarity']): ?>
            <tr><th>Clarity</th><td><?= htmlspecialchars($p['clarity']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['treatment']): ?>
            <tr><th>Treatment</th><td><?= htmlspecialchars($p['treatment']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['origin']): ?>
            <tr><th>Geographic Origin</th><td><?= htmlspecialchars($p['origin']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['certification']): ?>
            <tr><th>Certification</th><td><?= htmlspecialchars($p['certification']) ?></td></tr>
            <?php endif; ?>
            <?php if ($p['sku']): ?>
            <tr><th>SKU</th><td><?= htmlspecialchars($p['sku']) ?></td></tr>
            <?php endif; ?>
          </table>
        </div>
        <div>
          <?php if ($p['description']): ?>
          <h3 class="pd-details__heading">About This Stone</h3>
          <?php foreach (array_filter(explode("\n\n", $p['description'])) as $para): ?>
          <p class="pd-details__text"><?= nl2br(htmlspecialchars(trim($para))) ?></p>
          <?php endforeach; ?>
          <?php else: ?>
          <h3 class="pd-details__heading">About Mirabella Ceylon</h3>
          <p class="pd-details__text">Every gemstone in our collection is ethically sourced from the gem fields of Sri Lanka — the Gem Capital of the World. Our stones are individually selected for exceptional colour, clarity, and cut, and are accompanied by internationally recognised certificates of authenticity.</p>
          <?php endif; ?>
          <h3 class="pd-details__heading" style="margin-top:28px;">Ideal For</h3>
          <ul class="pd-details__list">
            <li><i class="fas fa-check"></i> Bespoke &amp; heirloom jewellery</li>
            <li><i class="fas fa-check"></i> Investment-grade gem collections</li>
            <li><i class="fas fa-check"></i> Gifts for special occasions</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Certificate tab -->
    <?php if ($p['certification']): ?>
    <div class="pd-tab-panel" id="tab-certificate">
      <div class="pd-cert-wrap">
        <div class="pd-cert-card">
          <div class="pd-cert-card__logo">
            <i class="fas fa-certificate"></i>
            <span><?= htmlspecialchars($p['certification']) ?></span>
          </div>
          <div class="pd-cert-card__body">
            <div class="pd-cert-card__title"><?= htmlspecialchars($p['certification']) ?> Certification</div>
            <p class="pd-cert-card__text">This gemstone has been independently examined and certified by <?= htmlspecialchars($p['certification']) ?>. The certificate confirms natural origin and all listed specifications.</p>
            <div class="pd-cert-fields">
              <?php if ($p['cut']): ?>
              <div class="pd-cert-field"><span>Shape &amp; Cut</span><strong><?= htmlspecialchars($p['cut']) ?></strong></div>
              <?php endif; ?>
              <?php if ($p['weight_ct']): ?>
              <div class="pd-cert-field"><span>Weight</span><strong><?= rtrim(rtrim(number_format((float)$p['weight_ct'], 3), '0'), '.') ?> ct</strong></div>
              <?php endif; ?>
              <?php if ($p['treatment']): ?>
              <div class="pd-cert-field"><span>Treatment</span><strong><?= htmlspecialchars($p['treatment']) ?></strong></div>
              <?php endif; ?>
              <?php if ($p['origin']): ?>
              <div class="pd-cert-field"><span>Origin</span><strong><?= htmlspecialchars($p['origin']) ?></strong></div>
              <?php endif; ?>
            </div>
            <p style="margin-top:16px;font-size:13px;color:var(--text-soft);">
              <i class="fab fa-whatsapp" style="color:#25d366;"></i>
              Contact us via WhatsApp to request a copy of the certificate for this stone.
            </p>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Shipping tab -->
    <div class="pd-tab-panel" id="tab-shipping">
      <div class="pd-shipping-grid">
        <div class="pd-ship-card">
          <div class="pd-ship-card__icon"><i class="fas fa-globe"></i></div>
          <h4 class="pd-ship-card__title">Free Worldwide Shipping</h4>
          <p class="pd-ship-card__text">All orders over $500 qualify for complimentary express shipping to any destination worldwide. Fully insured and tracked door-to-door.</p>
        </div>
        <div class="pd-ship-card">
          <div class="pd-ship-card__icon"><i class="fas fa-box-open"></i></div>
          <h4 class="pd-ship-card__title">Luxury Packaging</h4>
          <p class="pd-ship-card__text">Every gemstone is presented in a premium Mirabella Ceylon gift box with a velvet cushion and authentication card.</p>
        </div>
        <div class="pd-ship-card">
          <div class="pd-ship-card__icon"><i class="fas fa-clock"></i></div>
          <h4 class="pd-ship-card__title">Delivery Times</h4>
          <p class="pd-ship-card__text">Express: 3–5 business days.<br />Standard: 7–12 business days.<br />Dispatched within 1–2 business days of payment confirmation.</p>
        </div>
        <div class="pd-ship-card">
          <div class="pd-ship-card__icon"><i class="fas fa-undo-alt"></i></div>
          <h4 class="pd-ship-card__title">14-Day Returns</h4>
          <p class="pd-ship-card__text">Return your gem within 14 days in its original condition for a full refund. No questions asked.</p>
        </div>
      </div>
    </div>

    <!-- Reviews tab -->
    <div class="pd-tab-panel" id="tab-reviews">
      <div class="pd-reviews-wrap">

        <?php if ($reviewCount > 0): ?>
        <div class="pd-reviews-summary">
          <div class="pd-reviews-avg">
            <div class="pd-reviews-avg__score"><?= number_format($avgRating, 1) ?></div>
            <div class="stars"><?= stars($avgRating) ?></div>
            <div class="pd-reviews-avg__count">Based on <?= $reviewCount ?> review<?= $reviewCount != 1 ? 's' : '' ?></div>
          </div>
          <div class="pd-reviews-bars">
            <?php for ($star = 5; $star >= 1; $star--):
              $cnt = $dist[$star];
              $pct = $reviewCount > 0 ? round($cnt / $reviewCount * 100) : 0;
            ?>
            <div class="pd-review-bar">
              <span><?= $star ?></span>
              <div class="pd-review-bar__track"><div class="pd-review-bar__fill" style="width:<?= $pct ?>%"></div></div>
              <span><?= $cnt ?></span>
            </div>
            <?php endfor; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Review list -->
        <?php if ($reviews): ?>
        <div class="pd-review-list">
          <?php foreach ($reviews as $rev):
            $name = $rev['first_name'] ? $rev['first_name'] . ' ' . substr($rev['last_name'], 0, 1) . '.' : $rev['reviewer_name'];
            $initials = strtoupper(substr($rev['reviewer_name'], 0, 1) . (strpos($rev['reviewer_name'], ' ') !== false ? substr(strrchr($rev['reviewer_name'], ' '), 1, 1) : ''));
            $date = date('F Y', strtotime($rev['created_at']));
          ?>
          <div class="pd-review">
            <div class="pd-review__header">
              <div class="pd-review__avatar"><?= htmlspecialchars($initials ?: 'MC') ?></div>
              <div>
                <div class="pd-review__author"><?= htmlspecialchars($name) ?></div>
                <div class="pd-review__meta">Verified Buyer &nbsp;·&nbsp; <?= $date ?></div>
              </div>
              <div class="stars pd-review__stars"><?= stars((float)$rev['rating']) ?></div>
            </div>
            <?php if ($rev['title']): ?>
            <div class="pd-review__title"><?= htmlspecialchars($rev['title']) ?></div>
            <?php endif; ?>
            <p class="pd-review__text"><?= nl2br(htmlspecialchars($rev['body'] ?? '')) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
        <?php elseif (!customer_logged_in()): ?>
        <p style="color:var(--text-soft);text-align:center;padding:32px 0;">
          No reviews yet. <a href="login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Sign in</a> to be the first to review this product.
        </p>
        <?php endif; ?>

        <!-- Write a review -->
        <?php if (customer_logged_in() && !$alreadyReviewed): ?>
        <div class="pd-review-form" id="reviewFormWrap" style="margin-top:32px;padding-top:28px;border-top:1px solid var(--border-light);">
          <h3 class="pd-details__heading" style="margin-bottom:20px;">
            <i class="fas fa-pen" style="color:var(--gold);margin-right:8px;font-size:14px;"></i>Write a Review
          </h3>
          <form id="reviewForm" novalidate>
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>" />

            <div style="margin-bottom:16px;">
              <label style="font-size:13px;font-weight:700;display:block;margin-bottom:8px;">Your Rating</label>
              <div class="pd-star-select" id="starSelect">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="pd-star-btn" data-val="<?= $i ?>" aria-label="<?= $i ?> star<?= $i>1?'s':'' ?>">
                  <i class="far fa-star"></i>
                </button>
                <?php endfor; ?>
              </div>
              <input type="hidden" id="ratingInput" name="rating" value="5" />
            </div>

            <div style="margin-bottom:14px;">
              <label for="reviewTitle" style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">Title</label>
              <input type="text" id="reviewTitle" name="title"
                     placeholder="Summarise your experience"
                     style="width:100%;padding:10px 14px;border:1px solid var(--border-light);border-radius:var(--radius-sm);font-size:14px;" />
            </div>

            <div style="margin-bottom:14px;">
              <label for="reviewBody" style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">Your Review</label>
              <textarea id="reviewBody" name="body" rows="4" required
                        placeholder="Tell us about the quality, colour, and your experience..."
                        style="width:100%;padding:10px 14px;border:1px solid var(--border-light);border-radius:var(--radius-sm);font-size:14px;resize:vertical;"></textarea>
            </div>

            <div id="reviewMsg" style="display:none;margin-bottom:12px;font-size:13px;"></div>

            <button type="submit" class="btn btn--gold" id="reviewSubmit">
              <i class="fas fa-paper-plane"></i> Submit Review
            </button>
          </form>
        </div>
        <?php elseif (customer_logged_in() && $alreadyReviewed): ?>
        <p style="color:var(--text-soft);text-align:center;padding:16px 0;font-size:14px;">
          <i class="fas fa-check-circle" style="color:#2ecc71;"></i> You have already reviewed this product. Thank you!
        </p>
        <?php elseif (!customer_logged_in()): ?>
        <div style="text-align:center;padding:24px 0;border-top:1px solid var(--border-light);margin-top:24px;">
          <p style="font-size:14px;color:var(--text-soft);margin-bottom:12px;">Share your experience with this gemstone.</p>
          <a href="login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn--gold">
            <i class="fas fa-sign-in-alt"></i> Sign in to Write a Review
          </a>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</section>


<!-- RELATED PRODUCTS -->
<?php if ($related): ?>
<section class="pd-related">
  <div class="container">
    <div class="section-header reveal">
      <div class="eyebrow">You May Also Like</div>
      <h2 class="section-title">Related Gemstones</h2>
    </div>
    <div class="products__grid pd-related__grid reveal">

      <?php foreach ($related as $rel):
        $relWeight = $rel['weight_ct'] ? ' — ' . rtrim(rtrim(number_format((float)$rel['weight_ct'], 2), '0'), '.') . 'ct' : '';
      ?>
      <article class="product-card">
        <div class="product-card__img">
          <?php if ($rel['image_main']): ?>
          <img src="<?= htmlspecialchars($rel['image_main']) ?>"
               alt="<?= htmlspecialchars($rel['name']) ?>" loading="lazy" />
          <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8f6f0;color:#ccc;font-size:40px;">
            <i class="fas fa-gem"></i>
          </div>
          <?php endif; ?>
          <button class="product-card__wishlist js-wishlist-btn" data-id="<?= $rel['id'] ?>" aria-label="Wishlist">
            <i class="far fa-heart"></i>
          </button>
          <div class="product-card__overlay">
            <a href="product-detail.php?slug=<?= urlencode($rel['slug']) ?>" class="btn-quickview">
              <i class="fas fa-eye"></i> View Details
            </a>
          </div>
        </div>
        <div class="product-card__info">
          <?php if ($rel['category_name']): ?>
          <div class="product-card__cat"><?= htmlspecialchars($rel['category_name']) ?></div>
          <?php endif; ?>
          <h3 class="product-card__name">
            <a href="product-detail.php?slug=<?= urlencode($rel['slug']) ?>" style="color:inherit;text-decoration:none;">
              <?= htmlspecialchars($rel['name'] . $relWeight) ?>
            </a>
          </h3>
          <div class="product-card__footer">
            <div class="product-card__price">$<?= number_format((float)$rel['price_usd'], 0) ?></div>
            <button class="btn-cart" data-action="add-to-cart" data-id="<?= $rel['id'] ?>">
              <i class="fas fa-shopping-bag"></i> Add
            </button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>

    </div>
    <div style="text-align:center;margin-top:40px;">
      <a href="shop.php<?= $p['category_slug'] ? '?cat=' . urlencode($p['category_slug']) : '' ?>"
         class="btn btn--gold">
        View All <?= htmlspecialchars($p['category_name'] ?? 'Collections') ?> <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Data for JS -->
<script>
  window.PD = {
    productId:   <?= $p['id'] ?>,
    productName: <?= json_encode($p['name'] . $weightLabel) ?>,
    productSku:  <?= json_encode($p['sku'] ?? '') ?>,
    waNumber:    '<?= preg_replace('/[^0-9]/', '', get_site_setting('whatsapp_number', '94718456999')) ?>',
    pageUrl:     <?= json_encode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>,
  };
</script>

<?php include 'includes/footer.php'; ?>
