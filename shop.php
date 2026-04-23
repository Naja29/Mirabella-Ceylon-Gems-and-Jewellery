<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';

// Filters from URL 
$catSlug   = trim($_GET['cat']    ?? '');
$sortBy    = trim($_GET['sort']   ?? 'newest');
$priceMin  = max(0, (int)($_GET['min']  ?? 0));
$priceMax  = min(500000, (int)($_GET['max'] ?? 50000));
$certIn    = $_GET['cert']   ?? [];
$weightIn  = $_GET['weight'] ?? [];
if (!is_array($certIn))   $certIn   = [$certIn];
if (!is_array($weightIn)) $weightIn = [$weightIn];
$certs   = array_values(array_filter(array_map('trim', $certIn)));
$weights = array_values(array_filter($weightIn));
$inStock = ($_GET['stock'] ?? '0') === '1';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$db = db();

// Categories for tabs 
$categories = $db->query(
    "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order"
)->fetchAll();

$activeCatId = null;
foreach ($categories as $c) {
    if ($c['slug'] === $catSlug) { $activeCatId = $c['id']; break; }
}

// Build WHERE 
$where  = ['p.is_active = 1'];
$params = [];

if ($activeCatId) {
    $where[]  = 'p.category_id = ?';
    $params[] = $activeCatId;
}
if ($priceMin > 0) {
    $where[]  = 'p.price_usd >= ?';
    $params[] = $priceMin;
}
if ($priceMax < 50000) {
    $where[]  = 'p.price_usd <= ?';
    $params[] = $priceMax;
}
if ($inStock) {
    $where[] = 'p.stock > 0';
}
if ($weights && !in_array('all', $weights)) {
    $wc = [];
    foreach ($weights as $w) {
        if ($w === 'under1') $wc[] = 'p.weight_ct < 1';
        if ($w === '1to3')   $wc[] = '(p.weight_ct >= 1 AND p.weight_ct <= 3)';
        if ($w === '3to5')   $wc[] = '(p.weight_ct > 3 AND p.weight_ct <= 5)';
        if ($w === 'above5') $wc[] = 'p.weight_ct > 5';
    }
    if ($wc) $where[] = '(' . implode(' OR ', $wc) . ')';
}
if ($certs) {
    $cc = [];
    foreach ($certs as $cert) {
        $cc[] = 'p.certification LIKE ?';
        $params[] = '%' . $cert . '%';
    }
    $where[] = '(' . implode(' OR ', $cc) . ')';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderSQL = match($sortBy) {
    'price-asc'  => 'ORDER BY p.price_usd ASC',
    'price-desc' => 'ORDER BY p.price_usd DESC',
    'bestseller' => 'ORDER BY p.is_featured DESC, p.created_at DESC',
    default      => 'ORDER BY p.is_featured DESC, p.created_at DESC',
};

// Count & paginate 
$countSt = $db->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$countSt->execute($params);
$total      = (int)$countSt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$st = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $whereSQL
    $orderSQL
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$products = $st->fetchAll();

// Cert counts 
$certCountRows = $db->query(
    "SELECT certification, COUNT(*) AS cnt FROM products WHERE is_active = 1 AND stock > 0
     AND certification IS NOT NULL AND certification != '' GROUP BY certification"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// URL builder helper 
function shopUrl(array $override = [], array $remove = []): string {
    $p = $_GET;
    foreach ($override as $k => $v) $p[$k] = $v;
    foreach ($remove  as $k)        unset($p[$k]);
    unset($p['page']);
    $qs = http_build_query($p, '', '&', PHP_QUERY_RFC3986);
    return 'shop.php' . ($qs ? '?' . $qs : '');
}

function pageUrl(int $pg): string {
    $p = $_GET;
    $p['page'] = $pg;
    $qs = http_build_query($p, '', '&', PHP_QUERY_RFC3986);
    return 'shop.php?' . $qs;
}

// Star renderer 
function renderStars(float $rating): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i)         $out .= '<i class="fas fa-star"></i>';
        elseif ($rating >= $i-.5)  $out .= '<i class="fas fa-star-half-alt"></i>';
        else                       $out .= '<i class="far fa-star"></i>';
    }
    return $out;
}

