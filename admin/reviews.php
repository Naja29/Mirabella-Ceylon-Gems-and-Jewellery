<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/activity.php';

$pageTitle  = 'Reviews';
$activePage = 'reviews';

$db        = db();
$flash     = '';
$flashType = 'success';

// Process POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare('UPDATE reviews SET is_approved = 1 WHERE id = ?')->execute([$id]);
            log_activity('review', "Review #$id approved and published.");
        }
        $flash = 'Review approved and published.';
        header('Location: reviews.php?flash=' . urlencode($flash) . '&ft=success');
        exit;
    }

    if ($action === 'unapprove') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare('UPDATE reviews SET is_approved = 0 WHERE id = ?')->execute([$id]);
            log_activity('review', "Review #$id unpublished.");
        }
        $flash = 'Review unpublished.';
        header('Location: reviews.php?flash=' . urlencode($flash) . '&ft=success');
        exit;
    }

    if ($action === 'approve_all') {
        $db->query('UPDATE reviews SET is_approved = 1 WHERE is_approved = 0');
        log_activity('review', 'All pending reviews approved in bulk.');
        $flash = 'All pending reviews approved.';
        header('Location: reviews.php?flash=' . urlencode($flash) . '&ft=success');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
            log_activity('review', "Review #$id deleted.");
        }
        $flash = 'Review deleted.';
        header('Location: reviews.php?flash=' . urlencode($flash) . '&ft=success');
        exit;
    }
}

// Flash from redirect 
if (!$flash && isset($_GET['flash'])) {
    $flash     = htmlspecialchars($_GET['flash']);
    $flashType = $_GET['ft'] ?? 'success';
}

// Filters 
$search    = trim($_GET['q']       ?? '');
$filterSt  = trim($_GET['status']  ?? '');
$filterRat = (int)($_GET['rating'] ?? 0);

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(r.reviewer_name LIKE ? OR r.title LIKE ? OR r.body LIKE ? OR p.name LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterSt === 'approved') { $where[] = 'r.is_approved = 1'; }
if ($filterSt === 'pending')  { $where[] = 'r.is_approved = 0'; }
if ($filterRat)               { $where[] = 'r.rating = ?'; $params[] = $filterRat; }

$whereStr = implode(' AND ', $where);

