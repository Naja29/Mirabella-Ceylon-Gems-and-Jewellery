<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/customer_auth.php';

header('Content-Type: application/json');

if (!customer_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Please sign in to use your wishlist.', 'login' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$action     = trim($_POST['action']     ?? '');
$productId  = (int)($_POST['product_id'] ?? 0);
$customerId = (int)$_SESSION['customer_id'];

$db = db();

// Ensure table exists (auto-migration for dev)
$db->exec("CREATE TABLE IF NOT EXISTS `wishlists` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `product_id`  INT UNSIGNED NOT NULL,
  `added_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wish` (`customer_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function wishlistCount(PDO $db, int $customerId): int {
    return (int)$db->prepare('SELECT COUNT(*) FROM wishlists WHERE customer_id = ?')
        ->execute([$customerId]) ? $db->query("SELECT COUNT(*) FROM wishlists WHERE customer_id = $customerId")->fetchColumn() : 0;
}

function getCount(PDO $db, int $customerId): int {
    $st = $db->prepare('SELECT COUNT(*) FROM wishlists WHERE customer_id = ?');
    $st->execute([$customerId]);
    return (int)$st->fetchColumn();
}

if ($action === 'toggle') {
    if (!$productId) {
        echo json_encode(['ok' => false, 'error' => 'Invalid product.']);
        exit;
    }
    $chk = $db->prepare('SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?');
    $chk->execute([$customerId, $productId]);
    $exists = $chk->fetchColumn();

    if ($exists) {
        $db->prepare('DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?')
           ->execute([$customerId, $productId]);
        echo json_encode(['ok' => true, 'action' => 'removed', 'count' => getCount($db, $customerId)]);
    } else {
        // Verify product exists
        $p = $db->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
        $p->execute([$productId]);
        if (!$p->fetchColumn()) {
            echo json_encode(['ok' => false, 'error' => 'Product not found.']);
            exit;
        }
        try {
            $db->prepare('INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)')
               ->execute([$customerId, $productId]);
        } catch (\PDOException $e) {
            // Duplicate — already exists
        }
        echo json_encode(['ok' => true, 'action' => 'added', 'count' => getCount($db, $customerId)]);
    }

} elseif ($action === 'remove') {
    if (!$productId) {
        echo json_encode(['ok' => false, 'error' => 'Invalid product.']);
        exit;
    }
    $db->prepare('DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?')
       ->execute([$customerId, $productId]);
    echo json_encode(['ok' => true, 'count' => getCount($db, $customerId)]);

} elseif ($action === 'clear') {
    $db->prepare('DELETE FROM wishlists WHERE customer_id = ?')->execute([$customerId]);
    echo json_encode(['ok' => true, 'count' => 0]);

} elseif ($action === 'count') {
    echo json_encode(['ok' => true, 'count' => getCount($db, $customerId)]);

} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
}
