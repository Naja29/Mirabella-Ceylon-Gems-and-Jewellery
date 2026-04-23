<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/activity.php';

$pageTitle  = 'Messages';
$activePage = 'messages';

$db        = db();
$flash     = '';
$flashType = 'success';

// Ensure replies table exists
$db->exec("CREATE TABLE IF NOT EXISTS `message_replies` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` INT UNSIGNED NOT NULL,
  `admin_id`   INT UNSIGNED DEFAULT NULL,
  `admin_name` VARCHAR(100) NOT NULL DEFAULT 'Admin',
  `body`       TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mr_msg` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Process POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        $id   = (int)($_POST['id']   ?? 0);
        $body = trim($_POST['body']  ?? '');
        if ($id && $body) {
            $adminName = $_SESSION['admin_name'] ?? 'Admin';
            $adminId   = $_SESSION['admin_id']   ?? null;
            $db->prepare('INSERT INTO message_replies (message_id, admin_id, admin_name, body) VALUES (?,?,?,?)')
               ->execute([$id, $adminId, $adminName, $body]);
            $db->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
            log_activity('message', "Reply sent to message #$id.");
            $flash = 'Reply saved.';
        } else {
            $flash = 'Reply cannot be empty.'; $flashType = 'error';
        }
        header('Location: messages.php?view=' . $id . '&flash=' . urlencode($flash) . '&ft=' . $flashType);
        exit;
    }

    if ($action === 'delete_reply') {
        $rid = (int)($_POST['rid'] ?? 0);
        $mid = (int)($_POST['id']  ?? 0);
        if ($rid) $db->prepare('DELETE FROM message_replies WHERE id = ?')->execute([$rid]);
        header('Location: messages.php?view=' . $mid);
        exit;
    }

    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $row = $db->prepare('SELECT name FROM messages WHERE id = ?');
            $row->execute([$id]);
            $sender = $row->fetchColumn() ?: "Message #$id";
            $db->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
            log_activity('message', "Message from \"$sender\" marked as read.");
        }
        // AJAX fetch from JS — return JSON instead of redirect
        if (!empty($_POST['_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
        header('Location: messages.php?view=' . $id);
        exit;
    }

    if ($action === 'mark_unread') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $db->prepare('UPDATE messages SET is_read = 0 WHERE id = ?')->execute([$id]);
        header('Location: messages.php');
        exit;
    }

    if ($action === 'mark_all_read') {
        $db->query('UPDATE messages SET is_read = 1');
        log_activity('message', 'All messages marked as read.');
        $flash = 'All messages marked as read.';
        header('Location: messages.php?flash=' . urlencode($flash) . '&ft=success');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
            log_activity('message', "Message #$id deleted.");
        }
        $flash = 'Message deleted.';
        header('Location: messages.php?flash=' . urlencode($flash) . '&ft=success');
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
$filterRd = trim($_GET['read']   ?? '');
$dateFrom = trim($_GET['from']   ?? '');
$dateTo   = trim($_GET['to']     ?? '');

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterRd === 'unread') { $where[] = 'is_read = 0'; }
if ($filterRd === 'read')   { $where[] = 'is_read = 1'; }
if ($dateFrom) { $where[] = 'DATE(created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(created_at) <= ?'; $params[] = $dateTo; }

$whereStr = implode(' AND ', $where);

$msgs = $db->prepare("
    SELECT m.*, COUNT(r.id) AS reply_count
    FROM messages m
    LEFT JOIN message_replies r ON r.message_id = m.id
    WHERE $whereStr
    GROUP BY m.id
    ORDER BY m.is_read ASC, m.created_at DESC
");
$msgs->execute($params);
$msgs = $msgs->fetchAll();

// Stats 
$stats = [
    'total'   => (int)$db->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
    'unread'  => (int)$db->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn(),
    'read'    => (int)$db->query('SELECT COUNT(*) FROM messages WHERE is_read = 1')->fetchColumn(),
    'today'   => (int)$db->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
];

// View single message & auto mark read
$viewMsg     = null;
$viewReplies = [];
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $st  = $db->prepare('SELECT * FROM messages WHERE id = ?');
    $st->execute([$vid]);
    $viewMsg = $st->fetch() ?: null;
    if ($viewMsg && !$viewMsg['is_read']) {
        $db->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$vid]);
        $viewMsg['is_read'] = 1;
    }
    if ($viewMsg) {
        $rs = $db->prepare('SELECT * FROM message_replies WHERE message_id = ? ORDER BY created_at ASC');
        $rs->execute([$vid]);
        $viewReplies = $rs->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Messages | Mirabella Ceylon Admin</title>
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

    /* Message detail */
    .msg-sender{display:flex;align-items:center;gap:14px;margin-bottom:20px;}
    .msg-avatar{width:48px;height:48px;border-radius:50%;background:var(--gold-pale);border:2px solid var(--gold-glow);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--gold);flex-shrink:0;}
    .msg-sender__name{font-weight:700;font-size:15px;color:var(--text);}
    .msg-sender__email{font-size:12px;color:var(--text-mid);margin-top:2px;}
    .msg-sender__meta{font-size:11px;color:var(--text-soft);margin-top:3px;}
    .msg-subject{font-family:var(--font-display);font-size:16px;font-weight:600;color:var(--text);margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--dark-border);}
    .msg-body{font-size:14px;color:var(--text-mid);line-height:1.8;white-space:pre-wrap;background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;padding:16px 18px;}

    /* Reply thread */
    .reply-thread{margin-top:24px;}
    .reply-thread__title{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:14px;padding-bottom:6px;border-bottom:1px solid var(--dark-border);}
    .reply-item{display:flex;gap:12px;margin-bottom:14px;}
    .reply-avatar{width:32px;height:32px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#0d0d0d;flex-shrink:0;}
    .reply-bubble{background:rgba(200,168,75,.08);border:1px solid rgba(200,168,75,.2);border-radius:0 10px 10px 10px;padding:10px 14px;flex:1;}
    .reply-bubble__meta{font-size:10px;color:var(--text-soft);margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;}
    .reply-bubble__body{font-size:13px;color:var(--text-mid);line-height:1.6;white-space:pre-wrap;}
    .reply-del-btn{background:none;border:none;color:var(--text-soft);cursor:pointer;font-size:11px;padding:2px 4px;}
    .reply-del-btn:hover{color:var(--danger);}
    .reply-form{margin-top:20px;}
    .reply-form__label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:8px;display:block;}
    .reply-form textarea{width:100%;background:var(--dark-3);border:1px solid var(--dark-border);border-radius:8px;color:var(--text);font-family:var(--font-body);font-size:13px;padding:10px 14px;resize:vertical;min-height:90px;transition:.2s;}
    .reply-form textarea:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-glow);}
    .reply-form__actions{display:flex;gap:8px;margin-top:8px;justify-content:flex-end;}

    /* Table row styles */
    .admin-table tbody tr.unread td{background:rgba(201,168,76,.04);}
    .admin-table tbody tr.unread td:first-child{border-left:3px solid var(--gold);}
    .msg-preview{font-size:12px;color:var(--text-soft);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

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
          <div class="page-header__eyebrow">Inbox</div>
          <h1 class="page-header__title">
            Messages
            <?php if ($stats['unread'] > 0): ?>
            <span style="font-size:14px;background:var(--gold);color:var(--dark);padding:2px 10px;border-radius:20px;font-family:var(--font-body);font-weight:700;vertical-align:middle;margin-left:8px;">
              <?= $stats['unread'] ?> new
            </span>
            <?php endif; ?>
          </h1>
          <p class="page-header__sub">Contact form submissions from customers.</p>
        </div>
        <div class="page-header__actions">
          <?php if ($stats['unread'] > 0): ?>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn-admin btn-admin--outline">
              <i class="fas fa-check-double"></i> Mark All Read
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

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card stat-card--gold">
          <div class="stat-card__head">
            <div class="stat-card__label">Unread</div>
            <div class="stat-card__icon"><i class="fas fa-envelope"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['unread']) ?></div>
          <div class="stat-card__change <?= $stats['unread'] > 0 ? 'stat-card__change--down' : 'stat-card__change--neutral' ?>">
            <i class="fas fa-<?= $stats['unread'] > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i>
            <span><?= $stats['unread'] > 0 ? 'Needs attention' : 'All caught up' ?></span>
          </div>
        </div>
        <div class="stat-card stat-card--green">
          <div class="stat-card__head">
            <div class="stat-card__label">Read</div>
            <div class="stat-card__icon"><i class="fas fa-envelope-open"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['read']) ?></div>
        </div>
        <div class="stat-card stat-card--blue">
          <div class="stat-card__head">
            <div class="stat-card__label">Total Messages</div>
            <div class="stat-card__icon"><i class="fas fa-inbox"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card stat-card--orange">
          <div class="stat-card__head">
            <div class="stat-card__label">Received Today</div>
            <div class="stat-card__icon"><i class="fas fa-calendar-day"></i></div>
          </div>
          <div class="stat-card__value"><?= number_format($stats['today']) ?></div>
        </div>
      </div>

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar">
        <div class="filter-search-wrap">
          <i class="fas fa-search"></i>
          <input type="search" name="q" placeholder="Search messages…" value="<?= htmlspecialchars($search) ?>" />
        </div>
        <select name="read" onchange="this.form.submit()">
          <option value="">All Messages</option>
          <option value="unread" <?= $filterRd==='unread' ? 'selected':'' ?>>Unread</option>
          <option value="read"   <?= $filterRd==='read'   ? 'selected':'' ?>>Read</option>
        </select>
        <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" title="From date" onchange="this.form.submit()">
        <input type="date" name="to"   value="<?= htmlspecialchars($dateTo) ?>"   title="To date"   onchange="this.form.submit()">
        <?php if ($search || $filterRd || $dateFrom || $dateTo): ?>
        <a href="messages.php" class="btn-admin btn-admin--ghost btn-admin--sm">
          <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
        <button type="submit" class="btn-admin btn-admin--outline btn-admin--sm">
          <i class="fas fa-search"></i> Search
        </button>
      </form>

      <!-- Messages Table -->
      <div class="admin-card">
        <div class="admin-card__head">
          <div class="admin-card__title">
            <i class="fas fa-inbox"></i>
            <?= count($msgs) ?> Message<?= count($msgs) != 1 ? 's' : '' ?>
            <?= ($search || $filterRd || $dateFrom || $dateTo) ? '<span style="font-weight:400;color:var(--text-soft);font-size:12px;"> (filtered)</span>' : '' ?>
          </div>
        </div>
        <div class="admin-table-wrap">
          <?php if (empty($msgs)): ?>
          <div class="admin-empty">
            <i class="fas fa-inbox"></i>
            <p>No messages yet. Contact form submissions will appear here.</p>
          </div>
          <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th></th>
                <th>From</th>
                <th>Subject</th>
                <th>Preview</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($msgs as $m): ?>
              <tr class="<?= !$m['is_read'] ? 'unread' : '' ?>" style="cursor:pointer;"
                  onclick="openMessage(<?= $m['id'] ?>)">
                <td style="width:20px;" onclick="event.stopPropagation();">
                  <?php if (!$m['is_read']): ?>
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--gold);"></span>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($m['name']) ?></strong><br>
                  <span style="font-size:11px;color:var(--text-soft);"><?= htmlspecialchars($m['email']) ?></span>
                </td>
                <td>
                  <span style="font-weight:<?= $m['is_read'] ? '400' : '700' ?>;color:<?= $m['is_read'] ? 'var(--text-mid)' : 'var(--text)' ?>;">
                    <?= htmlspecialchars($m['subject'] ?: '(No subject)') ?>
                  </span>
                  <?php if ($m['reply_count'] > 0): ?>
                  <span style="font-size:10px;font-weight:700;background:rgba(200,168,75,.12);color:var(--gold);padding:1px 7px;border-radius:10px;margin-left:6px;">
                    <?= $m['reply_count'] ?> <?= $m['reply_count'] == 1 ? 'reply' : 'replies' ?>
                  </span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="msg-preview"><?= htmlspecialchars($m['message']) ?></div>
                </td>
                <td style="font-size:12px;color:var(--text-soft);white-space:nowrap;" onclick="event.stopPropagation();">
                  <?= date('d M Y', strtotime($m['created_at'])) ?><br>
                  <?= date('H:i', strtotime($m['created_at'])) ?>
                </td>
                <td onclick="event.stopPropagation();">
                  <div style="display:flex;gap:6px;">
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            onclick="openMessage(<?= $m['id'] ?>)" title="View">
                      <i class="fas fa-eye"></i>
                    </button>
                    <a href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=Re: <?= htmlspecialchars(urlencode($m['subject'] ?: 'Your enquiry')) ?>"
                       class="btn-admin btn-admin--ghost btn-admin--sm" title="Reply by Email">
                      <i class="fas fa-reply"></i>
                    </a>
                    <button class="btn-admin btn-admin--ghost btn-admin--sm"
                            style="color:var(--danger);"
                            onclick="confirmDelete(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['name'])) ?>')"
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


<!-- MESSAGE DETAIL DRAWER -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer" id="msgDrawer">
  <div class="drawer__head">
    <div class="drawer__title" id="drawerTitle">Message</div>
    <button class="drawer__close" id="drawerClose"><i class="fas fa-times"></i></button>
  </div>
  <div class="drawer__body" id="drawerBody"></div>
  <div class="drawer__footer" id="drawerFooter">
    <form method="POST" style="display:inline;" id="unreadForm">
      <input type="hidden" name="action" value="mark_unread">
      <input type="hidden" name="id" id="unreadId">
      <button type="submit" class="btn-admin btn-admin--ghost btn-admin--sm" id="markUnreadBtn" style="display:none;">
        <i class="fas fa-envelope"></i> Mark Unread
      </button>
    </form>
    <div style="flex:1;"></div>
    <button class="btn-admin btn-admin--outline" id="drawerCancelBtn">Close</button>
    <a href="#" class="btn-admin btn-admin--primary" id="replyBtn" target="_blank">
      <i class="fas fa-reply"></i> Reply by Email
    </a>
  </div>
</div>


<!-- Delete Modal -->
<div class="del-modal" id="delModal">
  <div class="del-modal__box">
    <div class="del-modal__icon"><i class="fas fa-trash-alt"></i></div>
    <div class="del-modal__title">Delete Message?</div>
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


<!-- Messages data for JS -->
<script>
const ALL_MESSAGES = <?= json_encode(array_column($msgs, null, 'id')) ?>;
const VIEW_REPLIES = <?= json_encode($viewReplies) ?>;
const VIEW_MSG_ID  = <?= $viewMsg ? $viewMsg['id'] : 'null' ?>;
</script>

<script src="assets/js/admin.js"></script>
<script>
// Drawer open/close 
function openDrawer() {
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById('msgDrawer').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('drawerOverlay').classList.remove('open');
  document.getElementById('msgDrawer').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('drawerClose').addEventListener('click', closeDrawer);
document.getElementById('drawerCancelBtn').addEventListener('click', closeDrawer);
document.getElementById('drawerOverlay').addEventListener('click', closeDrawer);

// Open message
function openMessage(id) {
  const m = ALL_MESSAGES[id];
  if (!m) return;

  const initials = m.name.trim().split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
  const replySubject = encodeURIComponent('Re: ' + (m.subject || 'Your enquiry'));

  document.getElementById('drawerTitle').textContent = m.subject || '(No subject)';
  document.getElementById('replyBtn').href = 'mailto:' + m.email + '?subject=' + replySubject;
  document.getElementById('unreadId').value = id;
  document.getElementById('markUnreadBtn').style.display = m.is_read == 1 ? 'inline-flex' : 'none';

  const phone = m.phone ? `<span style="margin-left:10px;"><i class="fas fa-phone" style="margin-right:4px;font-size:10px;"></i>${escH(m.phone)}</span>` : '';

  // Build replies HTML (only available when server pre-loaded them via ?view=)
  const replies = (VIEW_MSG_ID == id) ? VIEW_REPLIES : [];
  let repliesHtml = '';
  if (replies.length) {
    repliesHtml = '<div class="reply-thread"><div class="reply-thread__title">Replies (' + replies.length + ')</div>';
    replies.forEach(r => {
      const initR = escH(r.admin_name.charAt(0).toUpperCase());
      repliesHtml += `
        <div class="reply-item">
          <div class="reply-avatar">${initR}</div>
          <div class="reply-bubble">
            <div class="reply-bubble__meta">
              <span>${escH(r.admin_name)} &middot; ${fmtDate(r.created_at)}</span>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this reply?')">
                <input type="hidden" name="action" value="delete_reply">
                <input type="hidden" name="rid" value="${r.id}">
                <input type="hidden" name="id" value="${id}">
                <button type="submit" class="reply-del-btn" title="Delete reply"><i class="fas fa-trash"></i></button>
              </form>
            </div>
            <div class="reply-bubble__body">${escH(r.body)}</div>
          </div>
        </div>`;
    });
    repliesHtml += '</div>';
  }

  document.getElementById('drawerBody').innerHTML = `
    <div class="msg-sender">
      <div class="msg-avatar">${escH(initials)}</div>
      <div>
        <div class="msg-sender__name">${escH(m.name)}</div>
        <div class="msg-sender__email">${escH(m.email)}</div>
        <div class="msg-sender__meta">
          <i class="fas fa-clock" style="margin-right:4px;font-size:10px;"></i>${fmtDate(m.created_at)}
          ${phone}
        </div>
      </div>
    </div>
    ${m.subject ? `<div class="msg-subject">${escH(m.subject)}</div>` : ''}
    <div class="msg-body">${escH(m.message)}</div>
    ${repliesHtml}
    <div class="reply-form" style="margin-top:24px;">
      <label class="reply-form__label">Write a Reply (internal note)</label>
      <form method="POST">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="id" value="${id}">
        <textarea name="body" placeholder="Type your reply or internal note here…" required></textarea>
        <div class="reply-form__actions">
          <button type="submit" class="btn-admin btn-admin--primary btn-admin--sm">
            <i class="fas fa-save"></i> Save Reply
          </button>
        </div>
      </form>
    </div>
  `;

  // Mark as read in DB + visually if message was unread
  if (m.is_read == 0) {
    ALL_MESSAGES[id].is_read = 1;
    const fd = new FormData();
    fd.append('action', 'mark_read');
    fd.append('id', id);
    fd.append('_ajax', '1');
    fetch('messages.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function () {
        // Decrement live badge count
        if (window.MC_NOTIF && window.applyNotifCounts) {
          window.MC_NOTIF.messages = Math.max(0, (window.MC_NOTIF.messages || 1) - 1);
          window.MC_NOTIF.total    = Math.max(0, (window.MC_NOTIF.total    || 1) - 1);
          window.applyNotifCounts(window.MC_NOTIF);
        }
      })
      .catch(function () {});

    const row = document.querySelector(`tr[onclick="openMessage(${id})"]`);
    if (row) {
      row.classList.remove('unread');
      const dot = row.querySelector('td:first-child span');
      if (dot) dot.remove();
      const subjectEl = row.querySelector('td:nth-child(3) span');
      if (subjectEl) { subjectEl.style.fontWeight = '400'; subjectEl.style.color = 'var(--text-mid)'; }
    }
    // Hide "N new" badge in page heading
    const headBadge = document.querySelector('.page-header__title span');
    if (headBadge) {
      const remaining = document.querySelectorAll('tr.unread').length - 1;
      if (remaining <= 0) headBadge.remove();
      else headBadge.textContent = remaining + ' new';
    }
  }
  document.getElementById('markUnreadBtn').style.display = 'inline-flex';

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
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) + ' at '
       + d.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
}

// Delete modal 
function confirmDelete(id, name) {
  document.getElementById('delId').value = id;
  document.getElementById('delModalSub').textContent = 'Delete message from "' + name + '"? This cannot be undone.';
  document.getElementById('delModal').classList.add('open');
}
function closeDelModal() {
  document.getElementById('delModal').classList.remove('open');
}
document.getElementById('delModal').addEventListener('click', function(e) {
  if (e.target === this) closeDelModal();
});

// Auto-open if ?view= set 
<?php if ($viewMsg): ?>
openMessage(<?= $viewMsg['id'] ?>);
<?php endif; ?>
</script>
</body>
</html>
