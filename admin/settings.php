<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/settings.php';

$pageTitle  = 'Settings';
$activePage = 'settings';

$db        = db();
$flash     = '';
$flashType = 'success';

// Process POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Store Settings 
    if ($action === 'store') {
        $fields = ['store_name','store_email','store_phone','store_address','store_currency'];
        foreach ($fields as $f) {
            save_setting($f, trim($_POST[$f] ?? ''));
        }
        $flash = 'Store settings saved.';
        header('Location: settings.php?flash=' . urlencode($flash) . '&ft=success&tab=store');
        exit;
    }

    // Social Links 
    if ($action === 'social') {
        $fields = ['social_instagram','social_facebook','social_whatsapp','social_linkedin','social_youtube','social_pinterest'];
        foreach ($fields as $f) {
            save_setting($f, trim($_POST[$f] ?? ''));
        }
        $flash = 'Social links saved.';
        header('Location: settings.php?flash=' . urlencode($flash) . '&ft=success&tab=social');
        exit;
    }

    //  Payment / Bank Details 
    if ($action === 'payment') {
        $fields = ['bank_name','bank_branch','bank_account_name','bank_account_no','bank_swift',
                   'frimi_number','frimi_name','ezcash_number','ezcash_name','whatsapp_number'];
        foreach ($fields as $f) {
            save_setting($f, trim($_POST[$f] ?? ''));
        }
        $flash = 'Payment details saved.';
        header('Location: settings.php?flash=' . urlencode($flash) . '&ft=success&tab=payment');
        exit;
    }

    // Maintenance 
    if ($action === 'maintenance') {
        save_setting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        save_setting('maintenance_msg',  trim($_POST['maintenance_msg'] ?? ''));
        $flash = 'Maintenance settings saved.';
        header('Location: settings.php?flash=' . urlencode($flash) . '&ft=success&tab=maintenance');
        exit;
    }

    // SMTP Settings 
    if ($action === 'smtp') {
        $fields = ['smtp_driver','smtp_host','smtp_port','smtp_encryption',
                   'smtp_username','smtp_from_name','smtp_from_email'];
        foreach ($fields as $f) {
            save_setting($f, trim($_POST[$f] ?? ''));
        }
        // Only update password if provided
        if (!empty($_POST['smtp_password'])) {
            save_setting('smtp_password', $_POST['smtp_password']);
        }
        $flash = 'Email settings saved.';
        header('Location: settings.php?flash=' . urlencode($flash) . '&ft=success&tab=smtp');
        exit;
    }


    // Change Password 
    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $admin = $db->prepare('SELECT * FROM admin_users WHERE id = ?');
        $admin->execute([$_SESSION['admin_id']]);
        $admin = $admin->fetch();

        if (!password_verify($current, $admin['password_hash'])) {
            $flash = 'Current password is incorrect.';
            $flashType = 'error';
        } elseif (strlen($new) < 8) {
            $flash = 'New password must be at least 8 characters.';
            $flashType = 'error';
        } elseif ($new !== $confirm) {
            $flash = 'New passwords do not match.';
            $flashType = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
               ->execute([$hash, $_SESSION['admin_id']]);
            $flash = 'Password changed successfully.';
        }

        $tab = $flashType === 'error' ? 'profile' : 'profile';
        header('Location: settings.php?flash=' . urlencode($flash) . '&ft=' . $flashType . '&tab=profile');
        exit;
    }

    // Update Profile 
    if ($action === 'profile') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$name || !$email) {
            $flash = 'Name and email are required.'; $flashType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = 'Invalid email address.'; $flashType = 'error';
        } else {
            $exists = $db->prepare('SELECT id FROM admin_users WHERE email = ? AND id != ?');
            $exists->execute([$email, $_SESSION['admin_id']]);
            if ($exists->fetch()) {
                $flash = 'That email is already in use.'; $flashType = 'error';
            } else {
                $db->prepare('UPDATE admin_users SET name = ?, email = ? WHERE id = ?')
                   ->execute([$name, $email, $_SESSION['admin_id']]);
                $_SESSION['admin_name']  = $name;
                $_SESSION['admin_email'] = $email;
                $flash = 'Profile updated successfully.';
            }
        }
        header('Location: settings.php?flash=' . urlencode($flash) . '&ft=' . $flashType . '&tab=profile');
        exit;
    }
}

// Flash from redirect 
if (!$flash && isset($_GET['flash'])) {
    $flash     = htmlspecialchars($_GET['flash']);
    $flashType = $_GET['ft'] ?? 'success';
}

$activeTab = $_GET['tab'] ?? 'store';