$reviews = $db->prepare("
    SELECT r.*, p.name AS product_name, p.id AS pid
    FROM reviews r
    LEFT JOIN products p ON p.id = r.product_id
    WHERE $whereStr
    ORDER BY r.is_approved ASC, r.created_at DESC
");
$reviews->execute($params);
$reviews = $reviews->fetchAll();

// Stats 
$stats = [
    'total'    => (int)$db->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
    'pending'  => (int)$db->query('SELECT COUNT(*) FROM reviews WHERE is_approved = 0')->fetchColumn(),
    'approved' => (int)$db->query('SELECT COUNT(*) FROM reviews WHERE is_approved = 1')->fetchColumn(),
    'avg'      => (float)$db->query('SELECT COALESCE(AVG(rating),0) FROM reviews WHERE is_approved = 1')->fetchColumn(),
];

// Stars helper 
function stars(int $n, bool $small = false): string {
    $size = $small ? 'font-size:11px;' : 'font-size:13px;';
    $out  = '<span style="' . $size . 'letter-spacing:1px;">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $n
            ? '<i class="fas fa-star" style="color:#C9A84C;"></i>'
            : '<i class="far fa-star" style="color:rgba(201,168,76,.3);"></i>';
    }
    return $out . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Reviews | Mirabella Ceylon Admin</title>
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
    .drawer{position:fixed;top:0;right:0;height:100%;width:min(580px,100%);background:var(--dark-card);border-left:1px solid var(--dark-border);z-index:901;transform:translateX(100%);transition:transform .3s var(--ease);display:flex;flex-direction:column;overflow:hidden;}
    .drawer.open{transform:translateX(0);}
    .drawer__head{padding:20px 26px;border-bottom:1px solid var(--dark-border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
    .drawer__title{font-family:var(--font-display);font-size:18px;font-weight:600;color:var(--text);}
    .drawer__close{width:34px;height:34px;border:none;background:none;color:var(--text-soft);cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:.2s;}
    .drawer__close:hover{background:rgba(255,255,255,.06);color:var(--text);}
    .drawer__body{flex:1;overflow-y:auto;padding:24px 26px;}
    .drawer__footer{padding:16px 26px;border-top:1px solid var(--dark-border);display:flex;gap:10px;flex-shrink:0;background:var(--dark-card);}

    /* Review detail */
    .rev-header{display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;}
    .rev-avatar{width:48px;height:48px;border-radius:50%;background:var(--gold-pale);border:2px solid var(--gold-glow);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--gold);flex-shrink:0;}
    .rev-header__name{font-weight:700;font-size:15px;color:var(--text);}
    .rev-header__meta{font-size:12px;color:var(--text-soft);margin-top:3px;}
    .rev-product{background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;padding:12px 14px;display:flex;align-items:center;gap:10px;margin-bottom:16px;}
    .rev-product i{color:var(--gold);}
    .rev-product__name{font-size:13px;color:var(--text);font-weight:600;}
    .rev-title{font-family:var(--font-display);font-size:16px;font-weight:600;color:var(--text);margin:14px 0 10px;}
    .rev-body{font-size:14px;color:var(--text-mid);line-height:1.8;background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;padding:16px 18px;}

    /* Rating bar */
    .rating-bar{display:flex;align-items:center;gap:10px;margin-bottom:6px;}
    .rating-bar__label{font-size:12px;color:var(--text-soft);width:30px;text-align:right;}
    .rating-bar__track{flex:1;height:6px;background:var(--dark-3);border-radius:4px;overflow:hidden;}
    .rating-bar__fill{height:100%;background:var(--gold);border-radius:4px;transition:.4s;}
    .rating-bar__count{font-size:11px;color:var(--text-soft);width:24px;}

    /* Table row pending */
    .admin-table tbody tr.pending-row td{background:rgba(243,156,18,.03);}
    .admin-table tbody tr.pending-row td:first-child{border-left:3px solid var(--warning);}
    .rev-preview{font-size:12px;color:var(--text-soft);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

    /* Filter */
    .filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:20px;}
    .filter-bar input[type=search]{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:7px;color:var(--text);font-size:13px;padding:8px 12px 8px 36px;width:220px;transition:.2s;}
    .filter-bar input[type=search]:focus{outline:none;border-color:var(--gold);}
    .filter-search-wrap{position:relative;}
    .filter-search-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-soft);font-size:12px;pointer-events:none;}
    .filter-bar select{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:7px;color:var(--text-mid);font-size:12px;padding:8px 12px;cursor:pointer;}
    .filter-bar select:focus{outline:none;border-color:var(--gold);}
    .filter-bar select option{background:var(--dark-3);}

    /* Flash */
    .flash-bar{padding:12px 20px;border-radius:8px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
    .flash-bar--success{background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.25);color:#2ecc71;}
    .flash-bar--error{background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.25);color:#e74c3c;}

    /* Delete modal */
    .del-modal{position:fixed;inset:0;z-index:950;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);opacity:0;pointer-events:none;transition:.25s;}
    .del-modal.open{opacity:1;pointer-events:all;}
    .del-modal__box{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:12px;padding:32px 36px;max-width:380px;width:90%;text-align:center;}
    .del-modal__icon{font-size:32px;color:var(--danger);margin-bottom:14px;}
    .del-modal__title{font-family:var(--font-display);font-size:18px;color:var(--text);margin-bottom:8px;}
    .del-modal__sub{font-size:13px;color:var(--text-mid);margin-bottom:22px;}
    .del-modal__btns{display:flex;gap:10px;justify-content:center;}

    /* Avg rating display */
    .avg-rating{display:flex;align-items:center;gap:8px;margin-top:6px;}
    .avg-rating__num{font-family:var(--font-display);font-size:28px;font-weight:700;color:var(--text);line-height:1;}
    .avg-rating__stars{display:flex;flex-direction:column;gap:3px;}
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
          <div class="page-header__eyebrow">Feedback</div>
          <h1 class="page-header__title">
            Reviews
            <?php if ($stats['pending'] > 0): ?>
            <span style="font-size:14px;background:var(--warning);color:var(--dark);padding:2px 10px;border-radius:20px;font-family:var(--font-body);font-weight:700;vertical-align:middle;margin-left:8px;">
              <?= $stats['pending'] ?> pending
            </span>
            <?php endif; ?>
          </h1>
          <p class="page-header__sub">Moderate customer product reviews before publishing.</p>
        </div>
        <div class="page-header__actions">
          <?php if ($stats['pending'] > 0): ?>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="approve_all">
            <button type="submit" class="btn-admin btn-admin--outline">
              <i class="fas fa-check-double"></i> Approve All Pending
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Flash -->
      <?php if ($flash): ?>
      <div class="flash-bar flash-bar--<?= htmlspecialchars($flashType) ?>">
        <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $flash ?>
      </div>
      <?php endif; ?>

      <!-- Stats + Rating breakdown -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr) 280px;gap:20px;margin-bottom:24px;">

        <div class="stat-card stat-card--orange">
          <div class="stat-card__head">
            <div class="stat-card__label">Pending</div>
            <div class="stat-card__icon"><i class="fas fa-clock"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['pending']) ?></div>
          <div class="stat-card__change <?= $stats['pending'] > 0 ? 'stat-card__change--down' : 'stat-card__change--neutral' ?>">
            <i class="fas fa-<?= $stats['pending'] > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i>
            <span><?= $stats['pending'] > 0 ? 'Awaiting moderation' : 'All clear' ?></span>
          </div>
        </div>

        <div class="stat-card stat-card--green">
          <div class="stat-card__head">
            <div class="stat-card__label">Approved</div>
            <div class="stat-card__icon"><i class="fas fa-check-circle"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['approved']) ?></div>
        </div>

        <div class="stat-card stat-card--blue">
          <div class="stat-card__head">
            <div class="stat-card__label">Total Reviews</div>
            <div class="stat-card__icon"><i class="fas fa-star"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['total']) ?></div>
        </div>

        <!-- Rating Breakdown -->
        <div class="admin-card" style="margin:0;">
          <div class="admin-card__head" style="padding:14px 18px;">
            <div class="admin-card__title" style="font-size:13px;">
              <i class="fas fa-star"></i> Rating Breakdown
            </div>
          </div>
          <div style="padding:12px 18px;">
            <div class="avg-rating" style="margin-bottom:12px;">
              <div class="avg-rating__num"><?= number_format($stats['avg'], 1) ?></div>
              <div class="avg-rating__stars">
                <?= stars((int)round($stats['avg'])) ?>
                <div style="font-size:11px;color:var(--text-soft);margin-top:2px;"><?= $stats['approved'] ?> reviews</div>
              </div>
            </div>
            <?php
            for ($r = 5; $r >= 1; $r--):
                $cnt = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE is_approved=1 AND rating=$r")->fetchColumn();
                $pct = $stats['approved'] > 0 ? ($cnt / $stats['approved']) * 100 : 0;
            ?>
            <div class="rating-bar">
              <div class="rating-bar__label"><?= $r ?><i class="fas fa-star" style="color:var(--gold);font-size:9px;margin-left:2px;"></i></div>
              <div class="rating-bar__track"><div class="rating-bar__fill" style="width:<?= $pct ?>%;"></div></div>
              <div class="rating-bar__count"><?= $cnt ?></div>
            </div>
            <?php endfor; ?>
          </div>
        </div>

      </div>

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar">
        <div class="filter-search-wrap">
          <i class="fas fa-search"></i>
          <input type="search" name="q" placeholder="Search reviews…" value="<?= htmlspecialchars($search) ?>" />
        </div>
        <select name="status" onchange="this.form.submit()">
          <option value="">All Reviews</option>
          <option value="pending"  <?= $filterSt==='pending'  ? 'selected':'' ?>>Pending</option>
          <option value="approved" <?= $filterSt==='approved' ? 'selected':'' ?>>Approved</option>
        </select>
        <select name="rating" onchange="this.form.submit()">
          <option value="">All Ratings</option>
          <?php for ($r = 5; $r >= 1; $r--): ?>
          <option value="<?= $r ?>" <?= $filterRat === $r ? 'selected':'' ?>><?= $r ?> Star<?= $r != 1 ? 's' : '' ?></option>
          <?php endfor; ?>
        </select>
        <?php if ($search || $filterSt || $filterRat): ?>
        <a href="reviews.php" class="btn-admin btn-admin--ghost btn-admin--sm">
          <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
        <button type="submit" class="btn-admin btn-admin--outline btn-admin--sm">
          <i class="fas fa-search"></i> Search
        </button>
      </form>

      <!-- Reviews Table -->
      <div class="admin-card">
        <div class="admin-card__head">
          <div class="admin-card__title">
            <i class="fas fa-star"></i>
            <?= count($reviews) ?> Review<?= count($reviews) != 1 ? 's' : '' ?>
            <?= ($search || $filterSt || $filterRat) ? '<span style="font-weight:400;color:var(--text-soft);font-size:12px;"> (filtered)</span>' : '' ?>
          </div>
        </div>
        <div class="admin-table-wrap">
          <?php if (empty($reviews)): ?>
          <div class="admin-empty">
            <i class="fas fa-star"></i>
            <p>No reviews yet. They will appear here once customers submit product reviews.</p>
          </div>
          <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Reviewer</th>
                <th>Product</th>
                <th>Rating</th>
                <th>Review</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reviews as $rev): ?>
              <tr class="<?= !$rev['is_approved'] ? 'pending-row' : '' ?>" style="cursor:pointer;"
                  onclick="openReview(<?= $rev['id'] ?>)">
                <td onclick="event.stopPropagation();">
                  <strong><?= htmlspecialchars($rev['reviewer_name']) ?></strong>
                </td>
                <td>
                  <span style="font-size:12px;color:var(--text-mid);">
                    <?= htmlspecialchars($rev['product_name'] ?? '—') ?>
                  </span>
                </td>
                <td><?= stars($rev['rating'], true) ?></td>
                <td>
                  <?php if ($rev['title']): ?>
                  <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px;">
                    <?= htmlspecialchars($rev['title']) ?>
                  </div>
                  <?php endif; ?>
                  <div class="rev-preview"><?= htmlspecialchars($rev['body'] ?? '') ?></div>
                </td>
                <td style="font-size:12px;color:var(--text-soft);white-space:nowrap;" onclick="event.stopPropagation();">
                  <?= date('d M Y', strtotime($rev['created_at'])) ?>
                </td>
                <td onclick="event.stopPropagation();">
                  <?php if ($rev['is_approved']): ?>
                    <span class="badge badge--active">Published</span>
                  <?php else: ?>
                    <span class="badge badge--pending">Pending</span>
                  <?php endif; ?>
                </td>
                <td onclick="event.stopPropagation();">
                  <div style="display:flex;gap:6px;">
                    <?php if (!$rev['is_approved']): ?>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="approve">
                      <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                      <button type="submit" class="btn-admin btn-admin--ghost btn-admin--sm"
                              style="color:var(--success);" title="Approve">
                        <i class="fas fa-check"></i>
                      </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="unapprove">
                      <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                      <button type="submit" class="btn-admin btn-admin--ghost btn-admin--sm"
                              style="color:var(--warning);" title="Unpublish">
                        <i class="fas fa-eye-slash"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            onclick="openReview(<?= $rev['id'] ?>)" title="View">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            style="color:var(--danger);"
                            onclick="confirmDelete(<?= $rev['id'] ?>, '<?= htmlspecialchars(addslashes($rev['reviewer_name'])) ?>')"
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


