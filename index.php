<?php
require_once __DIR__ . '/admin/includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle   = 'Mirabella Ceylon | Certified Gems & Jewellery Worldwide';
$pageDesc    = 'Discover the world\'s finest certified Ceylon sapphires, rubies and handcrafted jewellery. Delivered worldwide with elegance and trust.';
$activePage  = 'home';
$headerClass = 'is-transparent';

$db = db();

// Home page collection cards
try {
    $homeCategories = $db->query("
        SELECT name, slug, subtitle, image
        FROM categories
        WHERE show_on_home = 1 AND is_active = 1
        ORDER BY sort_order ASC
        LIMIT 6
    ")->fetchAll();
} catch (Exception $e) {
    $homeCategories = [];
}

// Approved reviews for testimonials section
try {
    $testimonials = $db->query("
        SELECT r.rating, r.title, r.body, r.reviewer_name,
               c.first_name, c.last_name
        FROM reviews r
        LEFT JOIN customers c ON c.id = r.customer_id
        WHERE r.is_approved = 1 AND r.body IS NOT NULL AND r.body != ''
        ORDER BY r.rating DESC, r.created_at DESC
        LIMIT 8
    ")->fetchAll();
} catch (Exception $e) {
    $testimonials = [];
}

// Featured products with avg rating
try {
    $featuredProducts = $db->query("
        SELECT p.id, p.name, p.slug, p.price_usd, p.compare_price,
               p.image_main, p.stock, p.is_featured, p.weight_ct, p.certification,
               c.name AS category_name,
               COALESCE(AVG(r.rating), 0) AS avg_rating,
               COUNT(r.id) AS review_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN reviews r ON r.product_id = p.id AND r.is_approved = 1
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT 6
    ")->fetchAll();
} catch (\Exception $e) {
    $featuredProducts = [];
}

// Wishlist IDs for logged-in customers
$wishlistIds = [];
if (!empty($_SESSION['customer_id'])) {
    try {
        $wst = $db->prepare("SELECT product_id FROM wishlists WHERE customer_id = ?");
        $wst->execute([(int)$_SESSION['customer_id']]);
        $wishlistIds = array_column($wst->fetchAll(), 'product_id');
    } catch (\Exception $e) {}
}

include 'includes/header.php';
?>


<!-- HERO SLIDER -->
<section class="hero-slider" id="hero" aria-label="Hero">

  <!-- Slide 1 -->
  <div class="slide active">
    <div class="slide__img" style="background-image: url('assets/images/hero-1.jpg');"></div>
    <div class="slide__overlay"></div>
    <div class="slide__content">
      <div class="slide__body">
        <div class="slide__tag">Authentic Ceylon Gems</div>
        <h1 class="slide__title">Rare Gems.<br /><em>Timeless</em> Beauty.</h1>
        <p class="slide__desc">
          The world's finest certified Ceylon sapphires, rubies and precious gemstones — sourced directly from Sri Lanka's legendary gem mines.
        </p>
        <div class="slide__actions">
          <a href="shop.php" class="btn btn--gold"><i class="fas fa-gem"></i> Explore Collection</a>
          <a href="#about" class="btn btn--outline-white">Our Story</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Slide 2 -->
  <div class="slide">
    <div class="slide__img" style="background-image: url('assets/images/hero-2.jpg');"></div>
    <div class="slide__overlay"></div>
    <div class="slide__content">
      <div class="slide__body">
        <div class="slide__tag">Handcrafted Jewellery</div>
        <h2 class="slide__title">Crafted with<br /><em>Passion</em> &amp; Skill.</h2>
        <p class="slide__desc">
          Every piece of jewellery is handcrafted by master artisans, setting certified gemstones in 18K gold and platinum.
        </p>
        <div class="slide__actions">
          <a href="shop.php?cat=jewellery" class="btn btn--gold"><i class="fas fa-ring"></i> View Jewellery</a>
          <a href="contact.php" class="btn btn--outline-white">Custom Order</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Slide 3 -->
  <div class="slide">
    <div class="slide__img" style="background-image: url('assets/images/hero-3.jpg');"></div>
    <div class="slide__overlay"></div>
    <div class="slide__content">
      <div class="slide__body">
        <div class="slide__tag">GIA Certified Sapphires</div>
        <h2 class="slide__title">The Blue <em>Sapphire</em><br />of Ceylon.</h2>
        <p class="slide__desc">
          Internationally certified, naturally sourced, breathtakingly beautiful. Discover our collection of Ceylon's most celebrated sapphires.
        </p>
        <div class="slide__actions">
          <a href="shop.php?cat=blue-sapphire" class="btn btn--gold"><i class="fas fa-gem"></i> View Sapphires</a>
          <a href="shop.php" class="btn btn--outline-white">All Gems</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Arrows -->
  <button class="slider-arrow slider-arrow--prev" aria-label="Previous slide">
    <i class="fas fa-chevron-left"></i>
  </button>
  <button class="slider-arrow slider-arrow--next" aria-label="Next slide">
    <i class="fas fa-chevron-right"></i>
  </button>

  <!-- Dots -->
  <div class="slider-dots" role="tablist">
    <button class="slider-dot active" aria-label="Slide 1" role="tab"></button>
    <button class="slider-dot"        aria-label="Slide 2" role="tab"></button>
    <button class="slider-dot"        aria-label="Slide 3" role="tab"></button>
  </div>

  <!-- Counter -->
  <div class="slider-counter">
    <span class="current">01</span><span class="sep"> / </span><span>03</span>
  </div>

  <!-- Progress -->
  <div class="slider-progress running"></div>

  <!-- Scroll cue -->
  <div class="scroll-cue" aria-hidden="true">
    <span>Scroll</span>
    <div class="scroll-cue__line"></div>
  </div>

</section>


<!-- MARQUEE -->
<div class="marquee" aria-hidden="true">
  <div class="marquee__track">
    <span class="marquee__item"><i class="fas fa-gem"></i> Ceylon Blue Sapphires</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Cat's Eye Chrysoberyl</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Padparadscha Sapphires</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Natural Rubies</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Star Sapphires</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Custom Jewellery</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> GIA Certified Gems</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Worldwide Delivery</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Ceylon Blue Sapphires</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Cat's Eye Chrysoberyl</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Padparadscha Sapphires</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Natural Rubies</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Star Sapphires</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Custom Jewellery</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> GIA Certified Gems</span>
    <span class="marquee__item"><i class="fas fa-gem"></i> Worldwide Delivery</span>
  </div>
</div>


<!-- COLLECTIONS -->
<section class="section" id="collections">
  <div class="container">

    <div class="text-center reveal">
      <div class="eyebrow">Browse by Category</div>
      <h2 class="section-title title-shine">Our Collections</h2>
      <p class="section-sub">From rare Ceylon sapphires to handcrafted gold jewellery — explore our curated world of gemstones.</p>
    </div>

    <div class="collections__grid">

      <?php
      $delays  = ['reveal-delay-1','reveal-delay-2','reveal-delay-3','reveal-delay-4','reveal-delay-5','reveal-delay-6'];

      // Static fallback used when DB has no show_on_home categories yet
      $fallback = [
        ['name'=>'Blue Sapphires',  'slug'=>'blue-sapphire',   'subtitle'=>"Ceylon's Finest",    'image'=>'assets/images/cat-sapphires.jpg'],
        ['name'=>'Padparadscha',    'slug'=>'padparadscha',    'subtitle'=>'Sunset Sapphires',    'image'=>'assets/images/cat-padparadscha.jpg'],
        ['name'=>'Loose Gems',      'slug'=>'loose-gemstones', 'subtitle'=>'Certified &amp; Natural','image'=>'assets/images/cat-gems.jpg'],
        ['name'=>'Jewellery',       'slug'=>'jewellery',       'subtitle'=>'Gold &amp; Platinum', 'image'=>'assets/images/cat-jewellery.jpg'],
      ];

      $cards = !empty($homeCategories) ? $homeCategories : $fallback;

      foreach ($cards as $i => $cat):
        $delay = $delays[$i] ?? '';
        $img   = htmlspecialchars($cat['image'] ?? 'assets/images/cat-gems.jpg');
        $name  = htmlspecialchars($cat['name']);
        $sub   = $cat['subtitle'] ?? '';
        $slug  = htmlspecialchars($cat['slug']);
      ?>
      <a href="shop.php?cat=<?= $slug ?>" class="collection-card reveal <?= $delay ?>">
        <div class="collection-card__img">
          <img src="<?= $img ?>" alt="<?= $name ?>" loading="lazy"
               onerror="this.src='assets/images/cat-gems.jpg'" />
          <div class="collection-card__overlay"></div>
        </div>
        <div class="collection-card__body">
          <?php if ($sub): ?><p><?= $sub ?></p><?php endif; ?>
          <h3><?= $name ?></h3>
          <span class="collection-card__link">Shop Now <i class="fas fa-arrow-right"></i></span>
        </div>
      </a>
      <?php endforeach; ?>

    </div>
  </div>
</section>


<!-- FEATURED PRODUCTS -->
<section class="section section--alt" id="products">
  <div class="container">

    <div class="text-center reveal">
      <div class="eyebrow">Hand Picked</div>
      <h2 class="section-title title-shine">Featured Products</h2>
      <p class="section-sub">Each piece is individually selected, certified, and crafted to the highest standard.</p>
    </div>

    <div class="products__grid">

      <?php if ($featuredProducts): ?>
        <?php
        $delays = ['reveal-delay-1','reveal-delay-2','reveal-delay-3','reveal-delay-1','reveal-delay-2','reveal-delay-3'];
        foreach ($featuredProducts as $i => $p):
          $price      = '$' . number_format((float)$p['price_usd'], 0);
          $compare    = $p['compare_price'] ? '$' . number_format((float)$p['compare_price'], 0) : null;
          $weight     = $p['weight_ct'] ? rtrim(rtrim(number_format((float)$p['weight_ct'], 2), '0'), '.') . 'ct' : null;
          $avgRating  = (float)$p['avg_rating'];
          $reviews    = (int)$p['review_count'];
          $inWishlist = in_array($p['id'], $wishlistIds);
          $delay      = $delays[$i % 3];

          $badge = null;
          if ($p['is_featured'])   $badge = ['label' => 'Bestseller', 'cls' => 'product-badge--gold'];
          if ($p['compare_price']) $badge = ['label' => 'Sale',       'cls' => 'product-badge--gold'];
          if ($p['stock'] == 0)    $badge = ['label' => 'Sold Out',   'cls' => 'product-badge--sold-out'];
        ?>
        <article class="product-card reveal <?= $delay ?>"
                 data-id="<?= $p['id'] ?>"
                 data-slug="<?= htmlspecialchars($p['slug']) ?>"
                 data-price="<?= (int)$p['price_usd'] ?>">
          <div class="product-card__img">
            <?php if ($p['image_main']): ?>
            <img src="<?= htmlspecialchars($p['image_main']) ?>"
                 alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" />
            <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8f6f0;color:#ccc;font-size:40px;">
              <i class="fas fa-gem"></i>
            </div>
            <?php endif; ?>

            <?php if ($badge): ?>
            <span class="product-badge <?= $badge['cls'] ?>"><?= $badge['label'] ?></span>
            <?php endif; ?>

            <button class="product-card__wishlist js-wishlist-btn<?= $inWishlist ? ' active' : '' ?>"
                    data-id="<?= $p['id'] ?>" aria-label="Add to wishlist">
              <i class="<?= $inWishlist ? 'fas' : 'far' ?> fa-heart"></i>
            </button>

            <div class="product-card__overlay">
              <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="btn-quickview">
                <i class="fas fa-eye"></i> Quick View
              </a>
            </div>
          </div>

          <div class="product-card__info">
            <?php if ($p['category_name']): ?>
            <div class="product-card__cat"><?= htmlspecialchars($p['category_name']) ?></div>
            <?php endif; ?>

            <h3 class="product-card__name">
              <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" style="color:inherit;text-decoration:none;">
                <?= htmlspecialchars($p['name']) ?><?= $weight ? ' — ' . $weight : '' ?>
              </a>
            </h3>

            <div class="product-card__rating">
              <div class="stars">
                <?php for ($s = 1; $s <= 5; $s++):
                  if ($s <= $avgRating)           echo '<i class="fas fa-star"></i>';
                  elseif ($s - 0.5 <= $avgRating) echo '<i class="fas fa-star-half-alt"></i>';
                  else                            echo '<i class="far fa-star"></i>';
                endfor; ?>
              </div>
              <?php if ($reviews > 0): ?>
              <span class="rating-count">(<?= $reviews ?> review<?= $reviews != 1 ? 's' : '' ?>)</span>
              <?php endif; ?>
            </div>

            <div class="product-card__footer">
              <div class="product-card__price">
                <?= $price ?>
                <?php if ($compare): ?><span class="price-old"><?= $compare ?></span><?php endif; ?>
              </div>
              <?php if ($p['stock'] > 0): ?>
              <button class="btn-cart" data-action="add-to-cart" data-id="<?= $p['id'] ?>">
                <i class="fas fa-shopping-bag"></i> Add to Cart
              </button>
              <?php else:
                $_wa = get_site_setting('social_whatsapp');
                $_waMsg = 'Hi, I\'m interested in ' . $p['name'] . '. Is it available or coming back in stock?';
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

      <?php else: ?>

        <!-- Fallback static cards when DB has no products yet -->
        <article class="product-card reveal reveal-delay-1">
          <div class="product-card__img">
            <img src="assets/images/prod-1.jpg" alt="Royal Ceylon Blue Sapphire" loading="lazy" />
            <span class="product-badge product-badge--gold">Bestseller</span>
            <button class="product-card__wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
            <div class="product-card__overlay">
              <a href="shop.php" class="btn-quickview"><i class="fas fa-eye"></i> Quick View</a>
            </div>
          </div>
          <div class="product-card__info">
            <div class="product-card__cat">Blue Sapphire</div>
            <h3 class="product-card__name">Royal Ceylon Blue Sapphire — 3.5ct</h3>
            <div class="product-card__rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
              <span class="rating-count">(24 reviews)</span>
            </div>
            <div class="product-card__footer">
              <div class="product-card__price">$1,850 <span class="price-old">$2,200</span></div>
              <button class="btn-cart" data-action="add-to-cart"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
            </div>
          </div>
        </article>

        <article class="product-card reveal reveal-delay-2">
          <div class="product-card__img">
            <img src="assets/images/prod-2.jpg" alt="18K Gold Sapphire Ring" loading="lazy" />
            <span class="product-badge product-badge--dark">New Arrival</span>
            <button class="product-card__wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
            <div class="product-card__overlay">
              <a href="shop.php" class="btn-quickview"><i class="fas fa-eye"></i> Quick View</a>
            </div>
          </div>
          <div class="product-card__info">
            <div class="product-card__cat">Jewellery Ring</div>
            <h3 class="product-card__name">18K Gold Sapphire Solitaire Ring</h3>
            <div class="product-card__rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
              <span class="rating-count">(18 reviews)</span>
            </div>
            <div class="product-card__footer">
              <div class="product-card__price">$3,200</div>
              <button class="btn-cart" data-action="add-to-cart"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
            </div>
          </div>
        </article>

        <article class="product-card reveal reveal-delay-3">
          <div class="product-card__img">
            <img src="assets/images/prod-3.jpg" alt="Chrysoberyl Cat's Eye" loading="lazy" />
            <span class="product-badge product-badge--gold">Rare Find</span>
            <button class="product-card__wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
            <div class="product-card__overlay">
              <a href="shop.php" class="btn-quickview"><i class="fas fa-eye"></i> Quick View</a>
            </div>
          </div>
          <div class="product-card__info">
            <div class="product-card__cat">Cat's Eye</div>
            <h3 class="product-card__name">Chrysoberyl Cat's Eye — 4.2ct</h3>
            <div class="product-card__rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
              <span class="rating-count">(9 reviews)</span>
            </div>
            <div class="product-card__footer">
              <div class="product-card__price">$2,400</div>
              <button class="btn-cart" data-action="add-to-cart"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
            </div>
          </div>
        </article>

        <article class="product-card reveal reveal-delay-1">
          <div class="product-card__img">
            <img src="assets/images/prod-4.jpg" alt="Padparadscha Sapphire" loading="lazy" />
            <span class="product-badge product-badge--outline">Limited</span>
            <button class="product-card__wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
            <div class="product-card__overlay">
              <a href="shop.php" class="btn-quickview"><i class="fas fa-eye"></i> Quick View</a>
            </div>
          </div>
          <div class="product-card__info">
            <div class="product-card__cat">Padparadscha</div>
            <h3 class="product-card__name">Padparadscha Sapphire — 2.8ct</h3>
            <div class="product-card__rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
              <span class="rating-count">(32 reviews)</span>
            </div>
            <div class="product-card__footer">
              <div class="product-card__price">$5,600 <span class="price-old">$6,200</span></div>
              <button class="btn-cart" data-action="add-to-cart"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
            </div>
          </div>
        </article>

        <article class="product-card reveal reveal-delay-2">
          <div class="product-card__img">
            <img src="assets/images/prod-5.jpg" alt="Blue Star Sapphire" loading="lazy" />
            <button class="product-card__wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
            <div class="product-card__overlay">
              <a href="shop.php" class="btn-quickview"><i class="fas fa-eye"></i> Quick View</a>
            </div>
          </div>
          <div class="product-card__info">
            <div class="product-card__cat">Star Sapphire</div>
            <h3 class="product-card__name">Blue Star Sapphire Cabochon — 6ct</h3>
            <div class="product-card__rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
              <span class="rating-count">(14 reviews)</span>
            </div>
            <div class="product-card__footer">
              <div class="product-card__price">$3,900</div>
              <button class="btn-cart" data-action="add-to-cart"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
            </div>
          </div>
        </article>

        <article class="product-card reveal reveal-delay-3">
          <div class="product-card__img">
            <img src="assets/images/prod-6.jpg" alt="Natural Ceylon Ruby" loading="lazy" />
            <span class="product-badge product-badge--dark">New Arrival</span>
            <button class="product-card__wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
            <div class="product-card__overlay">
              <a href="shop.php" class="btn-quickview"><i class="fas fa-eye"></i> Quick View</a>
            </div>
          </div>
          <div class="product-card__info">
            <div class="product-card__cat">Ruby</div>
            <h3 class="product-card__name">Natural Ceylon Ruby — 2.1ct</h3>
            <div class="product-card__rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
              <span class="rating-count">(7 reviews)</span>
            </div>
            <div class="product-card__footer">
              <div class="product-card__price">$4,100</div>
              <button class="btn-cart" data-action="add-to-cart"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
            </div>
          </div>
        </article>

      <?php endif; ?>

    </div>

    <div class="text-center" style="margin-top:56px;">
      <a href="shop.php" class="btn btn--dark">View All Products <i class="fas fa-arrow-right"></i></a>
    </div>

  </div>
</section>


<!-- ABOUT -->
<section class="section" id="about">
  <div class="container">
    <div class="about__grid">

      <div class="about__visual reveal">
        <div class="about__frame"></div>
        <img src="assets/images/about.jpg" alt="Mirabella Ceylon Gemstones" class="about__img-main" />
        <div class="about__stat-badge float-anim">
          <div class="num">15+</div>
          <div class="lbl">Years of Excellence</div>
        </div>
      </div>

      <div class="about__content reveal reveal-delay-2">
        <div class="eyebrow" style="justify-content:flex-start;">Our Heritage</div>
        <h2 class="section-title">
          The Finest Gems from<br />
          <em style="font-style:italic; color:var(--gold);">the Pearl of the Orient</em>
        </h2>
        <div class="about__rule"></div>
        <p class="about__text">
          Mirabella Ceylon was founded with a singular passion — to bring the world's most exquisite Ceylon gemstones to collectors, jewellers, and gem enthusiasts everywhere. Sri Lanka has been celebrated for millennia as the island of gems.
        </p>
        <p class="about__text">
          Under the personal direction of <strong>Chamodhya Kularathna Bandara</strong>, every gemstone we offer is individually sourced, graded, and certified to ensure absolute authenticity and quality.
        </p>
        <div class="about__features">
          <div class="about__feature"><i class="fas fa-check-circle"></i><span>GIA &amp; GRS Certified Gems</span></div>
          <div class="about__feature"><i class="fas fa-check-circle"></i><span>100% Natural, No Treatments</span></div>
          <div class="about__feature"><i class="fas fa-check-circle"></i><span>Custom Jewellery Design</span></div>
          <div class="about__feature"><i class="fas fa-check-circle"></i><span>Worldwide Insured Shipping</span></div>
          <div class="about__feature"><i class="fas fa-check-circle"></i><span>Expert Gem Consultation</span></div>
          <div class="about__feature"><i class="fas fa-check-circle"></i><span>Buyback Guarantee</span></div>
        </div>
        <div class="director-sig">
          <div class="director-sig__avatar">
            <img src="assets/images/director.jpg" alt="Chamodhya"
                 onerror="this.outerHTML='<i class=\'fas fa-user\'></i>'" />
          </div>
          <div>
            <div class="director-sig__name">Chamodhya Kularathna Bandara</div>
            <div class="director-sig__role">Founder &amp; Director, Mirabella Ceylon</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- STATS BAND -->
<div class="stats-band">
  <div class="container stats-band__grid">
    <div class="stat-item reveal reveal-delay-1">
      <div class="stat-item__num" data-count="500">0</div>
      <div class="stat-item__plus">+</div>
      <div class="stat-item__label">Certified Gems</div>
    </div>
    <div class="stat-item reveal reveal-delay-2">
      <div class="stat-item__num" data-count="15">0</div>
      <div class="stat-item__plus">+</div>
      <div class="stat-item__label">Years of Trust</div>
    </div>
    <div class="stat-item reveal reveal-delay-3">
      <div class="stat-item__num" data-count="50">0</div>
      <div class="stat-item__plus">+</div>
      <div class="stat-item__label">Countries Served</div>
    </div>
    <div class="stat-item reveal reveal-delay-4">
      <div class="stat-item__num" data-count="1200">0</div>
      <div class="stat-item__plus">+</div>
      <div class="stat-item__label">Happy Clients</div>
    </div>
  </div>
</div>


<!-- WHY US -->
<section class="why-us" id="why">
  <div class="container">
    <div class="text-center reveal">
      <div class="eyebrow" style="color:var(--gold-light);">Why Choose Us</div>
      <h2 class="section-title section-title--light">The Mirabella Promise</h2>
      <p class="section-sub section-sub--light">Our commitment goes beyond the gemstone. We deliver trust, expertise, and an experience worthy of the gems we carry.</p>
    </div>
    <div class="why-us__grid">
      <div class="why-card reveal reveal-delay-1">
        <div class="why-card__icon"><i class="fas fa-certificate"></i></div>
        <h3>Certified Authentic</h3>
        <p>Every gemstone comes with internationally recognised certification from GIA, GRS, or GGTL laboratories.</p>
      </div>
      <div class="why-card reveal reveal-delay-2">
        <div class="why-card__icon"><i class="fas fa-globe-asia"></i></div>
        <h3>Worldwide Delivery</h3>
        <p>Fully insured express delivery to over 50 countries. Your gem arrives safely with full tracking every step.</p>
      </div>
      <div class="why-card reveal reveal-delay-3">
        <div class="why-card__icon"><i class="fas fa-gem"></i></div>
        <h3>Direct from Source</h3>
        <p>We work directly with trusted miners in Sri Lanka - no middlemen, ensuring authenticity and fair pricing.</p>
      </div>
      <div class="why-card reveal reveal-delay-4">
        <div class="why-card__icon"><i class="fas fa-headset"></i></div>
        <h3>Expert Guidance</h3>
        <p>Our certified gemologists provide personal consultations to help you find the perfect gem or jewellery piece.</p>
      </div>
    </div>
  </div>
</section>


<!-- TESTIMONIALS -->
<section class="section section--alt" id="testimonials">
  <div class="container">
    <div class="text-center reveal">
      <div class="eyebrow">Client Stories</div>
      <h2 class="section-title title-shine">What Our Clients Say</h2>
      <p class="section-sub">Trusted by collectors, jewellers and gem lovers across the world.</p>
    </div>

    <div class="testimonials__slider reveal" id="testimonialsSlider">

      <button class="testimonials__arrow testimonials__arrow--prev" id="tPrev" aria-label="Previous review">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button class="testimonials__arrow testimonials__arrow--next" id="tNext" aria-label="Next review">
        <i class="fas fa-chevron-right"></i>
      </button>

      <div class="testimonials__track" id="testimonialsTrack">

        <?php
        // Helper: render star icons for a rating
        function homepageStars(int $r): string {
            $out = '';
            for ($i = 1; $i <= 5; $i++) {
                $out .= $i <= $r ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
            }
            return $out;
        }

        if (!empty($testimonials)):
            foreach ($testimonials as $t):
                $name = $t['first_name']
                    ? htmlspecialchars($t['first_name'] . ' ' . $t['last_name'])
                    : htmlspecialchars($t['reviewer_name'] ?? 'Customer');
                $text = htmlspecialchars($t['body']);
        ?>
        <div class="testimonial-card">
          <div class="testimonial-card__quote">"</div>
          <div class="stars"><?= homepageStars((int)$t['rating']) ?></div>
          <p class="testimonial-card__text"><?= $text ?></p>
          <div class="testimonial-card__reviewer">
            <div class="reviewer__avatar"><i class="fas fa-user"></i></div>
            <div>
              <div class="reviewer__name"><?= $name ?></div>
              <?php if (!empty($t['title'])): ?>
              <div class="reviewer__location"><?= htmlspecialchars($t['title']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; else: ?>

        <!-- Static fallback — shown until real reviews are approved -->
        <div class="testimonial-card">
          <div class="testimonial-card__quote">"</div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p class="testimonial-card__text">The blue sapphire I purchased was absolutely stunning. The certification, packaging, and communication were all first class. Will definitely buy again.</p>
          <div class="testimonial-card__reviewer">
            <div class="reviewer__avatar"><i class="fas fa-user"></i></div>
            <div>
              <div class="reviewer__name">James H.</div>
              <div class="reviewer__location">United Kingdom</div>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="testimonial-card__quote">"</div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p class="testimonial-card__text">Mirabella Ceylon is my go-to source for authentic padparadscha sapphires. The quality is unmatched and the personal attention is outstanding.</p>
          <div class="testimonial-card__reviewer">
            <div class="reviewer__avatar"><i class="fas fa-user"></i></div>
            <div>
              <div class="reviewer__name">Yuki T.</div>
              <div class="reviewer__location">Japan</div>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="testimonial-card__quote">"</div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
          <p class="testimonial-card__text">I commissioned a custom ring and the result exceeded all my expectations. The gem is breathtaking and the craftsmanship is truly world class.</p>
          <div class="testimonial-card__reviewer">
            <div class="reviewer__avatar"><i class="fas fa-user"></i></div>
            <div>
              <div class="reviewer__name">Priya M.</div>
              <div class="reviewer__location">Australia</div>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="testimonial-card__quote">"</div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p class="testimonial-card__text">Excellent service from start to finish. My alexandrite arrived beautifully packaged with all documents. The colour change is spectacular — exactly as described.</p>
          <div class="testimonial-card__reviewer">
            <div class="reviewer__avatar"><i class="fas fa-user"></i></div>
            <div>
              <div class="reviewer__name">Marcus L.</div>
              <div class="reviewer__location">Germany</div>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="testimonial-card__quote">"</div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p class="testimonial-card__text">I have bought from dealers worldwide and Mirabella Ceylon stands out for their honesty and transparency. The NGJA certificate gave me complete peace of mind.</p>
          <div class="testimonial-card__reviewer">
            <div class="reviewer__avatar"><i class="fas fa-user"></i></div>
            <div>
              <div class="reviewer__name">Sophia R.</div>
              <div class="reviewer__location">United States</div>
            </div>
          </div>
        </div>

        <?php endif; ?>

      </div>

      <div class="testimonials__dots" id="testimonialsDots"></div>

    </div>
  </div>
</section>


<!-- NEWSLETTER -->
<section class="newsletter" id="contact" aria-label="Newsletter">
  <div class="container">
    <div class="newsletter__inner">
      <div class="newsletter__content reveal">
        <div class="eyebrow" style="color:var(--gold-light);">Stay Connected</div>
        <h2 class="newsletter__title">Join the<br /><em>Inner Circle</em></h2>
        <p class="newsletter__desc">Be the first to discover rare gem arrivals, exclusive offers, and gemstone insights from the heart of Ceylon.</p>
      </div>
      <div class="newsletter__form-wrap reveal reveal-delay-2">
        <form class="newsletter__form" id="newsletterForm" novalidate>
          <input type="text"  placeholder="Your full name"     aria-label="Full name" />
          <input type="email" placeholder="Your email address" aria-label="Email address" required />
          <button class="btn btn--gold" type="submit">Subscribe Now <i class="fas fa-arrow-right"></i></button>
        </form>
        <p class="newsletter__note">No spam. Unsubscribe anytime. We respect your privacy.</p>
      </div>
    </div>
  </div>
</section>


<?php include 'includes/footer.php'; ?>
