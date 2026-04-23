<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/activity.php';

$pageTitle  = 'Products';
$activePage = 'products';

$flash = '';
$flashType = 'success';

// Helpers 
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function uniqueSlug(PDO $db, string $base, ?int $excludeId = null): string {
    $slug = slugify($base);
    $orig = $slug;
    $i    = 1;
    while (true) {
        $sql  = 'SELECT COUNT(*) FROM products WHERE slug = ?';
        $args = [$slug];
        if ($excludeId) { $sql .= ' AND id != ?'; $args[] = $excludeId; }
        $st = $db->prepare($sql);
        $st->execute($args);
        if ((int)$st->fetchColumn() === 0) break;
        $slug = $orig . '-' . $i++;
    }
    return $slug;
}

function handleImageUpload(string $field): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['image/jpeg','image/png','image/webp'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;

    $ext  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];
    $dir  = __DIR__ . '/../assets/images/products/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('prod_', true) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . $name);
    return 'assets/images/products/' . $name;
}

// Process POST 
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pname = $db->prepare('SELECT name FROM products WHERE id = ?');
            $pname->execute([$id]);
            $pn = $pname->fetchColumn() ?: "Product #$id";
            $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            log_activity('product', "Product \"$pn\" deleted.");
            $flash = 'Product deleted successfully.';
        }
        header('Location: products.php?flash=' . urlencode($flash) . '&ft=success');
        exit;
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id'] ?? 0);
        $val = (int)($_POST['val'] ?? 0);
        if ($id) {
            $db->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$val, $id]);
            $pname = $db->prepare('SELECT name FROM products WHERE id = ?');
            $pname->execute([$id]);
            $pn = $pname->fetchColumn() ?: "Product #$id";
            log_activity('product', "Product \"$pn\" " . ($val ? 'activated' : 'deactivated') . '.');
        }
        header('Location: products.php');
        exit;
    }

    if (in_array($action, ['add', 'edit'], true)) {
        $id = $action === 'edit' ? (int)($_POST['id'] ?? 0) : null;

        $name        = trim($_POST['name']         ?? '');
        $catId       = (int)($_POST['category_id'] ?? 0) ?: null;
        $sku         = trim($_POST['sku']           ?? '') ?: null;
        $shortDesc   = trim($_POST['short_desc']    ?? '') ?: null;
        $description = trim($_POST['description']   ?? '') ?: null;
        $gemType     = trim($_POST['gemstone_type'] ?? '') ?: null;
        $origin      = trim($_POST['origin']        ?? '') ?: null;
        $weightCt    = trim($_POST['weight_ct']     ?? '') ?: null;
        $dimensions  = trim($_POST['dimensions']    ?? '') ?: null;
        $colour      = trim($_POST['colour']        ?? '') ?: null;
        $clarity     = trim($_POST['clarity']       ?? '') ?: null;
        $cut         = trim($_POST['cut']           ?? '') ?: null;
        $treatment   = trim($_POST['treatment']     ?? '') ?: null;
        $cert        = trim($_POST['certification'] ?? '') ?: null;
        $price       = (float)($_POST['price_usd']  ?? 0);
        $compareP    = trim($_POST['compare_price'] ?? '') ?: null;
        $stock       = (int)($_POST['stock']        ?? 1);
        $isFeatured  = isset($_POST['is_featured'])  ? 1 : 0;
        $isActive    = isset($_POST['is_active'])    ? 1 : 0;

        // Check SKU uniqueness
        $skuConflict = false;
        if ($sku) {
            $skuSql  = 'SELECT COUNT(*) FROM products WHERE sku = ?';
            $skuArgs = [$sku];
            if ($id) { $skuSql .= ' AND id != ?'; $skuArgs[] = $id; }
            $skuSt = $db->prepare($skuSql);
            $skuSt->execute($skuArgs);
            $skuConflict = (int)$skuSt->fetchColumn() > 0;
        }

        if (!$name || $price <= 0) {
            $flash = 'Name and price are required.';
            $flashType = 'error';
        } elseif ($skuConflict) {
            $flash = "SKU \"$sku\" is already used by another product. Please use a unique SKU.";
            $flashType = 'error';
        } else {
            $slug      = uniqueSlug($db, $name, $id);
            $imagePath = handleImageUpload('image_main');

            if ($action === 'add') {
                $st = $db->prepare("INSERT INTO products
                    (category_id, name, slug, sku, short_desc, description,
                     gemstone_type, origin, weight_ct, dimensions, colour, clarity,
                     cut, treatment, certification, price_usd, compare_price,
                     stock, is_featured, is_active, image_main)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $st->execute([$catId,$name,$slug,$sku,$shortDesc,$description,
                    $gemType,$origin,$weightCt,$dimensions,$colour,$clarity,
                    $cut,$treatment,$cert,$price,$compareP,
                    $stock,$isFeatured,$isActive,$imagePath]);
                log_activity('product', "New product \"{$name}\" added.");
                $flash = "Product \"{$name}\" added successfully.";
            } else {
                $existing = $db->prepare('SELECT image_main FROM products WHERE id = ?');
                $existing->execute([$id]);
                $row       = $existing->fetch();
                $finalImg  = $imagePath ?? ($row['image_main'] ?? null);

                $st = $db->prepare("UPDATE products SET
                    category_id=?, name=?, slug=?, sku=?, short_desc=?, description=?,
                    gemstone_type=?, origin=?, weight_ct=?, dimensions=?, colour=?,
                    clarity=?, cut=?, treatment=?, certification=?,
                    price_usd=?, compare_price=?, stock=?, is_featured=?, is_active=?,
                    image_main=?
                    WHERE id=?");
                $st->execute([$catId,$name,$slug,$sku,$shortDesc,$description,
                    $gemType,$origin,$weightCt,$dimensions,$colour,$clarity,
                    $cut,$treatment,$cert,$price,$compareP,
                    $stock,$isFeatured,$isActive,$finalImg,$id]);
                log_activity('product', "Product \"{$name}\" updated.");
                $flash = "Product \"{$name}\" updated successfully.";
            }

            header('Location: products.php?flash=' . urlencode($flash) . '&ft=success');
            exit;
        }
    }
}

// Flash from redirect 
if (!$flash && isset($_GET['flash'])) {
    $flash     = htmlspecialchars($_GET['flash']);
    $flashType = $_GET['ft'] ?? 'success';
}

// Fetch categories 
$categories = $db->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

// Filters 
$search   = trim($_GET['q']      ?? '');
$filterCat= (int)($_GET['cat']   ?? 0);
$filterSt = $_GET['status']      ?? '';

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(p.name LIKE ? OR p.sku LIKE ? OR p.gemstone_type LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterCat) {
    $where[]  = 'p.category_id = ?';
    $params[] = $filterCat;
}
if ($filterSt === 'active')   { $where[] = 'p.is_active = 1'; }
if ($filterSt === 'inactive') { $where[] = 'p.is_active = 0'; }
if ($filterSt === 'featured') { $where[] = 'p.is_featured = 1'; }
if ($filterSt === 'low')      { $where[] = 'p.stock = 0'; }

$whereStr = implode(' AND ', $where);

$products = $db->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE $whereStr
    ORDER BY p.created_at DESC
");
$products->execute($params);
$products = $products->fetchAll();

// Stats 
$stats = [
    'total'    => (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'active'   => (int)$db->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'featured' => (int)$db->query('SELECT COUNT(*) FROM products WHERE is_featured = 1')->fetchColumn(),
    'outstock' => (int)$db->query('SELECT COUNT(*) FROM products WHERE stock = 0')->fetchColumn(),
];

// Edit: fetch product data 
$editProduct = null;
if (isset($_GET['edit'])) {
    $ep = $db->prepare('SELECT * FROM products WHERE id = ?');
    $ep->execute([(int)$_GET['edit']]);
    $editProduct = $ep->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Products | Mirabella Ceylon Admin</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/admin.css" />
  <style>
    /* Drawer */
    .drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:900;opacity:0;pointer-events:none;transition:opacity .3s;}
    .drawer-overlay.open{opacity:1;pointer-events:all;}
    .drawer{position:fixed;top:0;right:0;height:100%;width:min(600px,100%);background:var(--dark-card);border-left:1px solid var(--dark-border);z-index:901;transform:translateX(100%);transition:transform .3s var(--ease);display:flex;flex-direction:column;overflow:hidden;}
    .drawer.open{transform:translateX(0);}
    .drawer__head{padding:20px 26px;border-bottom:1px solid var(--dark-border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
    .drawer__title{font-family:var(--font-display);font-size:18px;font-weight:600;color:var(--text);}
    .drawer__close{width:34px;height:34px;border:none;background:none;color:var(--text-soft);cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:.2s;}
    .drawer__close:hover{background:rgba(255,255,255,.06);color:var(--text);}
    .drawer__body{flex:1;overflow-y:auto;padding:24px 26px;}
    .drawer__footer{padding:16px 26px;border-top:1px solid var(--dark-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;background:var(--dark-card);}

    /* Form fields */
    .pf-section{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--dark-border);}
    .pf-section:first-child{margin-top:0;}
    .pf-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .pf-grid--3{grid-template-columns:1fr 1fr 1fr;}
    .pf-full{grid-column:1/-1;}
    .pf-field{display:flex;flex-direction:column;gap:6px;}
    .pf-field label{font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-mid);}
    .pf-field label span{color:var(--danger);margin-left:2px;}
    .pf-field input,.pf-field select,.pf-field textarea{
      background:var(--dark-3);border:1px solid var(--dark-border);border-radius:7px;
      color:var(--text);font-family:var(--font-body);font-size:13px;
      padding:9px 12px;transition:.2s;width:100%;}
    .pf-field input:focus,.pf-field select:focus,.pf-field textarea:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-glow);}
    .pf-field textarea{resize:vertical;min-height:80px;}
    .pf-field select option{background:var(--dark-3);}

    /* Toggle switch */
    .pf-toggle{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text-mid);cursor:pointer;}
    .pf-toggle input{display:none;}
    .pf-toggle__track{width:36px;height:20px;background:var(--dark-border);border-radius:20px;position:relative;transition:.2s;flex-shrink:0;}
    .pf-toggle__track::after{content:'';position:absolute;top:3px;left:3px;width:14px;height:14px;background:#fff;border-radius:50%;transition:.2s;}
    .pf-toggle input:checked + .pf-toggle__track{background:var(--gold);}
    .pf-toggle input:checked + .pf-toggle__track::after{left:19px;}

    /* Image upload */
    .img-upload{border:2px dashed var(--dark-border);border-radius:9px;padding:20px;text-align:center;cursor:pointer;transition:.2s;position:relative;}
    .img-upload:hover{border-color:var(--gold);background:var(--gold-pale);}
    .img-upload input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
    .img-upload__icon{font-size:22px;color:var(--text-soft);margin-bottom:6px;}
    .img-upload__text{font-size:12px;color:var(--text-mid);}
    .img-preview{width:100%;max-height:140px;object-fit:contain;border-radius:7px;margin-top:10px;display:none;}
    .img-preview.show{display:block;}

    /* Filter bar */
    .filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:20px;}
    .filter-bar input[type=search]{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:7px;color:var(--text);font-size:13px;padding:8px 14px 8px 36px;width:220px;transition:.2s;}
    .filter-bar input[type=search]:focus{outline:none;border-color:var(--gold);}
    .filter-search-wrap{position:relative;}
    .filter-search-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-soft);font-size:12px;pointer-events:none;}
    .filter-bar select{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:7px;color:var(--text-mid);font-size:12px;padding:8px 12px;cursor:pointer;transition:.2s;}
    .filter-bar select:focus{outline:none;border-color:var(--gold);}
    .filter-bar select option{background:var(--dark-3);}

    /* Product image in table */
    .prod-img{width:44px;height:44px;border-radius:7px;object-fit:cover;background:var(--dark-3);border:1px solid var(--dark-border);}
    .prod-img-placeholder{width:44px;height:44px;border-radius:7px;background:var(--dark-3);border:1px solid var(--dark-border);display:flex;align-items:center;justify-content:center;color:var(--text-soft);font-size:16px;}

    /* Delete modal */
    .del-modal{position:fixed;inset:0;z-index:950;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);opacity:0;pointer-events:none;transition:.25s;}
    .del-modal.open{opacity:1;pointer-events:all;}
    .del-modal__box{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:12px;padding:32px 36px;max-width:380px;width:90%;text-align:center;}
    .del-modal__icon{font-size:32px;color:var(--danger);margin-bottom:14px;}
    .del-modal__title{font-family:var(--font-display);font-size:18px;color:var(--text);margin-bottom:8px;}
    .del-modal__sub{font-size:13px;color:var(--text-mid);margin-bottom:22px;}
    .del-modal__btns{display:flex;gap:10px;justify-content:center;}

    /* Flash */
    .flash-bar{padding:12px 20px;border-radius:8px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
    .flash-bar--success{background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.25);color:#2ecc71;}
    .flash-bar--error{background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.25);color:#e74c3c;}

    /* Stock badge */
    .stock-ok{color:var(--success);font-weight:600;}
    .stock-low{color:var(--warning);font-weight:600;}
    .stock-out{color:var(--danger);font-weight:600;}
  </style>
</head>
<body>

<div class="admin-layout">

  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="admin-main" id="adminMain">
    <div class="admin-content">

      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header__left">
          <div class="page-header__eyebrow">Catalogue</div>
          <h1 class="page-header__title">Products</h1>
          <p class="page-header__sub">Manage your gemstone and jewellery listings.</p>
        </div>
        <div class="page-header__actions">
          <button class="btn-admin btn-admin--primary" id="btnAddProduct">
            <i class="fas fa-plus"></i> Add Product
          </button>
        </div>
      </div>

      <!-- Flash -->
      <?php if ($flash): ?>
      <div class="flash-bar flash-bar--<?= htmlspecialchars($flashType) ?>">
        <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $flash ?>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card stat-card--blue">
          <div class="stat-card__head">
            <div class="stat-card__label">Total Products</div>
            <div class="stat-card__icon"><i class="fas fa-gem"></i></div>
          </div>
          <div class="stat-card__value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card stat-card--green">
          <div class="stat-card__head">
            <div class="stat-card__label">Active</div>
            <div class="stat-card__icon"><i class="fas fa-check-circle"></i></div>
          </div>
          <div class="stat-card__value"><?= $stats['active'] ?></div>
        </div>
        <div class="stat-card stat-card--gold">
          <div class="stat-card__head">
            <div class="stat-card__label">Featured</div>
            <div class="stat-card__icon"><i class="fas fa-star"></i></div>
          </div>
          <div class="stat-card__value"><?= $stats['featured'] ?></div>
        </div>
        <div class="stat-card stat-card--orange">
          <div class="stat-card__head">
            <div class="stat-card__label">Out of Stock</div>
            <div class="stat-card__icon"><i class="fas fa-exclamation-circle"></i></div>
          </div>
          <div class="stat-card__value"><?= $stats['outstock'] ?></div>
        </div>
      </div>

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar" id="filterForm">
        <div class="filter-search-wrap">
          <i class="fas fa-search"></i>
          <input type="search" name="q" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>" />
        </div>
        <select name="cat" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $filterCat == $cat['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <select name="status" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="active"   <?= $filterSt==='active'   ? 'selected':'' ?>>Active</option>
          <option value="inactive" <?= $filterSt==='inactive' ? 'selected':'' ?>>Inactive</option>
          <option value="featured" <?= $filterSt==='featured' ? 'selected':'' ?>>Featured</option>
          <option value="low"      <?= $filterSt==='low'      ? 'selected':'' ?>>Out of Stock</option>
        </select>
        <?php if ($search || $filterCat || $filterSt): ?>
        <a href="products.php" class="btn-admin btn-admin--ghost btn-admin--sm">
          <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
      </form>

      <!-- Table -->
      <div class="admin-card">
        <div class="admin-card__head">
          <div class="admin-card__title">
            <i class="fas fa-gem"></i>
            <?= count($products) ?> Product<?= count($products) != 1 ? 's' : '' ?>
            <?= ($search || $filterCat || $filterSt) ? '<span style="font-weight:400;color:var(--text-soft);font-size:12px;"> (filtered)</span>' : '' ?>
          </div>
        </div>
        <div class="admin-table-wrap">
          <?php if (empty($products)): ?>
          <div class="admin-empty">
            <i class="fas fa-gem"></i>
            <p>No products found. Click <strong>Add Product</strong> to add your first listing.</p>
          </div>
          <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Image</th>
                <th>Product</th>
                <th>Category</th>
                <th>Price (USD)</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <?php if ($p['image_main']): ?>
                  <img src="../<?= htmlspecialchars($p['image_main']) ?>" alt="" class="prod-img" />
                  <?php else: ?>
                  <div class="prod-img-placeholder"><i class="fas fa-gem"></i></div>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                  <span style="font-size:11px;color:var(--text-soft);">
                    <?= $p['sku'] ? 'SKU: ' . htmlspecialchars($p['sku']) : '' ?>
                    <?= $p['weight_ct'] ? ' &nbsp;·&nbsp; ' . $p['weight_ct'] . ' ct' : '' ?>
                    <?= $p['origin'] ? ' &nbsp;·&nbsp; ' . htmlspecialchars($p['origin']) : '' ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td>
                  <strong>$<?= number_format($p['price_usd'], 2) ?></strong>
                  <?php if ($p['compare_price']): ?>
                  <br><span style="font-size:11px;color:var(--text-soft);text-decoration:line-through;">$<?= number_format($p['compare_price'], 2) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['stock'] == 0): ?>
                    <span class="stock-out">Out</span>
                  <?php elseif ($p['stock'] <= 2): ?>
                    <span class="stock-low"><?= $p['stock'] ?></span>
                  <?php else: ?>
                    <span class="stock-ok"><?= $p['stock'] ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <?php if ($p['is_active']): ?>
                      <span class="badge badge--active">Active</span>
                    <?php else: ?>
                      <span class="badge badge--cancelled">Inactive</span>
                    <?php endif; ?>
                    <?php if ($p['is_featured']): ?>
                      <span class="badge badge--gold">Featured</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            onclick="openEdit(<?= $p['id'] ?>)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="val" value="<?= $p['is_active'] ? 0 : 1 ?>">
                      <button type="submit" class="btn-admin btn-admin--ghost btn-admin--sm"
                              title="<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>">
                        <i class="fas fa-<?= $p['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                      </button>
                    </form>
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            style="color:var(--danger);"
                            onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')"
                            title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>


<!-- DRAWER — Add / Edit Product -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer" id="productDrawer">
  <div class="drawer__head">
    <div class="drawer__title" id="drawerTitle">Add Product</div>
    <button class="drawer__close" id="drawerClose"><i class="fas fa-times"></i></button>
  </div>

  <form class="drawer__body" method="POST" enctype="multipart/form-data" id="productForm">
    <input type="hidden" name="action" id="formAction" value="add">
    <input type="hidden" name="id"     id="formId"     value="">

    <!-- Basic Info -->
    <div class="pf-section">Basic Information</div>
    <div class="pf-grid">
      <div class="pf-field pf-full">
        <label>Product Name <span>*</span></label>
        <input type="text" name="name" id="fName" placeholder="e.g. Royal Blue Sapphire 3.5ct" required>
      </div>
      <div class="pf-field">
        <label>SKU</label>
        <input type="text" name="sku" id="fSku" placeholder="e.g. MCG-001">
      </div>
      <div class="pf-field">
        <label>Category</label>
        <select name="category_id" id="fCategory">
          <option value="">— Select Category —</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="pf-grid" style="margin-top:14px;">
      <div class="pf-field pf-full">
        <label>Short Description</label>
        <input type="text" name="short_desc" id="fShortDesc" placeholder="One-line summary shown on product cards">
      </div>
      <div class="pf-field pf-full">
        <label>Full Description</label>
        <textarea name="description" id="fDesc" rows="4" placeholder="Detailed product description…"></textarea>
      </div>
    </div>

    <!-- Gemstone Details -->
    <div class="pf-section">Gemstone Details</div>
    <div class="pf-grid">
      <div class="pf-field">
        <label>Gemstone Type</label>
        <input type="text" name="gemstone_type" id="fGemType" placeholder="e.g. Blue Sapphire">
      </div>
      <div class="pf-field">
        <label>Origin</label>
        <input type="text" name="origin" id="fOrigin" placeholder="e.g. Ceylon (Sri Lanka)">
      </div>
      <div class="pf-field">
        <label>Weight (carats)</label>
        <input type="number" name="weight_ct" id="fWeight" step="0.001" min="0" placeholder="3.500">
      </div>
      <div class="pf-field">
        <label>Dimensions (mm)</label>
        <input type="text" name="dimensions" id="fDimensions" placeholder="8.2 x 6.1 x 4.3">
      </div>
      <div class="pf-field">
        <label>Colour</label>
        <input type="text" name="colour" id="fColour" placeholder="e.g. Royal Blue">
      </div>
      <div class="pf-field">
        <label>Clarity</label>
        <input type="text" name="clarity" id="fClarity" placeholder="e.g. Eye Clean">
      </div>
      <div class="pf-field">
        <label>Cut</label>
        <input type="text" name="cut" id="fCut" placeholder="e.g. Oval Mixed Cut">
      </div>
      <div class="pf-field">
        <label>Treatment</label>
        <input type="text" name="treatment" id="fTreatment" placeholder="e.g. No Heat">
      </div>
      <div class="pf-field pf-full">
        <label>Certification</label>
        <input type="text" name="certification" id="fCert" placeholder="e.g. GIA Report #12345678">
      </div>
    </div>

    <!-- Pricing & Inventory -->
    <div class="pf-section">Pricing & Inventory</div>
    <div class="pf-grid pf-grid--3">
      <div class="pf-field">
        <label>Price (USD) <span>*</span></label>
        <input type="number" name="price_usd" id="fPrice" step="0.01" min="0" placeholder="0.00" required>
      </div>
      <div class="pf-field">
        <label>Compare Price (USD)</label>
        <input type="number" name="compare_price" id="fCompare" step="0.01" min="0" placeholder="0.00">
      </div>
      <div class="pf-field">
        <label>Stock Qty</label>
        <input type="number" name="stock" id="fStock" min="0" value="1" placeholder="1">
      </div>
    </div>

    <!-- Image Upload -->
    <div class="pf-section">Product Image</div>
    <div class="img-upload" id="imgUploadArea">
      <input type="file" name="image_main" id="fImage" accept="image/jpeg,image/png,image/webp"
             onchange="previewImage(this)">
      <div class="img-upload__icon"><i class="fas fa-cloud-upload-alt"></i></div>
      <div class="img-upload__text">Click to upload (JPG, PNG, WebP · max 5 MB)</div>
      <img src="" alt="" class="img-preview" id="imgPreview">
    </div>
    <div id="currentImgWrap" style="margin-top:10px;display:none;">
      <div style="font-size:11px;color:var(--text-soft);margin-bottom:6px;">Current image (upload new to replace):</div>
      <img src="" alt="" id="currentImg" style="max-height:100px;border-radius:7px;border:1px solid var(--dark-border);">
    </div>

    <!-- Visibility -->
    <div class="pf-section">Visibility</div>
    <div style="display:flex;gap:24px;flex-wrap:wrap;">
      <label class="pf-toggle">
        <input type="checkbox" name="is_active" id="fActive" checked>
        <span class="pf-toggle__track"></span>
        Active (visible in shop)
      </label>
      <label class="pf-toggle">
        <input type="checkbox" name="is_featured" id="fFeatured">
        <span class="pf-toggle__track"></span>
        Featured on homepage
      </label>
    </div>

  </form><!-- end form body -->

  <div class="drawer__footer">
    <button class="btn-admin btn-admin--outline" id="drawerCancelBtn">Cancel</button>
    <button class="btn-admin btn-admin--primary" id="drawerSaveBtn">
      <i class="fas fa-save"></i> Save Product
    </button>
  </div>
</div>


<!-- Delete Confirmation Modal -->
<div class="del-modal" id="delModal">
  <div class="del-modal__box">
    <div class="del-modal__icon"><i class="fas fa-trash-alt"></i></div>
    <div class="del-modal__title">Delete Product?</div>
    <div class="del-modal__sub" id="delModalSub">This action cannot be undone.</div>
    <form method="POST" id="delForm">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id"     id="delId">
      <div class="del-modal__btns">
        <button type="button" class="btn-admin btn-admin--outline" onclick="closeDelModal()">Cancel</button>
        <button type="submit" class="btn-admin btn-admin--danger"><i class="fas fa-trash"></i> Delete</button>
      </div>
    </form>
  </div>
</div>


<script src="assets/js/admin.js"></script>
<script>
// Product data for edit (PHP → JS) 
const PRODUCTS_DATA = <?= json_encode(array_column($products, null, 'id')) ?>;

// Drawer open/close 
function openDrawer() {
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById('productDrawer').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('drawerOverlay').classList.remove('open');
  document.getElementById('productDrawer').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('btnAddProduct').addEventListener('click', function () {
  resetForm();
  document.getElementById('drawerTitle').textContent = 'Add Product';
  document.getElementById('formAction').value = 'add';
  document.getElementById('formId').value = '';
  openDrawer();
});
document.getElementById('drawerClose').addEventListener('click', closeDrawer);
document.getElementById('drawerCancelBtn').addEventListener('click', closeDrawer);
document.getElementById('drawerOverlay').addEventListener('click', closeDrawer);

document.getElementById('drawerSaveBtn').addEventListener('click', function () {
  document.getElementById('productForm').submit();
});

// Reset form 
function resetForm() {
  const f = document.getElementById('productForm');
  f.reset();
  document.getElementById('imgPreview').classList.remove('show');
  document.getElementById('imgPreview').src = '';
  document.getElementById('currentImgWrap').style.display = 'none';
  document.getElementById('fActive').checked = true;
  document.getElementById('fFeatured').checked = false;
}

// Open edit drawer 
function openEdit(id) {
  const p = PRODUCTS_DATA[id];
  if (!p) return;

  resetForm();
  document.getElementById('drawerTitle').textContent = 'Edit Product';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('formId').value    = id;

  const set = (el, val) => { const e = document.getElementById(el); if (e) e.value = val ?? ''; };
  set('fName',      p.name);
  set('fSku',       p.sku);
  set('fCategory',  p.category_id);
  set('fShortDesc', p.short_desc);
  set('fDesc',      p.description);
  set('fGemType',   p.gemstone_type);
  set('fOrigin',    p.origin);
  set('fWeight',    p.weight_ct);
  set('fDimensions',p.dimensions);
  set('fColour',    p.colour);
  set('fClarity',   p.clarity);
  set('fCut',       p.cut);
  set('fTreatment', p.treatment);
  set('fCert',      p.certification);
  set('fPrice',     p.price_usd);
  set('fCompare',   p.compare_price);
  set('fStock',     p.stock);

  document.getElementById('fActive').checked   = p.is_active  == 1;
  document.getElementById('fFeatured').checked = p.is_featured == 1;

  if (p.image_main) {
    const wrap = document.getElementById('currentImgWrap');
    const img  = document.getElementById('currentImg');
    img.src = '../' + p.image_main;
    wrap.style.display = 'block';
  }

  openDrawer();
}

// Image preview 
function previewImage(input) {
  const preview = document.getElementById('imgPreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.classList.add('show'); };
    reader.readAsDataURL(input.files[0]);
  }
}

// Delete modal 
function confirmDelete(id, name) {
  document.getElementById('delId').value = id;
  document.getElementById('delModalSub').textContent = 'Delete "' + name + '"? This cannot be undone.';
  document.getElementById('delModal').classList.add('open');
}
function closeDelModal() {
  document.getElementById('delModal').classList.remove('open');
}
document.getElementById('delModal').addEventListener('click', function(e) {
  if (e.target === this) closeDelModal();
});

// Auto-open drawer if edit param set 
<?php if ($editProduct): ?>
openEdit(<?= $editProduct['id'] ?>);
<?php endif; ?>
</script>
</body>
</html>
