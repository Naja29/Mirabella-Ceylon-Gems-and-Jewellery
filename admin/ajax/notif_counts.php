<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    $db = db();
    $data = [
        'orders'    => (int)$db->query("SELECT COUNT(*) FROM orders   WHERE status    = 'pending'")->fetchColumn(),
        'messages'  => (int)$db->query("SELECT COUNT(*) FROM messages WHERE is_read   = 0")->fetchColumn(),
        'reviews'   => (int)$db->query("SELECT COUNT(*) FROM reviews  WHERE is_approved = 0")->fetchColumn(),
        'customers' => (int)$db->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    ];
    $data['total'] = $data['orders'] + $data['messages'] + $data['reviews'];
    echo json_encode($data);
} catch (Throwable $e) {
    echo json_encode(['orders'=>0,'messages'=>0,'reviews'=>0,'customers'=>0,'total'=>0]);
}
