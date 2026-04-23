<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/includes/db.php';

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode(['ok' => true, 'results' => []]);
    exit;
}

$db   = db();
$like = '%' . $q . '%';

$st = $db->prepare("
    SELECT p.id, p.name, p.slug, p.price_usd, p.image_main, p.weight_ct,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1
      AND (p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)
    ORDER BY p.is_featured DESC, p.name ASC
    LIMIT 8
");
$st->execute([$like, $like, $like]);
$rows = $st->fetchAll();

$results = array_map(function($r) {
    $weight = $r['weight_ct']
        ? rtrim(rtrim(number_format((float)$r['weight_ct'], 2), '0'), '.') . 'ct'
        : null;
    return [
        'name'     => $r['name'] . ($weight ? ' — ' . $weight : ''),
        'cat'      => $r['category_name'] ?? '',
        'price'    => '$' . number_format((float)$r['price_usd'], 0),
        'img'      => $r['image_main'] ?? '',
        'href'     => 'product-detail.php?slug=' . urlencode($r['slug']),
    ];
}, $rows);

echo json_encode(['ok' => true, 'results' => $results]);
