<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_auth.php';

require_customer_login();

$customer_id = $_SESSION['customer_id'];
$db          = db();

// Load customer record
$stCust = $db->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
$stCust->execute([$customer_id]);
$customer = $stCust->fetch();

if (!$customer) {
    destroy_customer_session();
    header('Location: login.php');
    exit;
}

$success     = '';
$error       = '';
$activePanel = $_GET['tab'] ?? 'orders';

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');

        if (!$firstName || !$lastName) {
            $error = 'First and last name are required.';
        } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $chk = $db->prepare('SELECT id FROM customers WHERE email = ? AND id != ?');
            $chk->execute([$email, $customer_id]);
            if ($chk->fetch()) {
                $error = 'That email address is already in use by another account.';
            } else {
                $db->prepare('UPDATE customers SET first_name=?, last_name=?, email=?, phone=? WHERE id=?')
                   ->execute([$firstName, $lastName, $email, $phone ?: null, $customer_id]);
                $_SESSION['customer_fname'] = $firstName;
                $_SESSION['customer_lname'] = $lastName;
                $_SESSION['customer_email'] = $email;
                $stCust->execute([$customer_id]);
                $customer = $stCust->fetch();
                $success = 'Profile updated successfully.';
            }
        }
        $activePanel = 'profile';

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPwd  = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$newPwd || !$confirm) {
            $error = 'Please fill in all password fields.';
        } elseif (!password_verify($current, $customer['password_hash'])) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($newPwd) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPwd !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE customers SET password_hash=? WHERE id=?')->execute([$hash, $customer_id]);
            $success = 'Password changed successfully.';
        }
        $activePanel = 'password';
    }
}

// Load orders 
$stOrders = $db->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stOrders->execute([$customer_id]);
$orderList = $stOrders->fetchAll();

$orderItems = [];
foreach ($orderList as $ord) {
    $stItems = $db->prepare("
        SELECT oi.*, p.image_main, p.slug, p.gemstone_type, c.name AS category_name
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stItems->execute([$ord['id']]);
    $orderItems[$ord['id']] = $stItems->fetchAll();
}

// Stats 
$totalOrders = count($orderList);
$totalSpent  = array_sum(array_column($orderList, 'total_usd'));
$memberSince = date('M Y', strtotime($customer['created_at']));
$initials    = strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1));

// Helpers 
function order_status_badge(string $status): string {
    $map = [
        'pending'    => ['processing', 'Pending',    'fas fa-clock'],
        'confirmed'  => ['processing', 'Confirmed',  'fas fa-check'],
        'processing' => ['processing', 'Processing', 'fas fa-cog'],
        'shipped'    => ['transit',    'Shipped',    'fas fa-shipping-fast'],
        'delivered'  => ['delivered',  'Delivered',  'fas fa-check-circle'],
        'cancelled'  => ['cancelled',  'Cancelled',  'fas fa-times-circle'],
        'refunded'   => ['cancelled',  'Refunded',   'fas fa-undo'],
    ];
    [$cls, $label, $icon] = $map[$status] ?? ['processing', ucfirst($status), 'fas fa-circle'];
    return "<span class=\"order-status order-status--{$cls}\"><i class=\"{$icon}\"></i> {$label}</span>";
}

function track_step_index(string $status): int {
    return match($status) {
        'pending'              => 0,
        'confirmed'            => 1,
        'processing'           => 1,
        'shipped'              => 2,
        'delivered'            => 3,
        default                => -1,
    };
}

// Page setup 
$pageTitle   = 'My Account | Mirabella Ceylon';
$pageDesc    = 'Manage your Mirabella Ceylon account, view orders, and update your profile.';
$activePage  = '';
$headerClass = 'is-solid';
$extraCSS    = ['assets/css/account.css'];
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="account-breadcrumb-bar">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <i class="fas fa-chevron-right breadcrumb__sep"></i>
      <span>My Account</span>
    </nav>
  </div>
</div>

<!-- Hero -->
<div class="account-page-hero">
  <div class="container account-page-hero__inner">
    <div>
      <p class="account-page-hero__sub">Welcome back,</p>
      <h1 class="account-page-hero__title"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></h1>
    </div>
    <div class="account-page-hero__icon" aria-hidden="true"><i class="fas fa-gem"></i></div>
  </div>
</div>