<!-- REVIEW DETAIL DRAWER -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer" id="reviewDrawer">
  <div class="drawer__head">
    <div class="drawer__title" id="drawerTitle">Review</div>
    <button class="drawer__close" id="drawerClose"><i class="fas fa-times"></i></button>
  </div>
  <div class="drawer__body" id="drawerBody"></div>
  <div class="drawer__footer" id="drawerFooter">
    <form method="POST" style="display:inline;" id="approveForm">
      <input type="hidden" name="id" id="approveId">
      <input type="hidden" name="action" id="approveAction">
      <button type="submit" class="btn-admin btn-admin--primary" id="approveBtn">
        <i class="fas fa-check"></i> Approve
      </button>
    </form>
    <form method="POST" style="display:inline;">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="deleteId">
      <button type="submit" class="btn-admin btn-admin--danger" id="deleteBtn">
        <i class="fas fa-trash"></i> Delete
      </button>
    </form>
    <div style="flex:1;"></div>
    <button class="btn-admin btn-admin--outline" id="drawerCancelBtn">Close</button>
  </div>
</div>


<!-- Delete Confirmation Modal -->
<div class="del-modal" id="delModal">
  <div class="del-modal__box">
    <div class="del-modal__icon"><i class="fas fa-star"></i></div>
    <div class="del-modal__title">Delete Review?</div>
    <div class="del-modal__sub" id="delModalSub">This cannot be undone.</div>
    <form method="POST" id="delForm">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delId">
      <div class="del-modal__btns">
        <button type="button" class="btn-admin btn-admin--outline" onclick="closeDelModal()">Cancel</button>
        <button type="submit" class="btn-admin btn-admin--danger"><i class="fas fa-trash"></i> Delete</button>
      </div>
    </form>
  </div>
