<?php

$adminName  = $_SESSION['admin_name']  ?? 'Admin';
$adminRole  = $_SESSION['admin_role']  ?? 'admin';
$adminEmail = $_SESSION['admin_email'] ?? '';
$initials   = strtoupper(substr($adminName, 0, 1));
$pageTitle  = $pageTitle  ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';

// Notification counts
try {
    $_ndb = db();
    $_pendingOrders  = (int)$_ndb->query("SELECT COUNT(*) FROM orders   WHERE status      = 'pending'")->fetchColumn();
    $_unreadMessages = (int)$_ndb->query("SELECT COUNT(*) FROM messages WHERE is_read     = 0")->fetchColumn();
    $_pendingReviews = (int)$_ndb->query("SELECT COUNT(*) FROM reviews  WHERE is_approved = 0")->fetchColumn();
    $_newCustomers   = (int)$_ndb->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()")->fetchColumn();
} catch (Throwable $_e) {
    $_pendingOrders = $_unreadMessages = $_pendingReviews = $_newCustomers = 0;
}
$_totalNotifs = $_pendingOrders + $_unreadMessages + $_pendingReviews;
?>

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <img src="../assets/images/logo.png" alt="Mirabella" class="sidebar-logo__img"
         onerror="this.style.display='none'" />
    <i class="fas fa-gem sidebar-logo__gem"></i>
    <div class="sidebar-logo__text">
      <div class="sidebar-logo__name">Mirabella Ceylon</div>
      <div class="sidebar-logo__tag">Admin Panel</div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">

    <div class="sidebar-section">Main</div>

    <a href="dashboard.php" data-label="Dashboard"
       class="sidebar-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-chart-line"></i></div>
      <span class="sidebar-item__label">Dashboard</span>
    </a>

    <a href="orders.php" data-label="Orders"
       class="sidebar-item <?= $activePage === 'orders' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-box-open"></i></div>
      <span class="sidebar-item__label">Orders</span>
      <span class="sidebar-badge" id="sbOrderBadge" style="display:none;"></span>
    </a>

    <a href="products.php" data-label="Products"
       class="sidebar-item <?= $activePage === 'products' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-gem"></i></div>
      <span class="sidebar-item__label">Products</span>
    </a>

    <a href="categories.php" data-label="Categories"
       class="sidebar-item <?= $activePage === 'categories' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-layer-group"></i></div>
      <span class="sidebar-item__label">Categories</span>
    </a>

    <a href="customers.php" data-label="Customers"
       class="sidebar-item <?= $activePage === 'customers' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-users"></i></div>
      <span class="sidebar-item__label">Customers</span>
      <span class="sidebar-badge" id="sbCustomerBadge" style="display:none;"></span>
    </a>

    <div class="sidebar-section">Content</div>

    <a href="messages.php" data-label="Messages"
       class="sidebar-item <?= $activePage === 'messages' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-envelope"></i></div>
      <span class="sidebar-item__label">Messages</span>
      <span class="sidebar-badge" id="sbMsgBadge" style="display:none;"></span>
    </a>

    <a href="reviews.php" data-label="Reviews"
       class="sidebar-item <?= $activePage === 'reviews' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-star"></i></div>
      <span class="sidebar-item__label">Reviews</span>
      <span class="sidebar-badge" id="sbReviewBadge" style="display:none;"></span>
    </a>

    <div class="sidebar-section">System</div>

    <a href="settings.php" data-label="Settings"
       class="sidebar-item <?= $activePage === 'settings' ? 'active' : '' ?>">
      <div class="sidebar-item__icon"><i class="fas fa-cog"></i></div>
      <span class="sidebar-item__label">Settings</span>
    </a>

    <a href="../index.php" target="_blank" data-label="View Store"
       class="sidebar-item">
      <div class="sidebar-item__icon"><i class="fas fa-external-link-alt"></i></div>
      <span class="sidebar-item__label">View Store</span>
    </a>

  </nav>

  <!-- Profile + Logout -->
  <div class="sidebar-bottom">
    <div class="sidebar-profile">
      <div class="sidebar-profile__avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="sidebar-profile__info">
        <div class="sidebar-profile__name"><?= htmlspecialchars($adminName) ?></div>
        <div class="sidebar-profile__role"><?= htmlspecialchars($adminRole) ?></div>
      </div>
    </div>
    <a href="logout.php" data-label="Logout"
       class="sidebar-item" style="color:#e05555;">
      <div class="sidebar-item__icon" style="color:#e05555;"><i class="fas fa-sign-out-alt"></i></div>
      <span class="sidebar-item__label">Logout</span>
    </a>
  </div>