// Load current admin 
$adminData = $db->prepare('SELECT * FROM admin_users WHERE id = ?');
$adminData->execute([$_SESSION['admin_id']]);
$adminData = $adminData->fetch();

// Load all settings 
$s = $db->query('SELECT `key`, `value` FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$s = array_merge([
    'store_name'       => 'Mirabella Ceylon',
    'store_email'      => '',
    'store_phone'      => '',
    'store_address'    => '',
    'store_currency'   => 'USD',
    'social_instagram' => '',
    'social_facebook'  => '',
    'social_whatsapp'  => '',
    'social_linkedin'  => '',
    'social_youtube'   => '',
    'social_pinterest' => '',
    'bank_name'         => 'Bank of Ceylon',
    'bank_branch'       => 'Colombo 03',
    'bank_account_name' => 'Mirabella Ceylon (Pvt) Ltd',
    'bank_account_no'   => '0072 1234 5678',
    'bank_swift'        => 'BCEYLKLX',
    'frimi_number'      => '077 123 4567',
    'frimi_name'        => 'Mirabella Ceylon (Pvt) Ltd',
    'ezcash_number'     => '071 123 4567',
    'ezcash_name'       => 'Mirabella Ceylon (Pvt) Ltd',
    'whatsapp_number'   => '+94771234567',
    'maintenance_mode' => '0',
    'maintenance_msg'  => '',
    'smtp_driver'      => 'smtp',
    'smtp_host'        => '',
    'smtp_port'        => '465',
    'smtp_encryption'  => 'ssl',
    'smtp_username'    => '',
    'smtp_password'    => '',
    'smtp_from_name'   => 'Mirabella Ceylon',
    'smtp_from_email'  => '',
], $s);