<!-- Main content -->
<section class="account-section">
  <div class="container">

    <?php if ($success): ?>
    <div style="display:flex;align-items:center;gap:10px;background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.3);border-radius:8px;padding:12px 18px;font-size:13px;color:#2e7d32;margin-bottom:24px;">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div style="display:flex;align-items:center;gap:10px;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);border-radius:8px;padding:12px 18px;font-size:13px;color:#e74c3c;margin-bottom:24px;">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="profile-layout">

      <!-- Sidebar  -->
      <aside class="profile-sidebar">

        <div class="profile-avatar-card">
          <div class="profile-avatar-card__banner"></div>
          <div class="profile-avatar-card__main">
            <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="profile-name"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></div>
            <div class="profile-email"><?= htmlspecialchars($customer['email']) ?></div>
            <div class="profile-member-badge"><i class="fas fa-gem"></i> Member since <?= $memberSince ?></div>
            <div class="profile-stats">
              <div class="profile-stat">
                <div class="profile-stat__val"><?= $totalOrders ?></div>
                <div class="profile-stat__label">Orders</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat__val">$<?= number_format($totalSpent, 0) ?></div>
                <div class="profile-stat__label">Spent</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat__val"><?= $customer['email_verified'] ? '<i class="fas fa-check" style="color:var(--gold);font-size:14px;"></i>' : '<i class="fas fa-times" style="color:#ccc;font-size:14px;"></i>' ?></div>
                <div class="profile-stat__label">Verified</div>
              </div>
            </div>
          </div>
        </div>

        <nav class="profile-nav" aria-label="Account navigation">
          <button class="profile-nav__item <?= $activePanel === 'orders'   ? 'active' : '' ?>" data-panel="orders">
            <i class="fas fa-box-open"></i> My Orders
            <i class="fas fa-chevron-right profile-nav__arrow"></i>
          </button>
          <button class="profile-nav__item <?= $activePanel === 'profile'  ? 'active' : '' ?>" data-panel="profile">
            <i class="fas fa-user-edit"></i> Edit Profile
            <i class="fas fa-chevron-right profile-nav__arrow"></i>
          </button>
          <button class="profile-nav__item <?= $activePanel === 'password' ? 'active' : '' ?>" data-panel="password">
            <i class="fas fa-lock"></i> Change Password
            <i class="fas fa-chevron-right profile-nav__arrow"></i>
          </button>
          <button class="profile-nav__item <?= $activePanel === 'prefs'    ? 'active' : '' ?>" data-panel="prefs">
            <i class="fas fa-sliders-h"></i> Preferences
            <i class="fas fa-chevron-right profile-nav__arrow"></i>
          </button>
          <a class="profile-nav__item profile-nav__item--danger" href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Sign Out
          </a>
        </nav>

      </aside>

      <!-- Content panels -->
      <main>

        <!-- ORDERS -->
        <div class="profile-panel <?= $activePanel === 'orders' ? 'active' : '' ?>" id="panel-orders">

          <?php if (empty($orderList)): ?>
          <div class="account-empty">
            <div class="account-empty__icon"><i class="fas fa-box-open"></i></div>
            <h3 class="account-empty__title">No orders yet</h3>
            <p class="account-empty__text">You haven't placed any orders with us yet. Explore our collection of certified Ceylon gemstones and handcrafted jewellery.</p>
            <a href="shop.php" class="btn btn--gold">Browse Collections</a>
          </div>
          <?php else: ?>

          <!-- Status filter tabs -->
          <div class="orders-tabs" role="tablist">
            <button class="orders-tab active" data-filter="all">All Orders <span style="font-size:10px;opacity:.7;">(<?= $totalOrders ?>)</span></button>
            <?php
            $statusCounts = array_count_values(array_column($orderList, 'status'));
            $tabStatuses  = ['pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'];
            foreach ($tabStatuses as $st => $label):
                if (!empty($statusCounts[$st])):
            ?>
            <button class="orders-tab" data-filter="<?= $st ?>"><?= $label ?> <span style="font-size:10px;opacity:.7;">(<?= $statusCounts[$st] ?>)</span></button>
            <?php endif; endforeach; ?>
          </div>

          <div class="orders-list">
            <?php foreach ($orderList as $order): ?>
            <?php
            $isCancelled = in_array($order['status'], ['cancelled','refunded']);
            $stepIdx     = track_step_index($order['status']);
            $steps       = ['Placed','Confirmed','Shipped','Delivered'];
            $stepIcons   = ['fas fa-shopping-bag','fas fa-check','fas fa-shipping-fast','fas fa-home'];
            ?>
            <div class="order-card <?= $isCancelled ? 'order-card--cancelled' : '' ?>" data-status="<?= $order['status'] ?>">

              <div class="order-card__header">
                <div>
                  <div class="order-card__num"><?= htmlspecialchars($order['order_number']) ?></div>
                  <div class="order-card__date">
                    <i class="far fa-calendar"></i>
                    <?= date('d M Y', strtotime($order['created_at'])) ?>
                    &nbsp;·&nbsp; <?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?>
                  </div>
                </div>
                <div class="order-card__header-right">
                  <?= order_status_badge($order['status']) ?>
                  <div class="order-card__total">$<?= number_format($order['total_usd'], 2) ?></div>
                </div>
              </div>

              <?php if (!empty($orderItems[$order['id']])): ?>
              <div class="order-card__items">
                <?php foreach ($orderItems[$order['id']] as $item): ?>
                <div class="order-item">
                  <div class="order-item__img">
                    <?php if ($item['image_main']): ?>
                    <img src="<?= htmlspecialchars($item['image_main']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" loading="lazy" />
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--border);font-size:22px;"><i class="fas fa-gem"></i></div>
                    <?php endif; ?>
                  </div>
                  <div style="flex:1;min-width:0;">
                    <?php if ($item['category_name']): ?>
                    <div class="order-item__cat"><?= htmlspecialchars($item['category_name']) ?></div>
                    <?php endif; ?>
                    <div class="order-item__name"><?= htmlspecialchars($item['product_name']) ?></div>
                    <div class="order-item__specs">
                      <?php if ($item['product_sku']): ?><span>SKU: <?= htmlspecialchars($item['product_sku']) ?></span><?php endif; ?>
                      <?php if ($item['gemstone_type']): ?><span><?= htmlspecialchars($item['gemstone_type']) ?></span><?php endif; ?>
                      <span>Qty: <?= $item['quantity'] ?></span>
                    </div>
                  </div>
                  <div style="text-align:right;">
                    <div class="order-item__price">$<?= number_format($item['line_total'], 2) ?></div>
                    <?php if (!empty($item['slug'])): ?>
                    <a href="product-detail.php?slug=<?= urlencode($item['slug']) ?>" class="order-item__view-link">View Product</a>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <div class="order-card__footer">
                <?php if ($isCancelled): ?>
                <div class="order-cancelled-note">
                  <i class="fas fa-info-circle"></i>
                  This order was <?= $order['status'] ?>.
                  <?php if ($order['payment_status'] === 'refunded'): ?> A refund has been issued.<?php endif; ?>
                </div>
                <?php else: ?>
                <!-- Progress bar -->
                <div class="order-track-bar">
                  <?php foreach ($steps as $i => $stepName): ?>
                  <div class="order-track__step <?= $stepIdx >= $i ? 'done' : '' ?> <?= $stepIdx === $i ? 'active' : '' ?>">
                    <div class="order-track__dot"><i class="<?= $stepIcons[$i] ?>"></i></div>
                    <span><?= $stepName ?></span>
                  </div>
                  <?php if ($i < count($steps) - 1): ?>
                  <div class="order-track__line <?= $stepIdx > $i ? 'done' : '' ?>"></div>
                  <?php endif; ?>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="order-card__actions">
                  <?php if ($order['status'] === 'shipped' && $order['payment_ref']): ?>
                  <a href="#" class="order-btn order-btn--gold"><i class="fas fa-map-marker-alt"></i> Track</a>
                  <?php endif; ?>
                  <a href="mailto:info@mirabelaceylon.com?subject=Order+<?= urlencode($order['order_number']) ?>" class="order-btn order-btn--outline">
                    <i class="fas fa-headset"></i> Support
                  </a>
                </div>
              </div>

            </div>
            <?php endforeach; ?>
          </div>

          <?php endif; ?>
        </div>

        <!-- PROFILE -->
        <div class="profile-panel <?= $activePanel === 'profile' ? 'active' : '' ?>" id="panel-profile">
          <div class="profile-card">
            <div class="profile-card__head">
              <div class="profile-card__title">
                <i class="fas fa-user-edit"></i> Personal Details
              </div>
            </div>
            <div class="profile-card__body">
              <form class="co-form" method="POST">
                <input type="hidden" name="action" value="update_profile" />

                <div class="co-row">
                  <div class="co-field">
                    <label for="pFirstName">First Name</label>
                    <div class="co-field__wrap">
                      <i class="fas fa-user co-field__icon"></i>
                      <input type="text" id="pFirstName" name="first_name"
                             placeholder="John" autocomplete="given-name"
                             value="<?= htmlspecialchars($customer['first_name']) ?>" required />
                    </div>
                  </div>
                  <div class="co-field">
                    <label for="pLastName">Last Name</label>
                    <div class="co-field__wrap">
                      <i class="fas fa-user co-field__icon"></i>
                      <input type="text" id="pLastName" name="last_name"
                             placeholder="Smith" autocomplete="family-name"
                             value="<?= htmlspecialchars($customer['last_name']) ?>" required />
                    </div>
                  </div>
                </div>

                <div class="co-field">
                  <label for="pEmail">Email Address</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-envelope co-field__icon"></i>
                    <input type="email" id="pEmail" name="email"
                           placeholder="you@example.com" autocomplete="email"
                           value="<?= htmlspecialchars($customer['email']) ?>" required />
                  </div>
                </div>

                <div class="co-field">
                  <label for="pPhone">Phone Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                  <div class="co-field__wrap">
                    <i class="fas fa-phone co-field__icon"></i>
                    <input type="tel" id="pPhone" name="phone"
                           placeholder="+1 234 567 8900" autocomplete="tel"
                           value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" />
                  </div>
                </div>

                <div class="profile-save-row">
                  <button type="submit" class="btn btn--gold" style="padding:11px 28px;">
                    <i class="fas fa-save"></i> Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- PASSWORD -->
        <div class="profile-panel <?= $activePanel === 'password' ? 'active' : '' ?>" id="panel-password">
          <div class="profile-card">
            <div class="profile-card__head">
              <div class="profile-card__title">
                <i class="fas fa-lock"></i> Change Password
              </div>
            </div>
            <div class="profile-card__body">
              <form class="co-form" method="POST" id="pwdForm">
                <input type="hidden" name="action" value="change_password" />

                <div class="co-field">
                  <label for="curPwd">Current Password</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-lock co-field__icon"></i>
                    <input type="password" id="curPwd" name="current_password"
                           placeholder="Your current password" autocomplete="current-password" required />
                    <button type="button" class="auth-field__toggle" id="togCur"><i class="far fa-eye"></i></button>
                  </div>
                </div>

                <div class="co-field">
                  <label for="newPwd">New Password</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-key co-field__icon"></i>
                    <input type="password" id="newPwd" name="new_password"
                           placeholder="Create a new password" autocomplete="new-password" required />
                    <button type="button" class="auth-field__toggle" id="togNew"><i class="far fa-eye"></i></button>
                  </div>
                  <div class="auth-strength"><div class="auth-strength__fill" id="pwdStrengthFill"></div></div>
                  <span class="auth-field__hint" id="pwdStrengthHint"></span>
                </div>

                <div class="co-field">
                  <label for="conPwd">Confirm New Password</label>
                  <div class="co-field__wrap">
                    <i class="fas fa-key co-field__icon"></i>
                    <input type="password" id="conPwd" name="confirm_password"
                           placeholder="Repeat new password" autocomplete="new-password" required />
                    <button type="button" class="auth-field__toggle" id="togCon"><i class="far fa-eye"></i></button>
                  </div>
                  <span class="auth-field__hint" id="pwdMatchHint"></span>
                </div>

                <div class="profile-save-row">
                  <button type="submit" class="btn btn--gold" style="padding:11px 28px;">
                    <i class="fas fa-shield-alt"></i> Update Password
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- PREFERENCES -->
        <div class="profile-panel <?= $activePanel === 'prefs' ? 'active' : '' ?>" id="panel-prefs">
          <div class="profile-card">
            <div class="profile-card__head">
              <div class="profile-card__title">
                <i class="fas fa-sliders-h"></i> Communication Preferences
              </div>
            </div>
            <div class="profile-card__body">
              <?php
              // Check newsletter subscription status
              $nlSt = $db->prepare('SELECT id FROM newsletter_subscribers WHERE email = ? AND is_active = 1');
              $nlSt->execute([$customer['email']]);
              $isSubscribed = (bool) $nlSt->fetch();
              ?>
              <div class="pref-row">
                <div class="pref-row__info">
                  <strong>New Arrival Alerts</strong>
                  <span>Be the first to see rare gemstone arrivals and exclusive drops</span>
                </div>
                <label class="toggle-switch">
                  <input type="checkbox" class="pref-toggle" data-pref="newsletter" <?= $isSubscribed ? 'checked' : '' ?> />
                  <span class="toggle-switch__track"></span>
                </label>
              </div>
              <div class="pref-row">
                <div class="pref-row__info">
                  <strong>Order Status Updates</strong>
                  <span>Receive email notifications when your order status changes</span>
                </div>
                <label class="toggle-switch">
                  <input type="checkbox" checked disabled />
                  <span class="toggle-switch__track"></span>
                </label>
              </div>
              <div class="pref-row">
                <div class="pref-row__info">
                  <strong>Exclusive Member Offers</strong>
                  <span>Special pricing and early access for account holders</span>
                </div>
                <label class="toggle-switch">
                  <input type="checkbox" class="pref-toggle" data-pref="offers" checked />
                  <span class="toggle-switch__track"></span>
                </label>
              </div>
              <p style="font-size:12px;color:var(--text-lighter);margin-top:16px;line-height:1.6;">
                Order status updates are required and cannot be disabled. Other preferences are saved automatically.
              </p>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
