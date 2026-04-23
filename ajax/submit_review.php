<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/customer_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!customer_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Please sign in to submit a review.']);
    exit;
}

$productId  = (int)($_POST['product_id'] ?? 0);
$rating     = (int)($_POST['rating']     ?? 5);
$title      = trim($_POST['title']       ?? '');
$body       = trim($_POST['body']        ?? '');

if (!$productId) {
    echo json_encode(['ok' => false, 'error' => 'Invalid product.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['ok' => false, 'error' => 'Rating must be between 1 and 5.']);
    exit;
}
if (!$body) {
    echo json_encode(['ok' => false, 'error' => 'Please write your review.']);
    exit;
}

$db = db();

// Verify product exists
$chkP = $db->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
$chkP->execute([$productId]);
if (!$chkP->fetchColumn()) {
    echo json_encode(['ok' => false, 'error' => 'Product not found.']);
    exit;
}

// Prevent duplicate reviews
$chkR = $db->prepare('SELECT id FROM reviews WHERE product_id = ? AND customer_id = ? LIMIT 1');
$chkR->execute([$productId, $_SESSION['customer_id']]);
if ($chkR->fetchColumn()) {
    echo json_encode(['ok' => false, 'error' => 'You have already reviewed this product.']);
    exit;
}

$customerId = $_SESSION['customer_id'];
$name       = trim(($_SESSION['customer_fname'] ?? '') . ' ' . ($_SESSION['customer_lname'] ?? ''));

$db->prepare("
    INSERT INTO reviews (product_id, customer_id, reviewer_name, rating, title, body, is_approved)
    VALUES (?, ?, ?, ?, ?, ?, 0)
")->execute([$productId, $customerId, $name ?: 'Anonymous', $rating, $title ?: null, $body]);

echo json_encode(['ok' => true]);
