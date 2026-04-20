<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle  = 'Customers';
$activePage = 'customers';

$db        = db();
$flash     = '';
$flashType = 'success';

// Process POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $id  = (int)($_POST['id']  ?? 0);
        $val = (int)($_POST['val'] ?? 0);
        if ($id) $db->prepare('UPDATE customers SET is_active = ? WHERE id = ?')->execute([$val, $id]);
        header('Location: customers.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $db->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
        $flash = 'Customer removed successfully.';
        header('Location: customers.php?flash=' . urlencode($flash) . '&ft=success');
        exit;
    }
}

// Flash from redirect 
if (!$flash && isset($_GET['flash'])) {
    $flash     = htmlspecialchars($_GET['flash']);
    $flashType = $_GET['ft'] ?? 'success';
}

// Filters 
$search   = trim($_GET['q']      ?? '');
$filterSt = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['from']   ?? '');
$dateTo   = trim($_GET['to']     ?? '');

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterSt === 'active')   { $where[] = 'c.is_active = 1'; }
if ($filterSt === 'inactive') { $where[] = 'c.is_active = 0'; }
if ($filterSt === 'verified') { $where[] = 'c.email_verified = 1'; }
if ($dateFrom) { $where[] = 'DATE(c.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(c.created_at) <= ?'; $params[] = $dateTo; }

$whereStr = implode(' AND ', $where);

$customers = $db->prepare("
    SELECT c.*,
           COUNT(o.id)             AS order_count,
           COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_usd ELSE 0 END), 0) AS total_spent
    FROM customers c
    LEFT JOIN orders o ON o.customer_id = c.id
    WHERE $whereStr
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$customers->execute($params);
$customers = $customers->fetchAll();

