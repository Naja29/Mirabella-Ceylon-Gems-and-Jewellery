<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/customer_auth.php';

header('Content-Type: application/json');

if (!customer_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$enabled = ($_POST['enabled'] ?? '0') === '1';
$email   = $_SESSION['customer_email'];
$db      = db();

if ($enabled) {
    $db->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)")->execute([$email]);
    $db->prepare("UPDATE newsletter_subscribers SET is_active = 1 WHERE email = ?")->execute([$email]);
} else {
    $db->prepare("UPDATE newsletter_subscribers SET is_active = 0 WHERE email = ?")->execute([$email]);
}

echo json_encode(['ok' => true]);
