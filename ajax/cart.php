<?php
require_once __DIR__ . '/../includes/cart.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty        = max(1, (int)($_POST['qty'] ?? 1));
        if ($product_id) cart_add($product_id, $qty);
        echo json_encode(['ok' => true, 'count' => cart_count()]);
        break;

    case 'update':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty        = (int)($_POST['qty'] ?? 1);
        if ($product_id) cart_update($product_id, $qty);
        echo json_encode(['ok' => true, 'count' => cart_count()]);
        break;

    case 'remove':
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id) cart_remove($product_id);
        echo json_encode(['ok' => true, 'count' => cart_count()]);
        break;

    case 'count':
        echo json_encode(['ok' => true, 'count' => cart_count()]);
        break;

    default:
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}
