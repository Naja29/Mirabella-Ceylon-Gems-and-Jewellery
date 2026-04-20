<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle  = 'Orders';
$activePage = 'orders';

$db    = db();
$flash = '';
$flashType = 'success';

// Process POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id            = (int)($_POST['id'] ?? 0);
        $status        = $_POST['status']         ?? '';
        $payStatus     = $_POST['payment_status'] ?? '';
        $notes         = trim($_POST['notes']     ?? '');
        $shippedAt     = trim($_POST['shipped_at']   ?? '') ?: null;
        $deliveredAt   = trim($_POST['delivered_at'] ?? '') ?: null;

        $allowed = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];
        $allowedP= ['unpaid','paid','refunded','partially_refunded'];

        if ($id && in_array($status, $allowed, true) && in_array($payStatus, $allowedP, true)) {
            $db->prepare("UPDATE orders SET
                status = ?, payment_status = ?, notes = ?,
                shipped_at = ?, delivered_at = ?
                WHERE id = ?")
               ->execute([$status, $payStatus, $notes, $shippedAt, $deliveredAt, $id]);

            $flash = 'Order updated successfully.';
        } else {
            $flash = 'Invalid data.'; $flashType = 'error';
        }

        header('Location: orders.php?flash=' . urlencode($flash) . '&ft=' . $flashType
            . ($id ? '&view=' . $id : ''));
        exit;
    }
}

// Flash from redirect 
if (!$flash && isset($_GET['flash'])) {
    $flash     = htmlspecialchars($_GET['flash']);
    $flashType = $_GET['ft'] ?? 'success';
}

// Filters 
$search    = trim($_GET['q']      ?? '');
$filterSt  = trim($_GET['status'] ?? '');
$filterPay = trim($_GET['pay']    ?? '');
$dateFrom  = trim($_GET['from']   ?? '');
$dateTo    = trim($_GET['to']     ?? '');

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterSt)  { $where[] = 'o.status = ?';         $params[] = $filterSt; }
if ($filterPay) { $where[] = 'o.payment_status = ?';  $params[] = $filterPay; }
if ($dateFrom)  { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)    { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $dateTo; }

$whereStr = implode(' AND ', $where);

$orders = $db->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
    FROM orders o
    WHERE $whereStr
    ORDER BY o.created_at DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();

// Stats 
$stats = [
    'total'     => (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending'   => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'shipped'   => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status IN ('shipped','delivered')")->fetchColumn(),
    'revenue'   => (float)$db->query("SELECT COALESCE(SUM(total_usd),0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
];

// View single order 
$viewOrder = null;
$viewItems = [];
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $st  = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$vid]);
    $viewOrder = $st->fetch() ?: null;
    if ($viewOrder) {
        $ist = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $ist->execute([$vid]);
        $viewItems = $ist->fetchAll();
    }
}