// Stats 
$stats = [
    'total'    => (int)$db->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
    'active'   => (int)$db->query('SELECT COUNT(*) FROM customers WHERE is_active = 1')->fetchColumn(),
    'verified' => (int)$db->query('SELECT COUNT(*) FROM customers WHERE email_verified = 1')->fetchColumn(),
    'new'      => (int)$db->query("SELECT COUNT(*) FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Customers | Mirabella Ceylon Admin</title>
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
    .drawer{position:fixed;top:0;right:0;height:100%;width:min(620px,100%);background:var(--dark-card);border-left:1px solid var(--dark-border);z-index:901;transform:translateX(100%);transition:transform .3s var(--ease);display:flex;flex-direction:column;overflow:hidden;}
    .drawer.open{transform:translateX(0);}
    .drawer__head{padding:20px 26px;border-bottom:1px solid var(--dark-border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
    .drawer__title{font-family:var(--font-display);font-size:18px;font-weight:600;color:var(--text);}
    .drawer__close{width:34px;height:34px;border:none;background:none;color:var(--text-soft);cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:.2s;}
    .drawer__close:hover{background:rgba(255,255,255,.06);color:var(--text);}
    .drawer__body{flex:1;overflow-y:auto;padding:24px 26px;}
    .drawer__footer{padding:16px 26px;border-top:1px solid var(--dark-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;background:var(--dark-card);}

    /* Customer profile header */
    .cust-profile{display:flex;align-items:center;gap:16px;margin-bottom:20px;}
    .cust-avatar{width:56px;height:56px;border-radius:50%;background:var(--gold-pale);border:2px solid var(--gold-glow);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:22px;font-weight:700;color:var(--gold);flex-shrink:0;}
    .cust-profile__name{font-family:var(--font-display);font-size:18px;font-weight:600;color:var(--text);}
    .cust-profile__email{font-size:13px;color:var(--text-mid);margin-top:2px;}
    .cust-profile__badges{display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;}

    /* Detail layout */
    .od-section{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--dark-border);}
    .od-section:first-child{margin-top:0;}
    .od-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .od-row{display:flex;flex-direction:column;gap:4px;}
    .od-row__label{font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-soft);}
    .od-row__val{font-size:13px;color:var(--text);}

    /* Mini order table */
    .mini-table{width:100%;border-collapse:collapse;font-size:13px;}
    .mini-table th{padding:7px 10px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-soft);border-bottom:1px solid var(--dark-border);text-align:left;}
    .mini-table td{padding:9px 10px;color:var(--text-mid);border-bottom:1px solid rgba(255,255,255,.04);}

    /* Stat mini cards */
    .cust-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:4px;}
    .cust-stat{background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;padding:12px 14px;text-align:center;}
    .cust-stat__val{font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--text);}
    .cust-stat__label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--text-soft);margin-top:3px;}

    /* Filter bar */
    .filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:20px;}
    .filter-bar input[type=search],.filter-bar input[type=date]{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:7px;color:var(--text);font-size:13px;padding:8px 12px;transition:.2s;}
    .filter-bar input[type=search]{padding-left:36px;width:220px;}
    .filter-bar input[type=search]:focus,.filter-bar input[type=date]:focus{outline:none;border-color:var(--gold);}
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
          <div class="page-header__eyebrow">People</div>
          <h1 class="page-header__title">Customers</h1>
          <p class="page-header__sub">View and manage registered customer accounts.</p>
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
            <div class="stat-card__label">Total Customers</div>
            <div class="stat-card__icon"><i class="fas fa-users"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card stat-card--green">
          <div class="stat-card__head">
            <div class="stat-card__label">Active</div>
            <div class="stat-card__icon"><i class="fas fa-user-check"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['active']) ?></div>
        </div>
        <div class="stat-card stat-card--gold">
          <div class="stat-card__head">
            <div class="stat-card__label">Email Verified</div>
            <div class="stat-card__icon"><i class="fas fa-envelope-open-text"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['verified']) ?></div>
        </div>
        <div class="stat-card stat-card--orange">
          <div class="stat-card__head">
            <div class="stat-card__label">New (30 days)</div>
            <div class="stat-card__icon"><i class="fas fa-user-plus"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['new']) ?></div>
        </div>
      </div>

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar">
        <div class="filter-search-wrap">
          <i class="fas fa-search"></i>
          <input type="search" name="q" placeholder="Search customers…" value="<?= htmlspecialchars($search) ?>" />
        </div>
        <select name="status" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="active"   <?= $filterSt==='active'   ? 'selected':'' ?>>Active</option>
          <option value="inactive" <?= $filterSt==='inactive' ? 'selected':'' ?>>Inactive</option>
          <option value="verified" <?= $filterSt==='verified' ? 'selected':'' ?>>Email Verified</option>
        </select>
        <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" title="Joined from" onchange="this.form.submit()">
        <input type="date" name="to"   value="<?= htmlspecialchars($dateTo) ?>"   title="Joined to"   onchange="this.form.submit()">
        <?php if ($search || $filterSt || $dateFrom || $dateTo): ?>
        <a href="customers.php" class="btn-admin btn-admin--ghost btn-admin--sm">
          <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
        <button type="submit" class="btn-admin btn-admin--outline btn-admin--sm">
          <i class="fas fa-search"></i> Search
        </button>
      </form>

      <!-- Customers Table -->
      <div class="admin-card">
        <div class="admin-card__head">
          <div class="admin-card__title">
            <i class="fas fa-users"></i>
            <?= count($customers) ?> Customer<?= count($customers) != 1 ? 's' : '' ?>
            <?= ($search || $filterSt || $dateFrom || $dateTo) ? '<span style="font-weight:400;color:var(--text-soft);font-size:12px;"> (filtered)</span>' : '' ?>
          </div>
        </div>
        <div class="admin-table-wrap">
          <?php if (empty($customers)): ?>
          <div class="admin-empty">
            <i class="fas fa-users"></i>
            <p>No customers yet. They will appear here once customers register on the site.</p>
          </div>
          <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Country</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Joined</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customers as $c): ?>
              <?php $initials = strtoupper(substr($c['first_name'],0,1) . substr($c['last_name'],0,1)); ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--gold-pale);border:1px solid var(--gold-glow);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--gold);flex-shrink:0;">
                      <?= htmlspecialchars($initials) ?>
                    </div>
                    <div>
                      <strong><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></strong><br>
                      <span style="font-size:11px;color:var(--text-soft);"><?= htmlspecialchars($c['email']) ?></span>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($c['country'] ?: '—') ?></td>
                <td style="text-align:center;">
                  <strong><?= $c['order_count'] ?></strong>
                </td>
                <td><strong>$<?= number_format($c['total_spent'], 2) ?></strong></td>
                <td style="font-size:12px;color:var(--text-soft);">
                  <?= date('d M Y', strtotime($c['created_at'])) ?>
                </td>
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <?php if ($c['is_active']): ?>
                      <span class="badge badge--active">Active</span>
                    <?php else: ?>
                      <span class="badge badge--cancelled">Inactive</span>
                    <?php endif; ?>
                    <?php if ($c['email_verified']): ?>
                      <span class="badge badge--confirmed">Verified</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            onclick="openCustomer(<?= $c['id'] ?>)" title="View Profile">
                      <i class="fas fa-eye"></i>
                    </button>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <input type="hidden" name="val" value="<?= $c['is_active'] ? 0 : 1 ?>">
                      <button type="submit" class="btn-admin btn-admin--ghost btn-admin--sm"
                              title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>">
                        <i class="fas fa-<?= $c['is_active'] ? 'user-slash' : 'user-check' ?>"></i>
                      </button>
                    </form>
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            style="color:var(--danger);"
                            onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['first_name'] . ' ' . $c['last_name'])) ?>')"
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


<!-- CUSTOMER DETAIL DRAWER -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer" id="customerDrawer">
  <div class="drawer__head">
    <div class="drawer__title" id="drawerTitle">Customer Profile</div>
    <button class="drawer__close" id="drawerClose"><i class="fas fa-times"></i></button>
  </div>
  <div class="drawer__body" id="drawerBody"></div>
  <div class="drawer__footer">
    <button class="btn-admin btn-admin--outline" id="drawerCancelBtn">Close</button>
  </div>
</div>