</section>

<?php
$extraJS = [];
include 'includes/footer.php';
?>

<script>
// Panel switching 
document.querySelectorAll('.profile-nav__item[data-panel]').forEach(btn => {
  btn.addEventListener('click', function () {
    const target = this.dataset.panel;

    document.querySelectorAll('.profile-nav__item[data-panel]').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.profile-panel').forEach(p => p.classList.remove('active'));

    this.classList.add('active');
    document.getElementById('panel-' + target)?.classList.add('active');

    // Update URL without reload
    history.replaceState(null, '', '?tab=' + target);
  });
});

// Order status filter 
document.querySelectorAll('.orders-tab').forEach(tab => {
  tab.addEventListener('click', function () {
    const filter = this.dataset.filter;
    document.querySelectorAll('.orders-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');

    document.querySelectorAll('.order-card').forEach(card => {
      if (filter === 'all' || card.dataset.status === filter) {
        card.classList.remove('hidden');
      } else {
        card.classList.add('hidden');
      }
    });
  });
});

// Password toggles 
function togglePwdBtn(btnId, inputId) {
  document.getElementById(btnId)?.addEventListener('click', function () {
    const input   = document.getElementById(inputId);
    const icon    = this.querySelector('i');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.classList.toggle('fa-eye',      !isHidden);
    icon.classList.toggle('fa-eye-slash', isHidden);
  });
}
togglePwdBtn('togCur', 'curPwd');
togglePwdBtn('togNew', 'newPwd');
togglePwdBtn('togCon', 'conPwd');