// Helpers 
function orderStatusBadge(string $s): string {
    $map = [
        'pending'    => ['badge--pending',   'clock'],
        'confirmed'  => ['badge--confirmed', 'check'],
        'processing' => ['badge--confirmed', 'cog'],
        'shipped'    => ['badge--shipped',   'truck'],
        'delivered'  => ['badge--delivered', 'check-double'],
        'cancelled'  => ['badge--cancelled', 'times'],
        'refunded'   => ['badge--cancelled', 'undo'],
    ];
    [$cls, $ico] = $map[$s] ?? ['badge--gold', 'circle'];
    return "<span class=\"badge $cls\"><i class=\"fas fa-$ico\" style=\"margin-right:4px;font-size:9px;\"></i>" . ucfirst($s) . "</span>";
}
function payStatusBadge(string $s): string {
    $map = [
        'paid'                => ['badge--delivered', 'Paid'],
        'unpaid'              => ['badge--pending',   'Unpaid'],
        'refunded'            => ['badge--cancelled', 'Refunded'],
        'partially_refunded'  => ['badge--pending',   'Part. Refunded'],
    ];
    [$cls, $label] = $map[$s] ?? ['badge--gold', ucfirst($s)];
    return "<span class=\"badge $cls\">$label</span>";
}
function fmtDate(string $d): string {
    return date('d M Y, H:i', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Orders | Mirabella Ceylon Admin</title>
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
    .drawer{position:fixed;top:0;right:0;height:100%;width:min(680px,100%);background:var(--dark-card);border-left:1px solid var(--dark-border);z-index:901;transform:translateX(100%);transition:transform .3s var(--ease);display:flex;flex-direction:column;overflow:hidden;}
    .drawer.open{transform:translateX(0);}
    .drawer__head{padding:20px 26px;border-bottom:1px solid var(--dark-border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
    .drawer__title{font-family:var(--font-display);font-size:18px;font-weight:600;color:var(--text);}
    .drawer__close{width:34px;height:34px;border:none;background:none;color:var(--text-soft);cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:.2s;}
    .drawer__close:hover{background:rgba(255,255,255,.06);color:var(--text);}
    .drawer__body{flex:1;overflow-y:auto;padding:24px 26px;}
    .drawer__footer{padding:16px 26px;border-top:1px solid var(--dark-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;background:var(--dark-card);}

    /* Order detail layout  */
    .od-section{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--dark-border);}
    .od-section:first-child{margin-top:0;}
    .od-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .od-row{display:flex;flex-direction:column;gap:4px;}
    .od-row__label{font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-soft);}
    .od-row__val{font-size:13px;color:var(--text);}
    .od-address{background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--text-mid);line-height:1.7;}

    /* Items table */
    .items-table{width:100%;border-collapse:collapse;font-size:13px;}
    .items-table th{padding:8px 12px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-soft);border-bottom:1px solid var(--dark-border);text-align:left;}
    .items-table td{padding:10px 12px;color:var(--text-mid);border-bottom:1px solid rgba(255,255,255,.04);}
    .items-table tfoot td{padding:8px 12px;font-size:12px;}
    .items-table tfoot .total-row td{color:var(--text);font-weight:700;font-size:14px;border-top:1px solid var(--dark-border);}

    /* Form fields */
    .pf-field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px;}
    .pf-field label{font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-mid);}
    .pf-field select,.pf-field textarea,.pf-field input[type=datetime-local]{
      background:var(--dark-3);border:1px solid var(--dark-border);border-radius:7px;
      color:var(--text);font-family:var(--font-body);font-size:13px;padding:9px 12px;transition:.2s;width:100%;}
    .pf-field select:focus,.pf-field textarea:focus,.pf-field input:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-glow);}
    .pf-field textarea{resize:vertical;min-height:70px;}
    .pf-field select option{background:var(--dark-3);}
    .pf-2col{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

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

    /* Totals */
    .totals-box{background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;padding:14px 16px;}
    .totals-row{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:13px;color:var(--text-mid);}
    .totals-row.total{font-weight:700;font-size:15px;color:var(--text);border-top:1px solid var(--dark-border);margin-top:6px;padding-top:10px;}
    .totals-row.total span:last-child{color:var(--gold);}
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
          <div class="page-header__eyebrow">Sales</div>
          <h1 class="page-header__title">Orders</h1>
          <p class="page-header__sub">View and manage all customer orders.</p>
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
            <div class="stat-card__label">Total Orders</div>
            <div class="stat-card__icon"><i class="fas fa-box-open"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card stat-card--orange">
          <div class="stat-card__head">
            <div class="stat-card__label">Pending</div>
            <div class="stat-card__icon"><i class="fas fa-clock"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['pending']) ?></div>
          <div class="stat-card__change <?= $stats['pending'] > 0 ? 'stat-card__change--down' : 'stat-card__change--neutral' ?>">
            <i class="fas fa-<?= $stats['pending'] > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i>
            <span><?= $stats['pending'] > 0 ? 'Needs attention' : 'All clear' ?></span>
          </div>
        </div>
        <div class="stat-card stat-card--gold">
          <div class="stat-card__head">
            <div class="stat-card__label">Shipped / Delivered</div>
            <div class="stat-card__icon"><i class="fas fa-truck"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['shipped']) ?></div>
        </div>
        <div class="stat-card stat-card--green">
          <div class="stat-card__head">
            <div class="stat-card__label">Total Revenue</div>
            <div class="stat-card__icon"><i class="fas fa-dollar-sign"></i></div>
          </div>
          <div class="stat-card__value">$<?= number_format($stats['revenue'], 0) ?></div>
          <div class="stat-card__change stat-card__change--neutral">
            <i class="fas fa-circle" style="font-size:6px;"></i>
            <span>All time (USD)</span>
          </div>
        </div>
      </div>

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar" id="filterForm">
        <div class="filter-search-wrap">
          <i class="fas fa-search"></i>
          <input type="search" name="q" placeholder="Search orders…" value="<?= htmlspecialchars($search) ?>" />
        </div>
        <select name="status" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterSt === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="pay" onchange="this.form.submit()">
          <option value="">All Payments</option>
          <option value="unpaid"             <?= $filterPay==='unpaid'             ? 'selected':'' ?>>Unpaid</option>
          <option value="paid"               <?= $filterPay==='paid'               ? 'selected':'' ?>>Paid</option>
          <option value="refunded"           <?= $filterPay==='refunded'           ? 'selected':'' ?>>Refunded</option>
          <option value="partially_refunded" <?= $filterPay==='partially_refunded' ? 'selected':'' ?>>Part. Refunded</option>
        </select>
        <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" title="From date" onchange="this.form.submit()">
        <input type="date" name="to"   value="<?= htmlspecialchars($dateTo) ?>"   title="To date"   onchange="this.form.submit()">
        <?php if ($search || $filterSt || $filterPay || $dateFrom || $dateTo): ?>
        <a href="orders.php" class="btn-admin btn-admin--ghost btn-admin--sm">
          <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
        <button type="submit" class="btn-admin btn-admin--outline btn-admin--sm">
          <i class="fas fa-search"></i> Search
        </button>
      </form>

      <!-- Orders Table -->
      <div class="admin-card">
        <div class="admin-card__head">
          <div class="admin-card__title">
            <i class="fas fa-box-open"></i>
            <?= count($orders) ?> Order<?= count($orders) != 1 ? 's' : '' ?>
            <?= ($search || $filterSt || $filterPay || $dateFrom || $dateTo) ? '<span style="font-weight:400;color:var(--text-soft);font-size:12px;"> (filtered)</span>' : '' ?>
          </div>
        </div>
        <div class="admin-table-wrap">
          <?php if (empty($orders)): ?>
          <div class="admin-empty">
            <i class="fas fa-box-open"></i>
            <p>No orders found. Orders will appear here once customers complete checkout.</p>
          </div>
          <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
              <tr>
                <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                <td>
                  <strong><?= htmlspecialchars($o['customer_name']) ?></strong><br>
                  <span style="font-size:11px;color:var(--text-soft);"><?= htmlspecialchars($o['customer_email']) ?></span>
                </td>
                <td style="text-align:center;"><?= $o['item_count'] ?></td>
                <td><strong>$<?= number_format($o['total_usd'], 2) ?></strong></td>
                <td><?= payStatusBadge($o['payment_status']) ?></td>
                <td><?= orderStatusBadge($o['status']) ?></td>
                <td style="font-size:12px;color:var(--text-soft);">
                  <?= date('d M Y', strtotime($o['created_at'])) ?><br>
                  <?= date('H:i', strtotime($o['created_at'])) ?>
                </td>
                <td>
                  <button class="btn-admin btn-admin--ghost btn-admin--sm"
                          onclick="openOrder(<?= $o['id'] ?>)" title="View Order">
                    <i class="fas fa-eye"></i>
                  </button>
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