function sv(array $s, string $k): string {
    return htmlspecialchars($s[$k] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Settings | Mirabella Ceylon Admin</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/admin.css" />
  <style>
    /* Tabs */
    .settings-layout{display:grid;grid-template-columns:220px 1fr;gap:24px;align-items:start;}
    .settings-nav{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:10px;overflow:hidden;position:sticky;top:84px;}
    .settings-nav__item{display:flex;align-items:center;gap:10px;padding:13px 18px;font-size:13px;color:var(--text-mid);cursor:pointer;border-left:3px solid transparent;transition:.2s;text-decoration:none;}
    .settings-nav__item:hover{background:rgba(255,255,255,.03);color:var(--text);}
    .settings-nav__item.active{border-left-color:var(--gold);background:var(--gold-pale);color:var(--gold);font-weight:700;}
    .settings-nav__item i{width:16px;text-align:center;font-size:13px;}
    .settings-nav__divider{height:1px;background:var(--dark-border);margin:4px 0;}

    /* Tab panels */
    .tab-panel{display:none;}
    .tab-panel.active{display:block;}

    /* Form fields */
    .sf-section{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin:0 0 16px;padding-bottom:8px;border-bottom:1px solid var(--dark-border);}
    .sf-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
    .sf-full{grid-column:1/-1;}
    .sf-field{display:flex;flex-direction:column;gap:6px;}
    .sf-field label{font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-mid);}
    .sf-field label span{color:var(--danger);margin-left:2px;}
    .sf-field input,.sf-field select,.sf-field textarea{
      background:var(--dark-3);border:1px solid var(--dark-border);border-radius:7px;
      color:var(--text);font-family:var(--font-body);font-size:13px;padding:10px 13px;transition:.2s;width:100%;}
    .sf-field input:focus,.sf-field select:focus,.sf-field textarea:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-glow);}
    .sf-field textarea{resize:vertical;min-height:80px;}
    .sf-field select option{background:var(--dark-3);}
    .sf-field__hint{font-size:11px;color:var(--text-soft);}

    /* Input with icon */
    .sf-input-wrap{position:relative;}
    .sf-input-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-soft);font-size:13px;pointer-events:none;}
    .sf-input-wrap input{padding-left:36px;}

    /* Toggle */
    .sf-toggle{display:flex;align-items:center;gap:12px;cursor:pointer;}
    .sf-toggle input{display:none;}
    .sf-toggle__track{width:44px;height:24px;background:var(--dark-border);border-radius:24px;position:relative;transition:.2s;flex-shrink:0;}
    .sf-toggle__track::after{content:'';position:absolute;top:4px;left:4px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.2s;}
    .sf-toggle input:checked + .sf-toggle__track{background:var(--gold);}
    .sf-toggle input:checked + .sf-toggle__track::after{left:24px;}
    .sf-toggle__label{font-size:14px;color:var(--text);font-weight:600;}
    .sf-toggle__sub{font-size:12px;color:var(--text-soft);margin-top:2px;}

    /*  Maintenance banner */
    .maintenance-warning{background:rgba(231,76,60,.08);border:1px solid rgba(231,76,60,.25);border-radius:10px;padding:18px 20px;display:flex;gap:14px;align-items:flex-start;margin-bottom:20px;}
    .maintenance-warning i{color:var(--danger);font-size:20px;flex-shrink:0;margin-top:2px;}
    .maintenance-warning__title{font-weight:700;font-size:14px;color:var(--danger);margin-bottom:4px;}
    .maintenance-warning__sub{font-size:13px;color:var(--text-mid);}
    .maintenance-active-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);color:#e74c3c;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}

    /* Profile avatar */
    .profile-avatar{width:72px;height:72px;border-radius:50%;background:var(--gold-pale);border:3px solid var(--gold-glow);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:28px;font-weight:700;color:var(--gold);flex-shrink:0;}
    .profile-header{display:flex;align-items:center;gap:18px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--dark-border);}
    .profile-header__name{font-family:var(--font-display);font-size:18px;font-weight:600;color:var(--text);}
    .profile-header__role{font-size:12px;color:var(--text-soft);margin-top:3px;}

    /* Password strength */
    .pw-strength{height:4px;border-radius:4px;margin-top:6px;background:var(--dark-border);overflow:hidden;}
    .pw-strength__bar{height:100%;border-radius:4px;transition:width .3s,background .3s;width:0;}

    /* Flash */
    .flash-bar{padding:12px 20px;border-radius:8px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
    .flash-bar--success{background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.25);color:#2ecc71;}
    .flash-bar--error{background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.25);color:#e74c3c;}

    /*  Responsive */
    @media(max-width:900px){
      .settings-layout{grid-template-columns:1fr;}
      .settings-nav{position:static;display:flex;overflow-x:auto;}
      .settings-nav__item{white-space:nowrap;border-left:none;border-bottom:3px solid transparent;}
      .settings-nav__item.active{border-bottom-color:var(--gold);border-left-color:transparent;}
      .settings-nav__divider{width:1px;height:auto;margin:4px 0;}
    }
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
          <div class="page-header__eyebrow">Configuration</div>
          <h1 class="page-header__title">
            Settings
            <?php if ($s['maintenance_mode'] === '1'): ?>
            <span class="maintenance-active-badge" style="margin-left:12px;">
              <i class="fas fa-tools"></i> Maintenance ON
            </span>
            <?php endif; ?>
          </h1>
          <p class="page-header__sub">Manage your store configuration and admin profile.</p>
        </div>
      </div>

      <!-- Flash -->
      <?php if ($flash): ?>
      <div class="flash-bar flash-bar--<?= htmlspecialchars($flashType) ?>">
        <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $flash ?>
      </div>
      <?php endif; ?>

      <!-- Settings Layout -->
      <div class="settings-layout">

        <!-- Nav -->
        <nav class="settings-nav">
          <a href="?tab=store"       class="settings-nav__item <?= $activeTab==='store'       ? 'active':'' ?>"><i class="fas fa-store"></i> Store</a>
          <a href="?tab=social"      class="settings-nav__item <?= $activeTab==='social'      ? 'active':'' ?>"><i class="fas fa-share-alt"></i> Social Links</a>
          <a href="?tab=payment"     class="settings-nav__item <?= $activeTab==='payment'     ? 'active':'' ?>"><i class="fas fa-university"></i> Payment Details</a>
          <a href="?tab=smtp"        class="settings-nav__item <?= $activeTab==='smtp'        ? 'active':'' ?>"><i class="fas fa-paper-plane"></i> Email / SMTP</a>
          <div class="settings-nav__divider"></div>
          <a href="?tab=maintenance" class="settings-nav__item <?= $activeTab==='maintenance' ? 'active':'' ?>">
            <i class="fas fa-tools" style="<?= $s['maintenance_mode']==='1' ? 'color:var(--danger)':'' ?>"></i>
            Maintenance
            <?php if ($s['maintenance_mode']==='1'): ?>
            <span style="margin-left:auto;width:8px;height:8px;border-radius:50%;background:var(--danger);display:inline-block;"></span>
            <?php endif; ?>
          </a>
          <div class="settings-nav__divider"></div>
          <a href="?tab=profile"     class="settings-nav__item <?= $activeTab==='profile'     ? 'active':'' ?>"><i class="fas fa-user-cog"></i> My Profile</a>
          <a href="?tab=password"    class="settings-nav__item <?= $activeTab==='password'    ? 'active':'' ?>"><i class="fas fa-lock"></i> Password</a>
        </nav>

        <!-- Panels -->
        <div>

          <!-- STORE SETTINGS -->
          <div class="tab-panel <?= $activeTab==='store' ? 'active':'' ?>" id="tab-store">
            <div class="admin-card">
              <div class="admin-card__head">
                <div class="admin-card__title"><i class="fas fa-store"></i> Store Settings</div>
              </div>
              <div class="admin-card__body">
                <form method="POST">
                  <input type="hidden" name="action" value="store">

                  <div class="sf-section">Business Information</div>
                  <div class="sf-grid">
                    <div class="sf-field">
                      <label>Store Name <span>*</span></label>
                      <input type="text" name="store_name" value="<?= sv($s,'store_name') ?>" required placeholder="Mirabella Ceylon">
                    </div>
                    <div class="sf-field">
                      <label>Currency</label>
                      <select name="store_currency">
                        <?php foreach (['USD'=>'USD — US Dollar','LKR'=>'LKR — Sri Lankan Rupee','EUR'=>'EUR — Euro','GBP'=>'GBP — British Pound','AUD'=>'AUD — Australian Dollar'] as $code => $label): ?>
                        <option value="<?= $code ?>" <?= sv($s,'store_currency')===$code ? 'selected':'' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="sf-section">Contact Details</div>
                  <div class="sf-grid">
                    <div class="sf-field">
                      <label>Store Email</label>
                      <div class="sf-input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="store_email" value="<?= sv($s,'store_email') ?>" placeholder="info@mirabelaceylon.com">
                      </div>
                      <div class="sf-field__hint">Used in order confirmation and contact emails.</div>
                    </div>
                    <div class="sf-field">
                      <label>Store Phone</label>
                      <div class="sf-input-wrap">
                        <i class="fas fa-phone"></i>
                        <input type="text" name="store_phone" value="<?= sv($s,'store_phone') ?>" placeholder="+94 77 000 0000">
                      </div>
                    </div>
                    <div class="sf-field sf-full">
                      <label>Store Address</label>
                      <textarea name="store_address" rows="3" placeholder="No. 1, Gem Street, Ratnapura, Sri Lanka"><?= sv($s,'store_address') ?></textarea>
                    </div>
                  </div>

                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn-admin btn-admin--primary">
                      <i class="fas fa-save"></i> Save Store Settings
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- SOCIAL LINKS -->
          <div class="tab-panel <?= $activeTab==='social' ? 'active':'' ?>" id="tab-social">
            <div class="admin-card">
              <div class="admin-card__head">
                <div class="admin-card__title"><i class="fas fa-share-alt"></i> Social Links</div>
              </div>
              <div class="admin-card__body">
                <form method="POST">
                  <input type="hidden" name="action" value="social">

                  <div class="sf-section">Social Media Profiles</div>
                  <div class="sf-grid">
                    <div class="sf-field">
                      <label>Instagram</label>
                      <div class="sf-input-wrap">
                        <i class="fab fa-instagram" style="color:#E1306C;"></i>
                        <input type="url" name="social_instagram" value="<?= sv($s,'social_instagram') ?>" placeholder="https://instagram.com/yourpage">
                      </div>
                    </div>
                    <div class="sf-field">
                      <label>Facebook</label>
                      <div class="sf-input-wrap">
                        <i class="fab fa-facebook" style="color:#1877F2;"></i>
                        <input type="url" name="social_facebook" value="<?= sv($s,'social_facebook') ?>" placeholder="https://facebook.com/yourpage">
                      </div>
                    </div>
                    <div class="sf-field">
                      <label>WhatsApp</label>
                      <div class="sf-input-wrap">
                        <i class="fab fa-whatsapp" style="color:#25D366;"></i>
                        <input type="text" name="social_whatsapp" value="<?= sv($s,'social_whatsapp') ?>" placeholder="+94770000000">
                      </div>
                      <div class="sf-field__hint">Enter phone number with country code (no spaces).</div>
                    </div>
                    <div class="sf-field">
                      <label>LinkedIn</label>
                      <div class="sf-input-wrap">
                        <i class="fab fa-linkedin" style="color:#0A66C2;"></i>
                        <input type="url" name="social_linkedin" value="<?= sv($s,'social_linkedin') ?>" placeholder="https://linkedin.com/company/yourpage">
                      </div>
                    </div>
                    <div class="sf-field">
                      <label>YouTube</label>
                      <div class="sf-input-wrap">
                        <i class="fab fa-youtube" style="color:#FF0000;"></i>
                        <input type="url" name="social_youtube" value="<?= sv($s,'social_youtube') ?>" placeholder="https://youtube.com/@yourchannel">
                      </div>
                    </div>
                    <div class="sf-field">
                      <label>Pinterest</label>
                      <div class="sf-input-wrap">
                        <i class="fab fa-pinterest" style="color:#E60023;"></i>
                        <input type="url" name="social_pinterest" value="<?= sv($s,'social_pinterest') ?>" placeholder="https://pinterest.com/yourpage">
                      </div>
                    </div>
                  </div>

                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn-admin btn-admin--primary">
                      <i class="fas fa-save"></i> Save Social Links
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- PAYMENT DETAILS -->
          <div class="tab-panel <?= $activeTab==='payment' ? 'active':'' ?>" id="tab-payment">
            <div class="sf-card">
              <div class="sf-card__head">
                <div class="sf-card__title"><i class="fas fa-university"></i> Bank Transfer</div>
                <div class="sf-card__sub">Details shown to customers on the checkout page.</div>
              </div>
              <form method="POST">
                <input type="hidden" name="action" value="payment">
                <div class="sf-grid">
                  <div class="sf-field">
                    <label>Bank Name</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-university"></i>
                      <input type="text" name="bank_name" value="<?= sv($s,'bank_name') ?>" placeholder="e.g. Bank of Ceylon">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>Branch</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-map-marker-alt"></i>
                      <input type="text" name="bank_branch" value="<?= sv($s,'bank_branch') ?>" placeholder="e.g. Colombo 03">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>Account Name</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-user"></i>
                      <input type="text" name="bank_account_name" value="<?= sv($s,'bank_account_name') ?>" placeholder="e.g. Mirabella Ceylon (Pvt) Ltd">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>Account Number</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-hashtag"></i>
                      <input type="text" name="bank_account_no" value="<?= sv($s,'bank_account_no') ?>" placeholder="e.g. 0072 1234 5678">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>SWIFT / BIC Code</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-globe"></i>
                      <input type="text" name="bank_swift" value="<?= sv($s,'bank_swift') ?>" placeholder="e.g. BCEYLKLX">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>WhatsApp Number</label>
                    <div class="sf-input-wrap">
                      <i class="fab fa-whatsapp" style="color:#25D366;"></i>
                      <input type="text" name="whatsapp_number" value="<?= sv($s,'whatsapp_number') ?>" placeholder="+94771234567">
                    </div>
                    <div class="sf-field__hint">Customers send payment slips to this number.</div>
                  </div>
                </div>

                <div class="sf-card__head" style="margin-top:24px;padding-top:24px;border-top:1px solid var(--dark-border);">
                  <div class="sf-card__title"><i class="fas fa-mobile-alt"></i> Mobile Payments (Local)</div>
                  <div class="sf-card__sub">FriMi and eZ Cash details shown for Sri Lanka orders.</div>
                </div>
                <div class="sf-grid">
                  <div class="sf-field">
                    <label>FriMi Number</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-mobile-alt" style="color:#5b2d8e;"></i>
                      <input type="text" name="frimi_number" value="<?= sv($s,'frimi_number') ?>" placeholder="077 123 4567">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>FriMi Account Name</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-user"></i>
                      <input type="text" name="frimi_name" value="<?= sv($s,'frimi_name') ?>" placeholder="Account holder name">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>eZ Cash Number</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-mobile-alt" style="color:#e31e24;"></i>
                      <input type="text" name="ezcash_number" value="<?= sv($s,'ezcash_number') ?>" placeholder="071 123 4567">
                    </div>
                  </div>
                  <div class="sf-field">
                    <label>eZ Cash Account Name</label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-user"></i>
                      <input type="text" name="ezcash_name" value="<?= sv($s,'ezcash_name') ?>" placeholder="Account holder name">
                    </div>
                  </div>
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                  <button type="submit" class="btn-admin btn-admin--primary">
                    <i class="fas fa-save"></i> Save Payment Details
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- MAINTENANCE -->
          <div class="tab-panel <?= $activeTab==='maintenance' ? 'active':'' ?>" id="tab-maintenance">

            <?php if ($s['maintenance_mode'] === '1'): ?>
            <div class="maintenance-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <div>
                <div class="maintenance-warning__title">Maintenance Mode is Active</div>
                <div class="maintenance-warning__sub">Your store is currently offline to visitors. The admin panel remains accessible. Disable maintenance mode when your site is ready.</div>
              </div>
            </div>
            <?php endif; ?>

            <div class="admin-card">
              <div class="admin-card__head">
                <div class="admin-card__title"><i class="fas fa-tools"></i> Maintenance Mode</div>
              </div>
              <div class="admin-card__body">
                <form method="POST" id="maintenanceForm">
                  <input type="hidden" name="action" value="maintenance">

                  <div style="background:var(--dark-3);border:1px solid var(--dark-border);border-radius:10px;padding:20px 22px;margin-bottom:20px;">
                    <label class="sf-toggle">
                      <input type="checkbox" name="maintenance_mode" id="maintenanceToggle"
                             <?= $s['maintenance_mode']==='1' ? 'checked':'' ?>>
                      <span class="sf-toggle__track"></span>
                      <div>
                        <div class="sf-toggle__label">Enable Maintenance Mode</div>
                        <div class="sf-toggle__sub">When enabled, all frontend pages will show a maintenance screen. The admin panel stays accessible.</div>
                      </div>
                    </label>
                  </div>

                  <div class="sf-field" style="margin-bottom:20px;">
                    <label>Maintenance Message</label>
                    <textarea name="maintenance_msg" rows="4"
                      placeholder="We are currently under maintenance. We will be back shortly. Thank you for your patience."><?= sv($s,'maintenance_msg') ?></textarea>
                    <div class="sf-field__hint">This message will be shown to visitors during maintenance.</div>
                  </div>

                  <!-- Preview -->
                  <div style="margin-bottom:20px;">
                    <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-soft);margin-bottom:10px;">Preview</div>
                    <div style="background:var(--dark-2);border:1px solid var(--dark-border);border-radius:10px;padding:32px;text-align:center;">
                      <i class="fas fa-tools" style="font-size:32px;color:var(--gold);margin-bottom:14px;display:block;"></i>
                      <div style="font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--text);margin-bottom:10px;">Under Maintenance</div>
                      <div id="msgPreview" style="font-size:13px;color:var(--text-mid);max-width:400px;margin:0 auto;line-height:1.7;">
                        <?= sv($s,'maintenance_msg') ?: 'We are currently under maintenance. We will be back shortly.' ?>
                      </div>
                    </div>
                  </div>

                  <div style="display:flex;justify-content:flex-end;gap:10px;">
                    <?php if ($s['maintenance_mode'] === '1'): ?>
                    <button type="submit" name="maintenance_mode" value="" class="btn-admin btn-admin--outline"
                            onclick="document.getElementById('maintenanceToggle').checked=false;">
                      <i class="fas fa-power-off"></i> Disable Maintenance
                    </button>
                    <?php endif; ?>
                    <button type="submit" class="btn-admin btn-admin--primary">
                      <i class="fas fa-save"></i> Save Maintenance Settings
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- EMAIL / SMTP -->
          <div class="tab-panel <?= $activeTab==='smtp' ? 'active':'' ?>" id="tab-smtp">
            <div class="admin-card" style="margin-bottom:20px;">
              <div class="admin-card__head">
                <div class="admin-card__title"><i class="fas fa-paper-plane"></i> Email / SMTP Configuration</div>
              </div>
              <div class="admin-card__body">
                <form method="POST">
                  <input type="hidden" name="action" value="smtp">

                  <div class="sf-section">Mail Driver</div>
                  <div class="sf-field" style="margin-bottom:16px;">
                    <label>Driver</label>
                    <select name="smtp_driver" id="smtpDriver" onchange="toggleSmtpFields()">
                      <option value="smtp" <?= sv($s,'smtp_driver')==='smtp' ? 'selected':'' ?>>SMTP (recommended for cPanel)</option>
                      <option value="mail" <?= sv($s,'smtp_driver')==='mail' ? 'selected':'' ?>>PHP mail() (server default)</option>
                    </select>
                    <div class="sf-field__hint">Use SMTP for reliable delivery. PHP mail() depends on server configuration.</div>
                  </div>

                  <div id="smtpFields">
                    <div class="sf-section">SMTP Server</div>
                    <div class="sf-grid">
                      <div class="sf-field">
                        <label>SMTP Host</label>
                        <div class="sf-input-wrap">
                          <i class="fas fa-server"></i>
                          <input type="text" name="smtp_host" value="<?= sv($s,'smtp_host') ?>" placeholder="mail.mirabelaceylon.com">
                        </div>
                        <div class="sf-field__hint">Usually mail.yourdomain.com on cPanel.</div>
                      </div>
                      <div class="sf-field">
                        <label>SMTP Port</label>
                        <select name="smtp_port">
                          <option value="465" <?= sv($s,'smtp_port')==='465' ? 'selected':'' ?>>465 — SSL (recommended)</option>
                          <option value="587" <?= sv($s,'smtp_port')==='587' ? 'selected':'' ?>>587 — TLS / STARTTLS</option>
                          <option value="25"  <?= sv($s,'smtp_port')==='25'  ? 'selected':'' ?>>25  — Plain (not recommended)</option>
                        </select>
                      </div>
                      <div class="sf-field">
                        <label>Encryption</label>
                        <select name="smtp_encryption">
                          <option value="ssl" <?= sv($s,'smtp_encryption')==='ssl' ? 'selected':'' ?>>SSL</option>
                          <option value="tls" <?= sv($s,'smtp_encryption')==='tls' ? 'selected':'' ?>>TLS / STARTTLS</option>
                          <option value="none"<?= sv($s,'smtp_encryption')==='none'? 'selected':'' ?>>None</option>
                        </select>
                      </div>
                      <div class="sf-field">
                        <label>SMTP Username</label>
                        <div class="sf-input-wrap">
                          <i class="fas fa-user"></i>
                          <input type="text" name="smtp_username" value="<?= sv($s,'smtp_username') ?>" placeholder="info@mirabelaceylon.com" autocomplete="off">
                        </div>
                      </div>
                      <div class="sf-field sf-full">
                        <label>SMTP Password</label>
                        <div class="sf-input-wrap">
                          <i class="fas fa-lock"></i>
                          <input type="password" name="smtp_password" placeholder="<?= $s['smtp_password'] ? '••••••••  (leave blank to keep current)' : 'Enter SMTP password' ?>" autocomplete="new-password">
                        </div>
                        <?php if ($s['smtp_password']): ?>
                        <div class="sf-field__hint" style="color:var(--success);">
                          <i class="fas fa-check-circle"></i> Password is saved. Leave blank to keep it unchanged.
                        </div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="sf-section">Sender Details</div>
                    <div class="sf-grid">
                      <div class="sf-field">
                        <label>From Name</label>
                        <input type="text" name="smtp_from_name" value="<?= sv($s,'smtp_from_name') ?>" placeholder="Mirabella Ceylon">
                        <div class="sf-field__hint">Displayed as the sender name in emails.</div>
                      </div>
                      <div class="sf-field">
                        <label>From Email</label>
                        <input type="email" name="smtp_from_email" value="<?= sv($s,'smtp_from_email') ?>" placeholder="info@mirabelaceylon.com">
                        <div class="sf-field__hint">Must match your SMTP username on most servers.</div>
                      </div>
                    </div>
                  </div><!-- end smtpFields -->

                  <div style="display:flex;justify-content:flex-end;margin-top:8px;">
                    <button type="submit" class="btn-admin btn-admin--primary">
                      <i class="fas fa-save"></i> Save Email Settings
                    </button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- MY PROFILE -->
          <div class="tab-panel <?= $activeTab==='profile' ? 'active':'' ?>" id="tab-profile">
            <div class="admin-card">
              <div class="admin-card__head">
                <div class="admin-card__title"><i class="fas fa-user-cog"></i> My Profile</div>
              </div>
              <div class="admin-card__body">
                <?php $initials = strtoupper(substr($adminData['name'],0,1)); ?>
                <div class="profile-header">
                  <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
                  <div>
                    <div class="profile-header__name"><?= htmlspecialchars($adminData['name']) ?></div>
                    <div class="profile-header__role">
                      <span class="badge badge--gold"><?= htmlspecialchars($adminData['role']) ?></span>
                    </div>
                    <div style="font-size:12px;color:var(--text-soft);margin-top:6px;">
                      <?= htmlspecialchars($adminData['email']) ?>
                    </div>
                  </div>
                </div>
                <form method="POST">
                  <input type="hidden" name="action" value="profile">
                  <div class="sf-grid">
                    <div class="sf-field">
                      <label>Display Name <span>*</span></label>
                      <input type="text" name="name" value="<?= htmlspecialchars($adminData['name']) ?>" required>
                    </div>
                    <div class="sf-field">
                      <label>Email Address <span>*</span></label>
                      <input type="email" name="email" value="<?= htmlspecialchars($adminData['email']) ?>" required>
                    </div>
                  </div>
                  <div style="display:flex;justify-content:flex-end;margin-top:4px;">
                    <button type="submit" class="btn-admin btn-admin--primary">
                      <i class="fas fa-save"></i> Update Profile
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- CHANGE PASSWORD -->
          <div class="tab-panel <?= $activeTab==='password' ? 'active':'' ?>" id="tab-password">
            <div class="admin-card">
              <div class="admin-card__head">
                <div class="admin-card__title"><i class="fas fa-lock"></i> Change Password</div>
              </div>
              <div class="admin-card__body">
                <form method="POST" id="pwForm">
                  <input type="hidden" name="action" value="password">

                  <div class="sf-field" style="margin-bottom:16px;">
                    <label>Current Password <span>*</span></label>
                    <div class="sf-input-wrap">
                      <i class="fas fa-lock"></i>
                      <input type="password" name="current_password" id="currentPw" required placeholder="Enter your current password">
                    </div>
                  </div>

                  <div class="sf-grid">
                    <div class="sf-field">
                      <label>New Password <span>*</span></label>
                      <div class="sf-input-wrap">
                        <i class="fas fa-key"></i>
                        <input type="password" name="new_password" id="newPw" required placeholder="Min. 8 characters" oninput="checkStrength(this.value)">
                      </div>
                      <div class="pw-strength"><div class="pw-strength__bar" id="pwBar"></div></div>
                      <div class="sf-field__hint" id="pwHint">Enter a new password</div>
                    </div>
                    <div class="sf-field">
                      <label>Confirm New Password <span>*</span></label>
                      <div class="sf-input-wrap">
                        <i class="fas fa-key"></i>
                        <input type="password" name="confirm_password" id="confirmPw" required placeholder="Repeat new password" oninput="checkMatch()">
                      </div>
                      <div class="sf-field__hint" id="matchHint">&nbsp;</div>
                    </div>
                  </div>

                  <div style="background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;padding:14px 16px;margin:4px 0 20px;font-size:12px;color:var(--text-soft);line-height:1.8;">
                    <strong style="color:var(--text-mid);display:block;margin-bottom:4px;">Password requirements:</strong>
                    <span id="req-len"  class="pw-req">✗ At least 8 characters</span><br>
                    <span id="req-upper" class="pw-req">✗ One uppercase letter</span><br>
                    <span id="req-num"  class="pw-req">✗ One number</span>
                  </div>

                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn-admin btn-admin--primary">
                      <i class="fas fa-lock"></i> Change Password
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<script src="assets/js/admin.js"></script>
<script>
// Maintenance message live preview 
const msgArea = document.querySelector('textarea[name="maintenance_msg"]');
const msgPrev = document.getElementById('msgPreview');
if (msgArea && msgPrev) {
  msgArea.addEventListener('input', () => {
    msgPrev.textContent = msgArea.value || 'We are currently under maintenance. We will be back shortly.';
  });
}

