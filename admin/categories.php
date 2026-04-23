<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/activity.php';

$pageTitle  = 'Categories';
$activePage = 'categories';

$flash     = '';
$flashType = 'success';

function catSlugify(string $t): string {
    $t = strtolower(trim($t));
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim($t, '-');
}

function catUniqueSlug(PDO $db, string $base, ?int $excludeId = null): string {
    $slug = catSlugify($base);
    $orig = $slug; $i = 1;
    while (true) {
        $sql  = 'SELECT COUNT(*) FROM categories WHERE slug = ?';
        $args = [$slug];
        if ($excludeId) { $sql .= ' AND id != ?'; $args[] = $excludeId; }
        $st = $db->prepare($sql); $st->execute($args);
        if ((int)$st->fetchColumn() === 0) break;
        $slug = $orig . '-' . $i++;
    }
    return $slug;
}

function handleCatImage(): ?string {
    if (empty($_FILES['image']['name'])) return null;
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/webp'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $ext  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];
    $dir  = __DIR__ . '/../assets/images/categories/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('cat_', true) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . $name);
    return 'assets/images/categories/' . $name;
}

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            $flash = 'Category deleted.';
        }
        header('Location: categories.php?flash=' . urlencode($flash) . '&ft=success'); exit;
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id'] ?? 0);
        $col = $_POST['col'] ?? 'is_active';
        if (!in_array($col, ['is_active','show_on_home'], true)) $col = 'is_active';
        $val = (int)($_POST['val'] ?? 0);
        if ($id) $db->prepare("UPDATE categories SET `$col` = ? WHERE id = ?")->execute([$val, $id]);
        header('Location: categories.php'); exit;
    }

    if (in_array($action, ['add','edit'], true)) {
        $id       = $action === 'edit' ? (int)($_POST['id'] ?? 0) : null;
        $name     = trim($_POST['name']     ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $slug     = trim($_POST['slug']     ?? '') ?: catSlugify($name);
        $slug     = catUniqueSlug($db, $slug, $id);
        $desc     = trim($_POST['description'] ?? '');
        $sort     = (int)($_POST['sort_order'] ?? 0);
        $home     = isset($_POST['show_on_home']) ? 1 : 0;
        $active   = isset($_POST['is_active'])    ? 1 : 0;

        $imgPath = handleCatImage();

        if (!$name) {
            $flash = 'Category name is required.'; $flashType = 'error';
        } else {
            if ($action === 'add') {
                $currentImg = '';
                $st = $db->prepare(
                    'INSERT INTO categories (name, slug, subtitle, description, image, sort_order, show_on_home, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $st->execute([$name, $slug, $subtitle, $desc, $imgPath ?? '', $sort, $home, $active]);
                log_activity('category', "Category \"$name\" added.");
                $flash = 'Category added successfully.';
            } else {
                $row = $db->prepare('SELECT image FROM categories WHERE id = ?');
                $row->execute([$id]);
                $currentImg = $row->fetchColumn() ?: '';
                $finalImg   = $imgPath ?? $currentImg;
                $st = $db->prepare(
                    'UPDATE categories SET name=?, slug=?, subtitle=?, description=?, image=?, sort_order=?, show_on_home=?, is_active=? WHERE id=?'
                );
                $st->execute([$name, $slug, $subtitle, $desc, $finalImg, $sort, $home, $active, $id]);
                log_activity('category', "Category \"$name\" updated.");
                $flash = 'Category updated successfully.';
            }
        }
        header('Location: categories.php?flash=' . urlencode($flash) . '&ft=' . $flashType); exit;
    }
}

// Flash from redirect
if (isset($_GET['flash'])) {
    $flash     = htmlspecialchars($_GET['flash']);
    $flashType = $_GET['ft'] ?? 'success';
}

// Load all categories
$categories = $db->query(
    'SELECT * FROM categories ORDER BY sort_order ASC, name ASC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Categories | Mirabella Ceylon Admin</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/admin.css" />
  <style>
    .drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:900;opacity:0;pointer-events:none;transition:opacity .3s;}
    .drawer-overlay.open{opacity:1;pointer-events:all;}
    .drawer{position:fixed;top:0;right:0;height:100%;width:min(560px,100%);background:var(--dark-card);border-left:1px solid var(--dark-border);z-index:901;transform:translateX(100%);transition:transform .3s var(--ease);display:flex;flex-direction:column;overflow:hidden;}
    .drawer.open{transform:translateX(0);}
    .drawer__head{padding:20px 26px;border-bottom:1px solid var(--dark-border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
    .drawer__title{font-family:var(--font-display);font-size:18px;font-weight:600;color:var(--text);}
    .drawer__close{width:34px;height:34px;border:none;background:none;color:var(--text-soft);cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:.2s;}
    .drawer__close:hover{background:rgba(255,255,255,.06);color:var(--text);}
    .drawer form{flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;}
    .drawer__body{flex:1;overflow-y:auto;padding:24px 26px;min-height:0;}
    .drawer__footer{padding:16px 26px;border-top:1px solid var(--dark-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;background:var(--dark-card);}

    .pf-section{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--dark-border);}
    .pf-section:first-child{margin-top:0;}
    .pf-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .pf-full{grid-column:1/-1;}
    .pf-field{display:flex;flex-direction:column;gap:6px;}
    .pf-field label{font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-mid);}
    .pf-field label span{color:var(--danger);margin-left:2px;}
    .pf-field input,.pf-field select,.pf-field textarea{background:var(--dark-3);border:1px solid var(--dark-border);border-radius:7px;color:var(--text);font-family:var(--font-body);font-size:13px;padding:9px 12px;transition:.2s;width:100%;}
    .pf-field input:focus,.pf-field select:focus,.pf-field textarea:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-glow);}
    .pf-field textarea{resize:vertical;min-height:80px;}
    .pf-field input[type="file"]{padding:6px 10px;cursor:pointer;}
    .pf-hint{font-size:11px;color:var(--text-soft);margin-top:3px;}

    .pf-toggle{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text-mid);cursor:pointer;}
    .pf-toggle input{display:none;}
    .pf-toggle__track{width:36px;height:20px;background:var(--dark-border);border-radius:20px;position:relative;transition:.2s;flex-shrink:0;}
    .pf-toggle__track::after{content:'';position:absolute;top:3px;left:3px;width:14px;height:14px;background:#fff;border-radius:50%;transition:.2s;}
    .pf-toggle input:checked + .pf-toggle__track{background:var(--gold);}
    .pf-toggle input:checked + .pf-toggle__track::after{left:19px;}

    .cat-img-preview{width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid var(--dark-border);}
    .cat-img-placeholder{width:80px;height:60px;border-radius:6px;border:1px solid var(--dark-border);background:var(--dark-3);display:flex;align-items:center;justify-content:center;color:var(--text-soft);font-size:20px;}

    .badge-home{background:rgba(168,123,63,.18);color:var(--gold);font-size:10px;font-weight:700;letter-spacing:.6px;padding:2px 8px;border-radius:20px;text-transform:uppercase;}

    .page-toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
    .page-toolbar__left{display:flex;align-items:center;gap:12px;}
    .page-toolbar__title{font-family:var(--font-display);font-size:22px;font-weight:600;color:var(--text);}
    .page-toolbar__count{font-size:12px;color:var(--text-soft);background:var(--dark-3);padding:3px 10px;border-radius:20px;}

    .action-btn{border:none;cursor:pointer;border-radius:7px;font-family:var(--font-body);font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;transition:.2s;}
    .action-btn--primary{background:var(--gold);color:#0d0d0d;}
    .action-btn--primary:hover{background:var(--gold-light);}
    .action-btn--ghost{background:transparent;color:var(--text-mid);border:1px solid var(--dark-border);}
    .action-btn--ghost:hover{border-color:var(--gold);color:var(--gold);}
    .action-btn--danger{background:rgba(224,85,85,.12);color:#e05555;}
    .action-btn--danger:hover{background:rgba(224,85,85,.22);}
    .action-btn--sm{padding:5px 10px;font-size:11px;}
  </style>
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="admin-main">
  <div class="admin-content">

    <?php if ($flash): ?>
    <div class="admin-alert admin-alert--<?= $flashType === 'error' ? 'danger' : 'success' ?>" style="margin-bottom:20px;">
      <i class="fas fa-<?= $flashType === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
      <?= $flash ?>
    </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="page-toolbar">
      <div class="page-toolbar__left">
        <div class="page-toolbar__title">Categories</div>
        <span class="page-toolbar__count"><?= count($categories) ?> total</span>
      </div>
      <button class="action-btn action-btn--primary" id="btnAddCat">
        <i class="fas fa-plus"></i> Add Category
      </button>
    </div>

    <!-- Table -->
    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:50px;">Sort</th>
              <th style="width:80px;">Image</th>
              <th>Name</th>
              <th>Subtitle</th>
              <th>Slug</th>
              <th style="width:90px;text-align:center;">On Home</th>
              <th style="width:80px;text-align:center;">Active</th>
              <th style="width:120px;text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($categories)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--text-soft);padding:40px;">No categories yet.</td></tr>
          <?php else: ?>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td style="color:var(--text-soft);font-size:13px;"><?= (int)$cat['sort_order'] ?></td>
              <td>
                <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/../' . $cat['image'])): ?>
                  <img src="../<?= htmlspecialchars($cat['image']) ?>" class="cat-img-preview" alt="">
                <?php else: ?>
                  <div class="cat-img-placeholder"><i class="fas fa-image"></i></div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars($cat['name']) ?></div>
              </td>
              <td style="color:var(--text-soft);font-size:13px;"><?= htmlspecialchars($cat['subtitle'] ?? '') ?></td>
              <td><code style="font-size:11px;color:var(--text-mid);"><?= htmlspecialchars($cat['slug']) ?></code></td>
              <td style="text-align:center;">
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id"  value="<?= $cat['id'] ?>">
                  <input type="hidden" name="col" value="show_on_home">
                  <input type="hidden" name="val" value="<?= ($cat['show_on_home'] ?? 0) ? 0 : 1 ?>">
                  <button type="submit" style="border:none;background:none;cursor:pointer;padding:0;" title="Toggle Homepage">
                    <?php if ($cat['show_on_home'] ?? 0): ?>
                      <span class="badge-home">Yes</span>
                    <?php else: ?>
                      <span style="font-size:11px;color:var(--text-soft);">—</span>
                    <?php endif; ?>
                  </button>
                </form>
              </td>
              <td style="text-align:center;">
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id"  value="<?= $cat['id'] ?>">
                  <input type="hidden" name="col" value="is_active">
                  <input type="hidden" name="val" value="<?= $cat['is_active'] ? 0 : 1 ?>">
                  <button type="submit" style="border:none;background:none;cursor:pointer;padding:0;" title="Toggle Active">
                    <?php if ($cat['is_active']): ?>
                      <span style="color:#4caf87;font-size:11px;font-weight:700;">Active</span>
                    <?php else: ?>
                      <span style="color:var(--text-soft);font-size:11px;">Off</span>
                    <?php endif; ?>
                  </button>
                </form>
              </td>
              <td style="text-align:center;">
                <div style="display:flex;gap:6px;justify-content:center;">
                  <button class="action-btn action-btn--ghost action-btn--sm btn-edit-cat"
                    data-id="<?= $cat['id'] ?>"
                    data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                    data-subtitle="<?= htmlspecialchars($cat['subtitle'] ?? '', ENT_QUOTES) ?>"
                    data-slug="<?= htmlspecialchars($cat['slug'], ENT_QUOTES) ?>"
                    data-description="<?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES) ?>"
                    data-image="<?= htmlspecialchars($cat['image'] ?? '', ENT_QUOTES) ?>"
                    data-sort="<?= (int)($cat['sort_order'] ?? 0) ?>"
                    data-home="<?= (int)($cat['show_on_home'] ?? 0) ?>"
                    data-active="<?= (int)$cat['is_active'] ?>">
                    <i class="fas fa-pen"></i>
                  </button>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Delete this category? Products in it will lose their category.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="action-btn action-btn--danger action-btn--sm">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Drawer overlay -->
<div class="drawer-overlay" id="drawerOverlay"></div>

<!-- Add / Edit Drawer -->
<div class="drawer" id="catDrawer">
  <div class="drawer__head">
    <div class="drawer__title" id="drawerTitle">Add Category</div>
    <button class="drawer__close" id="drawerClose"><i class="fas fa-times"></i></button>
  </div>
  <form method="post" enctype="multipart/form-data" id="catForm">
    <input type="hidden" name="action" id="fAction" value="add">
    <input type="hidden" name="id"     id="fId"     value="">
    <div class="drawer__body">

      <div class="pf-section">Basic Info</div>
      <div class="pf-grid">
        <div class="pf-field pf-full">
          <label>Name <span>*</span></label>
          <input type="text" name="name" id="fName" placeholder="e.g. Blue Sapphire" required>
        </div>
        <div class="pf-field pf-full">
          <label>Subtitle</label>
          <input type="text" name="subtitle" id="fSubtitle" placeholder="e.g. Ceylon's Finest" maxlength="120">
          <div class="pf-hint">Shown below category name on the homepage collection cards.</div>
        </div>
        <div class="pf-field pf-full">
          <label>Slug</label>
          <input type="text" name="slug" id="fSlug" placeholder="auto-generated from name">
          <div class="pf-hint">URL-friendly identifier. Leave blank to auto-generate.</div>
        </div>
        <div class="pf-field pf-full">
          <label>Description</label>
          <textarea name="description" id="fDescription" placeholder="Short description…"></textarea>
        </div>
      </div>

      <div class="pf-section">Image</div>
      <div class="pf-field">
        <label>Category Image</label>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:8px;">
          <div id="imgPreviewWrap" style="display:none;">
            <img id="imgPreview" class="cat-img-preview" src="" alt="">
          </div>
          <div id="imgPlaceholder" class="cat-img-placeholder"><i class="fas fa-image"></i></div>
        </div>
        <input type="file" name="image" id="fImage" accept="image/jpeg,image/png,image/webp">
        <div class="pf-hint">JPG, PNG or WebP · max 5 MB. Leave blank to keep existing image.</div>
      </div>

      <div class="pf-section">Display Settings</div>
      <div class="pf-grid">
        <div class="pf-field">
          <label>Sort Order</label>
          <input type="number" name="sort_order" id="fSort" value="0" min="0">
          <div class="pf-hint">Lower numbers appear first.</div>
        </div>
        <div class="pf-field" style="justify-content:flex-end;padding-bottom:4px;">
          <label style="margin-bottom:8px;">&nbsp;</label>
          <label class="pf-toggle">
            <input type="checkbox" name="show_on_home" id="fHome" value="1">
            <span class="pf-toggle__track"></span>
            Show on Homepage Collections
          </label>
          <label class="pf-toggle" style="margin-top:10px;">
            <input type="checkbox" name="is_active" id="fActive" value="1" checked>
            <span class="pf-toggle__track"></span>
            Active (visible in shop)
          </label>
        </div>
      </div>

    </div>
    <div class="drawer__footer">
      <button type="button" class="action-btn action-btn--ghost" id="drawerCancel">Cancel</button>
      <button type="submit" class="action-btn action-btn--primary">
        <i class="fas fa-save"></i> <span id="drawerSaveLabel">Save Category</span>
      </button>
    </div>
  </form>
</div>

<script>
(function () {
  const overlay   = document.getElementById('drawerOverlay');
  const drawer    = document.getElementById('catDrawer');
  const titleEl   = document.getElementById('drawerTitle');
  const saveLabel = document.getElementById('drawerSaveLabel');
  const closeBtn  = document.getElementById('drawerClose');
  const cancelBtn = document.getElementById('drawerCancel');

  function openDrawer() {
    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer() {
    overlay.classList.remove('open');
    drawer.classList.remove('open');
    document.body.style.overflow = '';
  }

  closeBtn.addEventListener('click', closeDrawer);
  cancelBtn.addEventListener('click', closeDrawer);
  overlay.addEventListener('click', closeDrawer);

  // Add
  document.getElementById('btnAddCat').addEventListener('click', function () {
    document.getElementById('fAction').value   = 'add';
    document.getElementById('fId').value       = '';
    document.getElementById('fName').value     = '';
    document.getElementById('fSubtitle').value = '';
    document.getElementById('fSlug').value     = '';
    document.getElementById('fDescription').value = '';
    document.getElementById('fSort').value     = '0';
    document.getElementById('fHome').checked   = false;
    document.getElementById('fActive').checked = true;
    document.getElementById('fImage').value    = '';
    document.getElementById('imgPreviewWrap').style.display = 'none';
    document.getElementById('imgPlaceholder').style.display = 'flex';
    titleEl.textContent   = 'Add Category';
    saveLabel.textContent = 'Save Category';
    openDrawer();
  });

  // Edit
  document.querySelectorAll('.btn-edit-cat').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const d = btn.dataset;
      document.getElementById('fAction').value      = 'edit';
      document.getElementById('fId').value          = d.id;
      document.getElementById('fName').value        = d.name;
      document.getElementById('fSubtitle').value    = d.subtitle;
      document.getElementById('fSlug').value        = d.slug;
      document.getElementById('fDescription').value = d.description;
      document.getElementById('fSort').value        = d.sort;
      document.getElementById('fHome').checked      = d.home === '1';
      document.getElementById('fActive').checked    = d.active === '1';
      document.getElementById('fImage').value       = '';
      const imgWrap = document.getElementById('imgPreviewWrap');
      const imgPlaceholder = document.getElementById('imgPlaceholder');
      if (d.image) {
        document.getElementById('imgPreview').src = '../' + d.image;
        imgWrap.style.display = 'block';
        imgPlaceholder.style.display = 'none';
      } else {
        imgWrap.style.display = 'none';
        imgPlaceholder.style.display = 'flex';
      }
      titleEl.textContent   = 'Edit Category';
      saveLabel.textContent = 'Update Category';
      openDrawer();
    });
  });

  // Live image preview
  document.getElementById('fImage').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById('imgPreview').src = e.target.result;
      document.getElementById('imgPreviewWrap').style.display = 'block';
      document.getElementById('imgPlaceholder').style.display = 'none';
    };
    reader.readAsDataURL(file);
  });

  // Auto-slug from name
  document.getElementById('fName').addEventListener('input', function () {
    const slugField = document.getElementById('fSlug');
    if (slugField.dataset.manual) return;
    slugField.value = this.value.toLowerCase().trim()
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  });
  document.getElementById('fSlug').addEventListener('input', function () {
    this.dataset.manual = this.value ? '1' : '';
  });
})();
</script>

</body>
</html>