<!-- ORDER DETAIL DRAWER -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer" id="orderDrawer">
  <div class="drawer__head">
    <div class="drawer__title" id="drawerTitle">Order Details</div>
    <button class="drawer__close" id="drawerClose"><i class="fas fa-times"></i></button>
  </div>

  <div class="drawer__body" id="drawerBody">
    <div style="text-align:center;padding:60px 0;color:var(--text-soft);">
      <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
      <p style="margin-top:12px;font-size:13px;">Loading order…</p>
    </div>
  </div>

  <div class="drawer__footer" id="drawerFooter" style="display:none;">
    <button class="btn-admin btn-admin--outline" id="drawerCancelBtn">Close</button>
    <button class="btn-admin btn-admin--primary" id="drawerSaveBtn">
      <i class="fas fa-save"></i> Save Changes
    </button>
  </div>
</div>


<!-- All orders data for JS -->
<script>
const ALL_ORDERS = <?= json_encode(array_column($orders, null, 'id')) ?>;
const ORDER_ITEMS = <?= json_encode(
    array_reduce($orders, function($carry, $o) use ($db) {
        $st = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $st->execute([$o['id']]);
        $carry[$o['id']] = $st->fetchAll(PDO::FETCH_ASSOC);
        return $carry;
    }, [])
) ?>;
</script>