// Password strength 
document.getElementById('newPwd')?.addEventListener('input', function () {
  const val  = this.value;
  const fill = document.getElementById('pwdStrengthFill');
  const hint = document.getElementById('pwdStrengthHint');
  if (!fill) return;
  let score = 0;
  if (val.length >= 8)           score++;
  if (/[A-Z]/.test(val))         score++;
  if (/[0-9]/.test(val))         score++;
  if (/[^A-Za-z0-9]/.test(val))  score++;
  const levels = [
    {w:'0%',   c:'transparent', label:''},
    {w:'25%',  c:'#e74c3c',     label:'Weak'},
    {w:'50%',  c:'#e67e22',     label:'Fair'},
    {w:'75%',  c:'#f1c40f',     label:'Good'},
    {w:'100%', c:'#27ae60',     label:'Strong'},
  ];
  const l = levels[score] || levels[0];
  fill.style.width      = val ? l.w : '0%';
  fill.style.background = l.c;
  hint.textContent      = val ? l.label : '';
});

// Password match 
document.getElementById('conPwd')?.addEventListener('input', function () {
  const pwd  = document.getElementById('newPwd')?.value;
  const hint = document.getElementById('pwdMatchHint');
  if (!hint) return;
  if (!this.value) { hint.textContent = ''; hint.className = 'auth-field__hint'; return; }
  const match = this.value === pwd;
  hint.textContent = match ? 'Passwords match' : 'Passwords do not match';
  hint.className   = 'auth-field__hint ' + (match ? 'success' : 'error');
});

// Newsletter toggle 
document.querySelectorAll('.pref-toggle').forEach(toggle => {
  toggle.addEventListener('change', function () {
    const pref    = this.dataset.pref;
    const enabled = this.checked;
    if (pref !== 'newsletter') return;
    fetch('ajax/toggle_newsletter.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'enabled=' + (enabled ? '1' : '0')
    }).catch(() => {});
  });
});
</script>
