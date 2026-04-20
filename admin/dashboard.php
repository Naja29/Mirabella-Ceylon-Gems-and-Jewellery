<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

/* Fetch stats */
$stats = [
  'total_orders'    => 0,
  'pending_orders'  => 0,
  'total_revenue'   => 0,
  'total_products'  => 0,
  'total_customers' => 0,
  'new_messages'    => 0,
];

$recentOrders   = [];
$recentActivity = [];

try {
  $db = db();

  $stats['total_orders']    = $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
  $stats['pending_orders']  = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
  $stats['total_revenue']   = $db->query("SELECT COALESCE(SUM(total_usd),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
  $stats['total_products']  = $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
  $stats['total_customers'] = $db->query('SELECT COUNT(*) FROM customers')->fetchColumn();
  $stats['new_messages']    = $db->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

  $recentOrders = $db->query(
    "SELECT o.id, o.order_number, o.customer_name, o.total_usd, o.status, o.created_at
     FROM orders o
     ORDER BY o.created_at DESC
     LIMIT 8"
  )->fetchAll();

  $recentActivity = $db->query(
    "SELECT type, description, created_at FROM activity_log
     ORDER BY created_at DESC LIMIT 8"
  )->fetchAll();

} catch (PDOException $e) {
  /* Tables don't exist yet — silently show zeros */
  error_log('Dashboard query error: ' . $e->getMessage());
}

function fmtMoney($val) {
  return '$' . number_format((float)$val, 0);
}
function timeAgo($datetime) {
  $diff = time() - strtotime($datetime);
  if ($diff < 60)     return 'just now';
  if ($diff < 3600)   return floor($diff/60)   . 'm ago';
  if ($diff < 86400)  return floor($diff/3600)  . 'h ago';
  return floor($diff/86400) . 'd ago';
}
function statusBadge($status) {
  $map = [
    'pending'   => 'badge--pending',
    'confirmed' => 'badge--confirmed',
    'shipped'   => 'badge--shipped',
    'delivered' => 'badge--delivered',
    'cancelled' => 'badge--cancelled',
  ];
  $cls = $map[$status] ?? 'badge--gold';
  return '<span class="badge ' . $cls . '">' . ucfirst(htmlspecialchars($status)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Dashboard | Mirabella Ceylon Admin</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/admin.css" />
</head>
<body>

<div class="admin-layout">

  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <!--  MAIN  -->
  <main class="admin-main" id="adminMain">
    <div class="admin-content">

      <!-- Page header -->
      <div class="page-header">
        <div class="page-header__left">
          <div class="page-header__eyebrow">Overview</div>
          <h1 class="page-header__title">Dashboard</h1>
          <p class="page-header__sub">
            Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?>.
            Here's what's happening today.
          </p>
        </div>
        <div class="page-header__actions">
          <a href="orders.php" class="btn-admin btn-admin--outline">
            <i class="fas fa-box-open"></i> View Orders
          </a>
          <a href="products.php" class="btn-admin btn-admin--primary">
            <i class="fas fa-plus"></i> Add Product
          </a>
        </div>
      </div>


      <!-- Stat Cards -->
      <div class="stats-grid">

        <div class="stat-card stat-card--gold">
          <div class="stat-card__head">
            <div class="stat-card__label">Total Orders</div>
            <div class="stat-card__icon"><i class="fas fa-box-open"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['total_orders']) ?></div>
          <div class="stat-card__change stat-card__change--neutral">
            <i class="fas fa-circle" style="font-size:6px;"></i>
            <span>All time</span>
          </div>
        </div>

        <div class="stat-card stat-card--orange">
          <div class="stat-card__head">
            <div class="stat-card__label">Pending Orders</div>
            <div class="stat-card__icon"><i class="fas fa-clock"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['pending_orders']) ?></div>
          <div class="stat-card__change <?= $stats['pending_orders'] > 0 ? 'stat-card__change--down' : 'stat-card__change--neutral' ?>">
            <i class="fas fa-<?= $stats['pending_orders'] > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i>
            <span><?= $stats['pending_orders'] > 0 ? 'Needs attention' : 'All clear' ?></span>
          </div>
        </div>

        <div class="stat-card stat-card--green">
          <div class="stat-card__head">
            <div class="stat-card__label">Total Revenue</div>
            <div class="stat-card__icon"><i class="fas fa-dollar-sign"></i></div>
          </div>
          <div class="stat-card__value"><?= fmtMoney($stats['total_revenue']) ?></div>
          <div class="stat-card__change stat-card__change--neutral">
            <i class="fas fa-circle" style="font-size:6px;"></i>
            <span>All time (USD)</span>
          </div>
        </div>

        <div class="stat-card stat-card--blue">
          <div class="stat-card__head">
            <div class="stat-card__label">Active Products</div>
            <div class="stat-card__icon"><i class="fas fa-gem"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['total_products']) ?></div>
          <div class="stat-card__change stat-card__change--neutral">
            <i class="fas fa-circle" style="font-size:6px;"></i>
            <span><?= number_format($stats['total_customers']) ?> customers</span>
          </div>
        </div>

      </div>


      <!-- Main Grid -->
      <div class="dashboard-grid">

        <!-- Recent Orders -->
        <div class="admin-card">
          <div class="admin-card__head">
            <div class="admin-card__title">
              <i class="fas fa-box-open"></i> Recent Orders
            </div>
            <div class="admin-card__actions">
              <a href="orders.php" class="btn-admin btn-admin--ghost btn-admin--sm">
                View all <i class="fas fa-arrow-right" style="font-size:10px;"></i>
              </a>
            </div>
          </div>
          <div class="admin-table-wrap">
            <?php if (empty($recentOrders)): ?>
            <div class="admin-empty">
              <i class="fas fa-box-open"></i>
              <p>No orders yet. They will appear here once customers start ordering.</p>
            </div>
            <?php else: ?>
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Customer</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                  <td><?= htmlspecialchars($order['customer_name']) ?></td>
                  <td><strong><?= fmtMoney($order['total_usd']) ?></strong></td>
                  <td><?= statusBadge($order['status']) ?></td>
                  <td><?= timeAgo($order['created_at']) ?></td>
                  <td>
                    <a href="orders.php?id=<?= $order['id'] ?>" class="btn-admin btn-admin--ghost btn-admin--sm">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>


        <!-- Right Column -->
        <div style="display:flex;flex-direction:column;gap:20px;">

          <!-- Quick Stats -->
          <div class="admin-card">
            <div class="admin-card__head">
              <div class="admin-card__title">
                <i class="fas fa-chart-pie"></i> Quick Stats
              </div>
            </div>
            <div class="admin-card__body" style="padding:16px 22px;">
              <div style="display:flex;flex-direction:column;gap:14px;">

                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-size:12px;color:var(--text-mid);">
                    <i class="fas fa-users" style="color:var(--info);margin-right:7px;width:14px;text-align:center;"></i>
                    Total Customers
                  </span>
                  <strong><?= number_format($stats['total_customers']) ?></strong>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-size:12px;color:var(--text-mid);">
                    <i class="fas fa-envelope" style="color:var(--gold);margin-right:7px;width:14px;text-align:center;"></i>
                    Unread Messages
                  </span>
                  <strong><?= number_format($stats['new_messages']) ?></strong>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-size:12px;color:var(--text-mid);">
                    <i class="fas fa-gem" style="color:var(--success);margin-right:7px;width:14px;text-align:center;"></i>
                    Active Listings
                  </span>
                  <strong><?= number_format($stats['total_products']) ?></strong>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-size:12px;color:var(--text-mid);">
                    <i class="fas fa-clock" style="color:var(--warning);margin-right:7px;width:14px;text-align:center;"></i>
                    Pending Orders
                  </span>
                  <strong style="color:<?= $stats['pending_orders'] > 0 ? 'var(--warning)' : 'var(--text)' ?>">
                    <?= number_format($stats['pending_orders']) ?>
                  </strong>
                </div>

              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="admin-card">
            <div class="admin-card__head">
              <div class="admin-card__title">
                <i class="fas fa-history"></i> Recent Activity
              </div>
            </div>
            <div class="admin-card__body">
              <?php if (empty($recentActivity)): ?>
              <div class="activity-list">
                <div class="activity-item">
                  <div class="activity-item__dot activity-item__dot--payment">
                    <i class="fas fa-check"></i>
                  </div>
                  <div class="activity-item__text">
                    <div class="activity-item__title">Admin panel is ready</div>
                    <div class="activity-item__time">Just now</div>
                  </div>
                </div>
                <div class="activity-item">
                  <div class="activity-item__dot activity-item__dot--message">
                    <i class="fas fa-gem"></i>
                  </div>
                  <div class="activity-item__text">
                    <div class="activity-item__title">Database connected successfully</div>
                    <div class="activity-item__time">Just now</div>
                  </div>
                </div>
              </div>
              <?php else: ?>
              <div class="activity-list">
                <?php foreach ($recentActivity as $item): ?>
                <div class="activity-item">
                  <div class="activity-item__dot activity-item__dot--<?= htmlspecialchars($item['type']) ?>">
                    <i class="fas fa-circle" style="font-size:8px;"></i>
                  </div>
                  <div class="activity-item__text">
                    <div class="activity-item__title"><?= htmlspecialchars($item['description']) ?></div>
                    <div class="activity-item__time"><?= timeAgo($item['created_at']) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="admin-card">
            <div class="admin-card__head">
              <div class="admin-card__title">
                <i class="fas fa-bolt"></i> Quick Actions
              </div>
            </div>
            <div class="admin-card__body" style="padding:14px 22px;display:flex;flex-direction:column;gap:8px;">
              <a href="products.php?action=add" class="btn-admin btn-admin--outline" style="justify-content:flex-start;">
                <i class="fas fa-plus-circle" style="color:var(--gold);"></i> Add New Product
              </a>
              <a href="orders.php?filter=pending" class="btn-admin btn-admin--outline" style="justify-content:flex-start;">
                <i class="fas fa-clock" style="color:var(--warning);"></i> View Pending Orders
              </a>
              <a href="messages.php?filter=unread" class="btn-admin btn-admin--outline" style="justify-content:flex-start;">
                <i class="fas fa-envelope" style="color:var(--info);"></i> Unread Messages
              </a>
              <a href="settings.php" class="btn-admin btn-admin--outline" style="justify-content:flex-start;">
                <i class="fas fa-cog" style="color:var(--text-soft);"></i> Settings
              </a>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>

</div>

<script src="assets/js/admin.js"></script>
</body>
</html>