<script src="assets/js/admin.js"></script>
<script>
// Drawer open/close 
function openDrawer() {
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById('orderDrawer').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('drawerOverlay').classList.remove('open');
  document.getElementById('orderDrawer').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('drawerClose').addEventListener('click', closeDrawer);
document.getElementById('drawerCancelBtn')?.addEventListener('click', closeDrawer);
document.getElementById('drawerOverlay').addEventListener('click', closeDrawer);

// Open order 
function openOrder(id) {
  const o = ALL_ORDERS[id];
  if (!o) return;
  const items = ORDER_ITEMS[id] || [];

  document.getElementById('drawerTitle').innerHTML =
    'Order <strong>' + escH(o.order_number) + '</strong>';

  // Build address string
  const addrParts = [o.ship_address, o.ship_city, o.ship_state, o.ship_zip, o.ship_country].filter(Boolean);
  const addrHtml  = addrParts.length ? addrParts.map(escH).join('<br>') : '<em style="opacity:.5">No address on file</em>';

  // Items rows
  let itemsHtml = '';
  if (items.length) {
    items.forEach(i => {
      itemsHtml += `<tr>
        <td>${escH(i.product_name)}${i.product_sku ? ' <span style="color:var(--text-soft);font-size:11px;">(' + escH(i.product_sku) + ')</span>' : ''}</td>
        <td style="text-align:center;">${i.quantity}</td>
        <td style="text-align:right;">$${fmt(i.unit_price)}</td>
        <td style="text-align:right;font-weight:600;">$${fmt(i.line_total)}</td>
      </tr>`;
    });
  } else {
    itemsHtml = '<tr><td colspan="4" style="text-align:center;color:var(--text-soft);padding:20px;">No items recorded</td></tr>';
  }

  const body = `
    <div class="od-section">Order Info</div>
    <div class="od-grid" style="margin-bottom:16px;">
      <div class="od-row">
        <div class="od-row__label">Order Number</div>
        <div class="od-row__val"><strong>${escH(o.order_number)}</strong></div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Date Placed</div>
        <div class="od-row__val">${fmtDate(o.created_at)}</div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Payment Method</div>
        <div class="od-row__val">${escH(o.payment_method || '—')}</div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Payment Ref</div>
        <div class="od-row__val" style="font-size:12px;word-break:break-all;">${escH(o.payment_ref || '—')}</div>
      </div>
    </div>

    <div class="od-section">Customer</div>
    <div class="od-grid" style="margin-bottom:16px;">
      <div class="od-row">
        <div class="od-row__label">Name</div>
        <div class="od-row__val"><strong>${escH(o.customer_name)}</strong></div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Email</div>
        <div class="od-row__val">${escH(o.customer_email)}</div>
      </div>
      <div class="od-row">
        <div class="od-row__label">Phone</div>
        <div class="od-row__val">${escH(o.customer_phone || '—')}</div>
      </div>
    </div>

    <div class="od-section">Shipping Address</div>
    <div class="od-address" style="margin-bottom:16px;">${addrHtml}</div>

    <div class="od-section">Order Items</div>
    <table class="items-table" style="margin-bottom:16px;">
      <thead>
        <tr>
          <th>Product</th>
          <th style="text-align:center;">Qty</th>
          <th style="text-align:right;">Unit Price</th>
          <th style="text-align:right;">Total</th>
        </tr>
      </thead>
      <tbody>${itemsHtml}</tbody>
    </table>

    <div class="totals-box" style="margin-bottom:20px;">
      <div class="totals-row"><span>Subtotal</span><span>$${fmt(o.subtotal_usd)}</span></div>
      <div class="totals-row"><span>Shipping</span><span>$${fmt(o.shipping_usd)}</span></div>
      <div class="totals-row"><span>Tax</span><span>$${fmt(o.tax_usd)}</span></div>
      <div class="totals-row total"><span>Total</span><span>$${fmt(o.total_usd)}</span></div>
    </div>

    <div class="od-section">Update Order</div>
    <form method="POST" id="updateForm">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id" value="${o.id}">
      <div class="pf-2col">
        <div class="pf-field">
          <label>Order Status</label>
          <select name="status">
            ${['pending','confirmed','processing','shipped','delivered','cancelled','refunded']
              .map(s => `<option value="${s}" ${o.status===s?'selected':''}>${cap(s)}</option>`).join('')}
          </select>
        </div>
        <div class="pf-field">
          <label>Payment Status</label>
          <select name="payment_status">
            ${[['unpaid','Unpaid'],['paid','Paid'],['refunded','Refunded'],['partially_refunded','Part. Refunded']]
              .map(([v,l]) => `<option value="${v}" ${o.payment_status===v?'selected':''}>${l}</option>`).join('')}
          </select>
        </div>
      </div>
      <div class="pf-2col">
        <div class="pf-field">
          <label>Shipped At</label>
          <input type="datetime-local" name="shipped_at"
            value="${o.shipped_at ? o.shipped_at.replace(' ','T').substring(0,16) : ''}">
        </div>
        <div class="pf-field">
          <label>Delivered At</label>
          <input type="datetime-local" name="delivered_at"
            value="${o.delivered_at ? o.delivered_at.replace(' ','T').substring(0,16) : ''}">
        </div>
      </div>
      <div class="pf-field">
        <label>Internal Notes</label>
        <textarea name="notes" rows="3" placeholder="Internal notes (not shown to customer)…">${escH(o.notes || '')}</textarea>
      </div>
    </form>
  `;

  document.getElementById('drawerBody').innerHTML = body;
  document.getElementById('drawerFooter').style.display = 'flex';
  document.getElementById('drawerSaveBtn').onclick = () => document.getElementById('updateForm').submit();

  openDrawer();
}

// Helpers 
function escH(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmt(n) { return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s.replace(' ','T'));
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) + ', '
       + d.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
}

// Auto-open if ?view= set 
<?php if ($viewOrder): ?>
openOrder(<?= $viewOrder['id'] ?>);
<?php endif; ?>
</script>
</body>
</html>