</div>


<!-- Reviews data for JS -->
<script>
const ALL_REVIEWS = <?= json_encode(array_column($reviews, null, 'id')) ?>;
</script>

<script src="assets/js/admin.js"></script>
<script>
// Drawer open/close 
function openDrawer() {
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById('reviewDrawer').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('drawerOverlay').classList.remove('open');
  document.getElementById('reviewDrawer').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('drawerClose').addEventListener('click', closeDrawer);
document.getElementById('drawerCancelBtn').addEventListener('click', closeDrawer);
document.getElementById('drawerOverlay').addEventListener('click', closeDrawer);

// Open review 
function openReview(id) {
  const r = ALL_REVIEWS[id];
  if (!r) return;

  const initials = r.reviewer_name.trim().split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();

  // Stars HTML
  let starsHtml = '';
  for (let i = 1; i <= 5; i++) {
    starsHtml += i <= r.rating
      ? '<i class="fas fa-star" style="color:#C9A84C;font-size:15px;"></i>'
      : '<i class="far fa-star" style="color:rgba(201,168,76,.3);font-size:15px;"></i>';
  }

  document.getElementById('drawerTitle').textContent = r.title || 'Review';

  // Footer buttons
  document.getElementById('approveId').value = id;
  document.getElementById('deleteId').value  = id;

  const approveBtn = document.getElementById('approveBtn');
  const approveAction = document.getElementById('approveAction');
  if (r.is_approved == 1) {
    approveAction.value = 'unapprove';
    approveBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Unpublish';
    approveBtn.className = 'btn-admin btn-admin--outline';
  } else {
    approveAction.value = 'approve';
    approveBtn.innerHTML = '<i class="fas fa-check"></i> Approve & Publish';
    approveBtn.className = 'btn-admin btn-admin--primary';
  }

  document.getElementById('drawerBody').innerHTML = `
    <div class="rev-header">
      <div class="rev-avatar">${escH(initials)}</div>
      <div>
        <div class="rev-header__name">${escH(r.reviewer_name)}</div>
        <div style="margin-top:5px;">${starsHtml}</div>
        <div class="rev-header__meta">
          <i class="fas fa-calendar" style="margin-right:4px;font-size:10px;"></i>${fmtDate(r.created_at)}
          &nbsp;·&nbsp;
          ${r.is_approved == 1
            ? '<span class="badge badge--active" style="font-size:10px;">Published</span>'
            : '<span class="badge badge--pending" style="font-size:10px;">Pending</span>'}
        </div>
      </div>
    </div>

    ${r.product_name ? `
    <div class="rev-product">
      <i class="fas fa-gem"></i>
      <div>
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--text-soft);margin-bottom:2px;">Product</div>
        <div class="rev-product__name">${escH(r.product_name)}</div>
      </div>
    </div>` : ''}

    ${r.title ? `<div class="rev-title">${escH(r.title)}</div>` : ''}
    ${r.body  ? `<div class="rev-body">${escH(r.body)}</div>`  : '<div style="color:var(--text-soft);font-size:13px;font-style:italic;">No written review.</div>'}
  `;

  openDrawer();
}

// Helpers 
function escH(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s.replace(' ','T'));
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
}

// Delete modal 
function confirmDelete(id, name) {
  document.getElementById('delId').value = id;
  document.getElementById('delModalSub').textContent = 'Delete review by "' + name + '"? This cannot be undone.';
  document.getElementById('delModal').classList.add('open');
}
function closeDelModal() {
  document.getElementById('delModal').classList.remove('open');
}
document.getElementById('delModal').addEventListener('click', function(e) {
  if (e.target === this) closeDelModal();
});
</script>
</body>
</html>