// Page meta 
$pageTitle  = 'Collections | Mirabella Ceylon';
$pageDesc   = 'Browse certified Ceylon sapphires, rubies, cat\'s eye gems and handcrafted jewellery. Delivered worldwide.';
$activePage = 'shop';
$extraCSS   = ['assets/css/shop.css'];
$extraJS    = ['assets/js/shop.js'];
include 'includes/header.php';
?>

<!-- PAGE HERO BANNER -->
<section class="page-hero">
  <div class="page-hero__bg" aria-hidden="true"></div>
  <div class="container page-hero__inner">
    <div class="page-hero__content">
      <div class="eyebrow" style="justify-content:flex-start;">Discover &amp; Collect</div>
      <h1 class="page-hero__title">Our Collections</h1>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <span class="breadcrumb__sep"><i class="fas fa-chevron-right"></i></span>
        <span>Collections</span>
      </nav>
    </div>
    <div class="page-hero__gem" aria-hidden="true">
      <i class="fas fa-gem"></i>
    </div>
  </div>
</section>


<!-- CATEGORY TABS -->
<div class="category-strip">
  <div class="container">
    <div class="category-strip__inner">

      <a href="<?= shopUrl(['cat' => ''], ['cat']) ?>"
         class="cat-tab <?= !$catSlug ? 'active' : '' ?>">
        <i class="fas fa-border-all"></i> All
      </a>

      <?php foreach ($categories as $cat):
        $icons = [
          'blue-sapphire'   => 'fas fa-gem',
          'padparadscha'    => 'fas fa-gem',
          'cats-eye'        => 'fas fa-eye',
          'ruby'            => 'fas fa-gem',
          'alexandrite'     => 'fas fa-gem',
          'spinel'          => 'fas fa-gem',
          'jewellery'       => 'fas fa-ring',
          'loose-gemstones' => 'fas fa-gem',
        ];
        $icon = $icons[$cat['slug']] ?? 'fas fa-gem';
      ?>
      <a href="<?= shopUrl(['cat' => $cat['slug']]) ?>"
         class="cat-tab <?= $catSlug === $cat['slug'] ? 'active' : '' ?>">
        <i class="<?= $icon ?>"></i> <?= htmlspecialchars($cat['name']) ?>
      </a>
      <?php endforeach; ?>

    </div>
  </div>
</div>