// SMTP fields toggle 
function toggleSmtpFields() {
  const driver = document.getElementById('smtpDriver')?.value;
  const fields = document.getElementById('smtpFields');
  if (fields) fields.style.display = driver === 'mail' ? 'none' : 'block';
}
toggleSmtpFields();

// Password strength 
function checkStrength(pw) {
  const bar   = document.getElementById('pwBar');
  const hint  = document.getElementById('pwHint');
  const len   = document.getElementById('req-len');
  const upper = document.getElementById('req-upper');
  const num   = document.getElementById('req-num');

  const hasLen   = pw.length >= 8;
  const hasUpper = /[A-Z]/.test(pw);
  const hasNum   = /[0-9]/.test(pw);

  const tick = '<span style="color:var(--success);">✓</span>';
  const cross= '<span style="color:var(--danger);">✗</span>';

  len.innerHTML   = (hasLen   ? tick : cross) + ' At least 8 characters';
  upper.innerHTML = (hasUpper ? tick : cross) + ' One uppercase letter';
  num.innerHTML   = (hasNum   ? tick : cross) + ' One number';

  const score = [hasLen, hasUpper, hasNum].filter(Boolean).length;
  const widths = ['0%','40%','70%','100%'];
  const colors = ['transparent','var(--danger)','var(--warning)','var(--success)'];
  const hints  = ['','Weak','Fair','Strong'];

  bar.style.width      = widths[score];
  bar.style.background = colors[score];
  hint.textContent     = hints[score] || 'Enter a new password';
  hint.style.color     = score === 3 ? 'var(--success)' : score === 2 ? 'var(--warning)' : 'var(--text-soft)';

  checkMatch();
}

function checkMatch() {
  const nw   = document.getElementById('newPw')?.value     || '';
  const cn   = document.getElementById('confirmPw')?.value || '';
  const hint = document.getElementById('matchHint');
  if (!hint) return;
  if (!cn) { hint.textContent = '\u00a0'; hint.style.color = ''; return; }
  if (nw === cn) {
    hint.textContent = '✓ Passwords match';
    hint.style.color = 'var(--success)';
  } else {
    hint.textContent = '✗ Passwords do not match';
    hint.style.color = 'var(--danger)';
  }
}
</script>
</body>
</html>