</aside>


<!-- TOP BAR -->
<header class="admin-topbar" id="adminTopbar">

  <button class="topbar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="fas fa-bars"></i>
  </button>

  <div class="topbar-breadcrumb">
    <span>Admin</span>
    <span class="topbar-breadcrumb__sep"><i class="fas fa-chevron-right" style="font-size:9px;"></i></span>
    <span class="topbar-breadcrumb__current"><?= htmlspecialchars($pageTitle) ?></span>
  </div>

  <div class="topbar-actions">

    <a href="../index.php" target="_blank" class="topbar-btn" title="View Store">
      <i class="fas fa-store"></i>
    </a>

    <div class="notif-wrap" id="notifWrap">
      <button class="topbar-btn" id="notifBtn" title="Notifications">
        <i class="fas fa-bell"></i>
        <span class="topbar-btn__badge notif-count-dot" id="notifBellCount"
              style="<?= $_totalNotifs > 0 ? '' : 'display:none;' ?>"><?= $_totalNotifs > 9 ? '9+' : $_totalNotifs ?></span>
      </button>

      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown__head">
          <span class="notif-dropdown__title">Notifications</span>
          <?php if ($_totalNotifs > 0): ?>
          <span class="notif-dropdown__count"><?= $_totalNotifs ?> new</span>
          <?php endif; ?>
        </div>

        <?php if ($_totalNotifs === 0): ?>
        <div class="notif-empty">
          <i class="fas fa-check-circle"></i>
          <span>All caught up!</span>
        </div>
        <?php else: ?>

        <?php if ($_pendingOrders > 0): ?>
        <a href="orders.php?status=pending" class="notif-item notif-item--warning">
          <div class="notif-item__icon"><i class="fas fa-box-open"></i></div>
          <div class="notif-item__body">
            <div class="notif-item__text"><strong><?= $_pendingOrders ?></strong> pending order<?= $_pendingOrders > 1 ? 's' : '' ?> awaiting confirmation</div>
            <div class="notif-item__sub">Tap to review</div>
          </div>
        </a>
        <?php endif; ?>

        <?php if ($_unreadMessages > 0): ?>
        <a href="messages.php" class="notif-item notif-item--info">
          <div class="notif-item__icon"><i class="fas fa-envelope"></i></div>
          <div class="notif-item__body">
            <div class="notif-item__text"><strong><?= $_unreadMessages ?></strong> unread message<?= $_unreadMessages > 1 ? 's' : '' ?></div>
            <div class="notif-item__sub">Tap to read</div>
          </div>
        </a>
        <?php endif; ?>

        <?php if ($_pendingReviews > 0): ?>
        <a href="reviews.php" class="notif-item notif-item--gold">
          <div class="notif-item__icon"><i class="fas fa-star"></i></div>
          <div class="notif-item__body">
            <div class="notif-item__text"><strong><?= $_pendingReviews ?></strong> review<?= $_pendingReviews > 1 ? 's' : '' ?> awaiting approval</div>
            <div class="notif-item__sub">Tap to approve</div>
          </div>
        </a>
        <?php endif; ?>

        <?php endif; ?>

        <div class="notif-dropdown__foot">
          <a href="dashboard.php">Go to Dashboard</a>
        </div>
      </div>
    </div>

    <div class="topbar-divider"></div>

    <div class="topbar-user-wrap">
      <div class="topbar-user" id="topbarUser">
        <div class="topbar-user__avatar"><?= htmlspecialchars($initials) ?></div>
        <div>
          <div class="topbar-user__name"><?= htmlspecialchars($adminName) ?></div>
          <div class="topbar-user__role"><?= htmlspecialchars($adminRole) ?></div>
        </div>
        <i class="fas fa-chevron-down topbar-user__chevron"></i>
      </div>
      <div class="topbar-dropdown" id="topbarDropdown">
        <a href="settings.php" class="topbar-dropdown__item">
          <i class="fas fa-user-cog"></i> My Profile
        </a>
        <a href="settings.php" class="topbar-dropdown__item">
          <i class="fas fa-cog"></i> Settings
        </a>
        <div class="topbar-dropdown__divider"></div>
        <a href="logout.php" class="topbar-dropdown__item danger">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </div>

  </div>
</header>

