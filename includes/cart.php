<?php

if (session_status() === PHP_SESSION_NONE) session_start();

function _cart_db(): PDO {
    static $db;
    if (!$db) {
        require_once __DIR__ . '/../admin/includes/db.php';
        $db = db();
    }
    return $db;
}

function cart_get_id(): int {
    $db          = _cart_db();
    $key         = session_id();
    $customer_id = $_SESSION['customer_id'] ?? null;

    $st = $db->prepare('SELECT id FROM cart_sessions WHERE session_key = ?');
    $st->execute([$key]);
    $row = $st->fetch();

    if ($row) {
        if ($customer_id) {
            $db->prepare('UPDATE cart_sessions SET customer_id = ? WHERE id = ?')
               ->execute([$customer_id, $row['id']]);
        }
        return (int)$row['id'];
    }

    $db->prepare('INSERT INTO cart_sessions (session_key, customer_id) VALUES (?, ?)')
       ->execute([$key, $customer_id]);
    return (int)$db->lastInsertId();
}

function cart_add(int $product_id, int $qty = 1): void {
    $db = _cart_db();

    $p = $db->prepare('SELECT stock FROM products WHERE id = ? AND is_active = 1');
    $p->execute([$product_id]);
    $stock = $p->fetchColumn();
    if ($stock === false) return;

    $cart_id = cart_get_id();
    $db->prepare("
        INSERT INTO cart_items (cart_id, product_id, quantity)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)
    ")->execute([$cart_id, $product_id, $qty, (int)$stock]);
}

function cart_update(int $product_id, int $qty): void {
    if ($qty <= 0) { cart_remove($product_id); return; }

    $db = _cart_db();
    $p  = $db->prepare('SELECT stock FROM products WHERE id = ?');
    $p->execute([$product_id]);
    $stock = (int)($p->fetchColumn() ?: 1);
    $qty   = min($qty, $stock);

    $db->prepare('UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?')
       ->execute([$qty, cart_get_id(), $product_id]);
}

function cart_remove(int $product_id): void {
    $db = _cart_db();
    $db->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?')
       ->execute([cart_get_id(), $product_id]);
}

function cart_clear(): void {
    $db = _cart_db();
    $db->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([cart_get_id()]);
}

function cart_items(): array {
    $db = _cart_db();
    $st = $db->prepare("
        SELECT ci.product_id, ci.quantity,
               p.name, p.slug, p.sku, p.price_usd, p.image_main,
               p.weight_ct, p.cut, p.certification, p.stock,
               c.name AS category_name
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id AND p.is_active = 1
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE ci.cart_id = ?
        ORDER BY ci.added_at
    ");
    $st->execute([cart_get_id()]);
    return $st->fetchAll();
}

function cart_count(): int {
    $db = _cart_db();
    $st = $db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?');
    $st->execute([cart_get_id()]);
    return (int)$st->fetchColumn();
}

function cart_subtotal(array $items): float {
    return array_sum(array_map(fn($i) => $i['price_usd'] * $i['quantity'], $items));
}