<!-- Delete Confirmation Modal -->
<div class="del-modal" id="delModal">
  <div class="del-modal__box">
    <div class="del-modal__icon"><i class="fas fa-user-times"></i></div>
    <div class="del-modal__title">Remove Customer?</div>
    <div class="del-modal__sub" id="delModalSub">This will permanently delete the customer account.</div>
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


<!-- Customer + order data for JS -->
<script>
const ALL_CUSTOMERS = <?= json_encode(array_column($customers, null, 'id')) ?>;
const CUSTOMER_ORDERS = <?= json_encode(
    array_reduce($customers, function($carry, $c) use ($db) {
        $st = $db->prepare("SELECT id, order_number, total_usd, status, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10");
        $st->execute([$c['id']]);
        $carry[$c['id']] = $st->fetchAll(PDO::FETCH_ASSOC);
        return $carry;
    }, [])
) ?>;
</script>

<script src="assets/js/admin.js"></script>
<script>
// Drawer open/close 
function openDrawer() {
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById('customerDrawer').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('drawerOverlay').classList.remove('open');
  document.getElementById('customerDrawer').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('drawerClose').addEventListener('click', closeDrawer);
document.getElementById('drawerCancelBtn').addEventListener('click', closeDrawer);
document.getElementById('drawerOverlay').addEventListener('click', closeDrawer);

// Open customer profile 
function openCustomer(id) {
  const c = ALL_CUSTOMERS[id];
  if (!c) return;
  const orders = CUSTOMER_ORDERS[id] || [];

  const initials = (c.first_name.charAt(0) + c.last_name.charAt(0)).toUpperCase();
  const fullName = escH(c.first_name + ' ' + c.last_name);

  document.getElementById('drawerTitle').textContent = 'Customer Profile';

  // Status badges
  const activeBadge   = c.is_active    == 1 ? '<span class="badge badge--active">Active</span>'    : '<span class="badge badge--cancelled">Inactive</span>';
  const verifiedBadge = c.email_verified == 1 ? '<span class="badge badge--confirmed">Verified</span>' : '';

  // Orders rows
  let ordersHtml = '';
  if (orders.length) {
    orders.forEach(o => {
      ordersHtml += `<tr>
        <td><strong>${escH(o.order_number)}</strong></td>
        <td>$${fmt(o.total_usd)}</td>
        <td>${statusBadge(o.status)}</td>
        <td style="font-size:11px;color:var(--text-soft);">${fmtDate(o.created_at)}</td>
        <td><a href="orders.php?view=${o.id}" class="btn-admin btn-admin--ghost btn-admin--sm"><i class="fas fa-eye"></i></a></td>
      </tr>`;
    });
  } else {
    ordersHtml = '<tr><td colspan="5" style="text-align:center;color:var(--text-soft);padding:16px;">No orders yet</td></tr>';
  }

  const body = `
    <div class="cust-profile">
      <div class="cust-avatar">${initials}</div>
      <div>
        <div class="cust-profile__name">${fullName}</div>
        <div class="cust-profile__email">${escH(c.email)}</div>
        <div class="cust-profile__badges">${activeBadge}${verifiedBadge}</div>
      </div>
    </div>

    <div class="cust-stats">
      <div class="cust-stat">
        <div class="cust-stat__val">${c.order_count}</div>
        <div class="cust-stat__label">Orders</div>
      </div>
      <div class="cust-stat">
        <div class="cust-stat__val">$${fmt(c.total_spent)}</div>
        <div class="cust-stat__label">Total Spent</div>
      </div>
      <div class="cust-stat">
        <div class="cust-stat__val">${c.order_count > 0 ? '$' + fmt(c.total_spent / c.order_count) : '—'}</div>
        <div class="cust-stat__label">Avg. Order</div>
      </div>
    </div>

    <div class="od-section" style="margin-top:20px;">Account Details</div>
    <div class="od-grid" style="margin-bottom:16px;">
      <div class="od-row">
        <div class="od-row__label">Phone</div>
        <div class="od-row__val">${escH(c.phone || '—')}</div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Country</div>
        <div class="od-row__val">${escH(c.country || '—')}</div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Joined</div>
        <div class="od-row__val">${fmtDate(c.created_at)}</div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Last Login</div>
        <div class="od-row__val">${c.last_login ? fmtDate(c.last_login) : '—'}</div>
      </div>
    </div>

    <div class="od-section">Order History</div>
    <table class="mini-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>${ordersHtml}</tbody>
    </table>
  `;

  document.getElementById('drawerBody').innerHTML = body;
  openDrawer();
}

// Helpers 
function escH(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmt(n) {
  return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s.replace(' ','T'));
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
}
function statusBadge(s) {
  const map = {
    pending:'badge--pending', confirmed:'badge--confirmed', processing:'badge--confirmed',
    shipped:'badge--shipped', delivered:'badge--delivered', cancelled:'badge--cancelled', refunded:'badge--cancelled'
  };
  return `<span class="badge ${map[s]||'badge--gold'}">${s.charAt(0).toUpperCase()+s.slice(1)}</span>`;
}

// Delete modal 
function confirmDelete(id, name) {
  document.getElementById('delId').value = id;
  document.getElementById('delModalSub').textContent = 'Remove "' + name + '"? This cannot be undone.';
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