<!-- SHOP LAYOUT -->
<div class="container shop-container">
  <div class="shop-layout">

    <!-- SIDEBAR -->
    <aside class="shop-sidebar" id="shopSidebar">

      <div class="sidebar-header">
        <h3 class="sidebar-title"><i class="fas fa-sliders-h"></i> Filters</h3>
        <button class="sidebar-close" id="sidebarClose" aria-label="Close filters">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="GET" action="shop.php" id="filterForm">
        <?php if ($catSlug): ?>
        <input type="hidden" name="cat" value="<?= htmlspecialchars($catSlug) ?>" />
        <?php endif; ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>" />

        <!-- Price Range -->
        <div class="filter-group">
          <h4 class="filter-group__title">Price Range</h4>
          <div class="price-inputs">
            <div class="price-input-wrap">
              <span class="price-input-prefix">$</span>
              <input type="number" id="priceMin" name="min" class="price-input"
                     value="<?= $priceMin ?>" min="0" max="50000" placeholder="Min" />
            </div>
            <span class="price-dash">—</span>
            <div class="price-input-wrap">
              <span class="price-input-prefix">$</span>
              <input type="number" id="priceMax" name="max" class="price-input"
                     value="<?= $priceMax >= 50000 ? 10000 : $priceMax ?>" min="0" max="50000" placeholder="Max" />
            </div>
          </div>
          <div class="price-range-track">
            <div class="price-range-fill" id="priceRangeFill"></div>
          </div>
          <div class="price-presets">
            <button type="button" class="price-preset <?= ($priceMin === 0 && $priceMax >= 50000) ? 'active' : '' ?>"
                    data-min="0" data-max="50000">All</button>
            <button type="button" class="price-preset <?= ($priceMax == 2000 && $priceMin == 0) ? 'active' : '' ?>"
                    data-min="0" data-max="2000">Under $2k</button>
            <button type="button" class="price-preset <?= ($priceMin == 2000 && $priceMax == 5000) ? 'active' : '' ?>"
                    data-min="2000" data-max="5000">$2k – $5k</button>
            <button type="button" class="price-preset <?= ($priceMin == 5000) ? 'active' : '' ?>"
                    data-min="5000" data-max="50000">Above $5k</button>
          </div>
        </div>

        <!-- Carat Weight -->
        <div class="filter-group">
          <h4 class="filter-group__title">Carat Weight</h4>
          <div class="filter-checks">
            <label class="filter-check">
              <input type="checkbox" name="weight[]" value="all"
                     <?= empty($weights) || in_array('all', $weights) ? 'checked' : '' ?> />
              <span class="filter-check__box"></span>
              <span class="filter-check__label">All Weights</span>
            </label>
            <?php
            $weightOpts = [
              'under1' => 'Under 1 ct',
              '1to3'   => '1 – 3 ct',
              '3to5'   => '3 – 5 ct',
              'above5' => 'Above 5 ct',
            ];
            foreach ($weightOpts as $wVal => $wLabel):
            ?>
            <label class="filter-check">
              <input type="checkbox" name="weight[]" value="<?= $wVal ?>"
                     <?= in_array($wVal, $weights) ? 'checked' : '' ?> />
              <span class="filter-check__box"></span>
              <span class="filter-check__label"><?= $wLabel ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Certification -->
        <div class="filter-group">
          <h4 class="filter-group__title">Certification</h4>
          <div class="filter-checks">
            <?php
            $certOpts = [
              'GIA'   => 'GIA Certified',
              'GRS'   => 'GRS Certified',
              'GGTL'  => 'GGTL Certified',
              'AGL'   => 'AGL Certified',
            ];
            foreach ($certOpts as $cVal => $cLabel):
              $checked = in_array(strtolower($cVal), array_map('strtolower', $certs));
            ?>
            <label class="filter-check">
              <input type="checkbox" name="cert[]" value="<?= $cVal ?>"
                     <?= $checked ? 'checked' : '' ?> />
              <span class="filter-check__box"></span>
              <span class="filter-check__label"><?= $cLabel ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Availability -->
        <div class="filter-group">
          <h4 class="filter-group__title">Availability</h4>
          <label class="filter-toggle-row">
            <span class="filter-toggle-row__label">In Stock Only</span>
            <span class="toggle-switch">
              <input type="checkbox" id="inStockOnly" name="stock" value="1"
                     <?= $inStock ? 'checked' : '' ?> />
              <span class="toggle-switch__track"></span>
            </span>
          </label>
        </div>

        <button type="submit" class="btn btn--gold btn--sm btn--full-w" style="margin-bottom:10px;">
          <i class="fas fa-check"></i> Apply Filters
        </button>
        <a href="shop.php<?= $catSlug ? '?cat=' . urlencode($catSlug) : '' ?>"
           class="btn btn--outline-gold btn--sm btn--full-w" id="clearFilters">
          <i class="fas fa-times"></i> Clear All Filters
        </a>

      </form>

    </aside>
    <!-- END SIDEBAR -->


    <!-- MAIN CONTENT -->
    <div class="shop-main">

      <!-- Toolbar -->
      <div class="shop-toolbar">
        <p class="shop-toolbar__count">
          Showing
          <strong><?= min($total, $offset + $perPage) - $offset ?></strong>
          of <strong><?= $total ?></strong> product<?= $total != 1 ? 's' : '' ?>
          <?php if ($page > 1): ?>
          <span style="font-weight:400;color:var(--text-soft);"> — page <?= $page ?> of <?= $totalPages ?></span>
          <?php endif; ?>
        </p>
        <div class="shop-toolbar__right">
          <button class="filter-toggle-btn" id="filterToggleBtn" aria-label="Toggle filters">
            <i class="fas fa-sliders-h"></i> Filters
          </button>
          <div class="sort-wrap">
            <label for="sortBy" class="sort-label">Sort:</label>
            <select id="sortBy" class="sort-select" data-base-url="<?= htmlspecialchars(shopUrl([], ['sort'])) ?>">
              <option value="newest"     <?= $sortBy === 'newest'     ? 'selected' : '' ?>>Newest</option>
              <option value="price-asc"  <?= $sortBy === 'price-asc'  ? 'selected' : '' ?>>Price: Low – High</option>
              <option value="price-desc" <?= $sortBy === 'price-desc' ? 'selected' : '' ?>>Price: High – Low</option>
              <option value="bestseller" <?= $sortBy === 'bestseller' ? 'selected' : '' ?>>Bestsellers</option>
            </select>
          </div>
          <div class="view-toggle">
            <button class="view-btn active" data-view="grid3" aria-label="3-column grid"><i class="fas fa-th"></i></button>
            <button class="view-btn" data-view="grid2" aria-label="2-column grid"><i class="fas fa-th-large"></i></button>
          </div>
        </div>
      </div>

      <!-- Active Filter Chips -->
      <?php
      $chips = [];
      if ($catSlug) {
          foreach ($categories as $c) {
              if ($c['slug'] === $catSlug) { $chips[] = ['label' => $c['name'], 'url' => shopUrl([], ['cat'])]; break; }
          }
      }
      if ($priceMin > 0 || $priceMax < 50000) {
          $label = ($priceMin > 0 && $priceMax < 50000)
              ? '$' . number_format($priceMin) . ' – $' . number_format($priceMax)
              : ($priceMin > 0 ? 'From $' . number_format($priceMin) : 'Up to $' . number_format($priceMax));
          $chips[] = ['label' => $label, 'url' => shopUrl([], ['min','max'])];
      }
      foreach ($certs as $cert) {
          $chips[] = ['label' => strtoupper($cert) . ' Cert.', 'url' => shopUrl(['cert' => array_filter($certs, fn($c) => $c !== $cert)])];
      }
      if ($inStock)  $chips[] = ['label' => 'In Stock Only', 'url' => shopUrl([], ['stock'])];
      ?>
      <?php if ($chips): ?>
      <div class="filter-chips" id="filterChips">
        <?php foreach ($chips as $chip): ?>
        <span class="filter-chip active-chip">
          <?= htmlspecialchars($chip['label']) ?>
          <a href="<?= $chip['url'] ?>" aria-label="Remove filter"><i class="fas fa-times"></i></a>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>


      <!-- Product Grid -->
      <?php if ($products): ?>
      <div class="products__grid shop-grid" id="shopGrid">

        <?php foreach ($products as $i => $p):
          $delay  = ($i % 3) + 1;
          $imgSrc = $p['image_main'] ?: '';
          $price  = number_format((float)$p['price_usd'], 0);
          $comparePrice = $p['compare_price'] ? number_format((float)$p['compare_price'], 0) : null;
          $badge  = '';
          if ($p['stock'] == 0) {
              $badge = ['label' => 'Sold Out', 'class' => 'product-badge--sold-out'];
          } else {
              if ($p['is_featured'])   $badge = ['label' => 'Bestseller',   'class' => 'product-badge--gold'];
              if ($p['compare_price']) $badge = ['label' => 'Sale',         'class' => 'product-badge--gold'];
              if (strtotime($p['created_at']) > strtotime('-30 days')) $badge = ['label' => 'New Arrival', 'class' => 'product-badge--dark'];
          }
          $weightCt = $p['weight_ct'] ? (float)$p['weight_ct'] : null;
          $certAttr = strtolower(trim($p['certification'] ?? ''));
        ?>
        <article class="product-card reveal reveal-delay-<?= $delay ?>"
                 data-cat="<?= htmlspecialchars($p['category_slug'] ?? '') ?>"
                 data-price="<?= (int)$p['price_usd'] ?>"
                 data-weight="<?= $weightCt ?>"
                 data-cert="<?= htmlspecialchars($certAttr) ?>"
                 data-instock="<?= $p['stock'] > 0 ? '1' : '0' ?>">

          <div class="product-card__img">
            <?php if ($imgSrc): ?>
            <img src="<?= htmlspecialchars($imgSrc) ?>"
                 alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" />
            <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8f6f0;color:#ccc;font-size:40px;">
              <i class="fas fa-gem"></i>
            </div>
            <?php endif; ?>

            <?php if ($badge): ?>
            <span class="product-badge <?= $badge['class'] ?>"><?= htmlspecialchars($badge['label']) ?></span>
            <?php endif; ?>

            <button class="product-card__wishlist js-wishlist-btn"
                    data-id="<?= $p['id'] ?>"
                    aria-label="Add to wishlist">
              <i class="far fa-heart"></i>
            </button>

            <div class="product-card__overlay">
              <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>"
                 class="btn-quickview">
                <i class="fas fa-eye"></i> View Details
              </a>
            </div>
          </div>

          <div class="product-card__info">
            <?php if ($p['category_name']): ?>
            <div class="product-card__cat"><?= htmlspecialchars($p['category_name']) ?></div>
            <?php endif; ?>

            <h3 class="product-card__name">
              <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>"
                 style="color:inherit;text-decoration:none;">
                <?= htmlspecialchars($p['name']) ?>
                <?= $weightCt ? ' — ' . rtrim(rtrim(number_format($weightCt, 2), '0'), '.') . 'ct' : '' ?>
              </a>
            </h3>

            <?php if ($p['certification']): ?>
            <div style="font-size:11px;color:var(--gold-dark);font-weight:700;letter-spacing:.04em;margin-bottom:4px;">
              <i class="fas fa-certificate" style="margin-right:3px;"></i><?= htmlspecialchars($p['certification']) ?> Certified
            </div>
            <?php endif; ?>

            <div class="product-card__footer">
              <div class="product-card__price">
                $<?= $price ?>
                <?php if ($comparePrice): ?>
                <span class="price-old">$<?= $comparePrice ?></span>
                <?php endif; ?>
              </div>
              <?php if ($p['stock'] > 0): ?>
              <button class="btn-cart" data-action="add-to-cart" data-id="<?= $p['id'] ?>">
                <i class="fas fa-shopping-bag"></i> Add
              </button>
              <?php else:
                $_wa = get_site_setting('social_whatsapp');
                $_waMsg = 'Hi, I\'m interested in ' . $p['name'] . ' (SKU: ' . ($p['sku'] ?? 'N/A') . '). Is it available or coming back in stock?';
                $_waUrl = $_wa ? 'https://wa.me/' . preg_replace('/\D/','',$_wa) . '?text=' . rawurlencode($_waMsg) : 'contact.php';
              ?>
              <a href="<?= htmlspecialchars($_waUrl) ?>" target="<?= $_wa ? '_blank' : '_self' ?>" rel="noopener noreferrer" class="btn-cart btn-enquire">
                <i class="fas fa-envelope"></i> Enquire
              </a>
              <?php endif; ?>
            </div>
          </div>

        </article>
        <?php endforeach; ?>

      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="Products pagination">
        <a href="<?= $page > 1 ? pageUrl($page - 1) : '#' ?>"
           class="page-btn page-btn--nav <?= $page <= 1 ? 'disabled' : '' ?>"
           aria-label="Previous page"
           <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>
          <i class="fas fa-chevron-left"></i>
        </a>

        <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        if ($start > 1): ?>
        <a href="<?= pageUrl(1) ?>" class="page-btn">1</a>
        <?php if ($start > 2): ?><span class="page-ellipsis">···</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($pg = $start; $pg <= $end; $pg++): ?>
        <a href="<?= pageUrl($pg) ?>"
           class="page-btn <?= $pg === $page ? 'active' : '' ?>"><?= $pg ?></a>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="page-ellipsis">···</span><?php endif; ?>
        <a href="<?= pageUrl($totalPages) ?>" class="page-btn"><?= $totalPages ?></a>
        <?php endif; ?>

        <a href="<?= $page < $totalPages ? pageUrl($page + 1) : '#' ?>"
           class="page-btn page-btn--nav <?= $page >= $totalPages ? 'disabled' : '' ?>"
           aria-label="Next page"
           <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>>
          <i class="fas fa-chevron-right"></i>
        </a>
      </nav>
      <?php endif; ?>

      <?php else: ?>
      <!-- No Results -->
      <div class="no-results" id="noResults">
        <div class="no-results__icon"><i class="fas fa-gem"></i></div>
        <h3 class="no-results__title">No gems found</h3>
        <p class="no-results__text">Try adjusting your filters or browse all collections.</p>
        <a href="shop.php" class="btn btn--gold">View All</a>
      </div>
      <?php endif; ?>

    </div>


  </div>
</div>

<!-- Sidebar overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php include 'includes/footer.php'; ?>
