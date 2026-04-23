<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/site_settings.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$phone     = trim($_POST['phone']      ?? '');
$address1  = trim($_POST['address1']   ?? '');
$address2  = trim($_POST['address2']   ?? '');
$city      = trim($_POST['city']       ?? '');
$country   = trim($_POST['country']    ?? '');
$district  = trim($_POST['district']   ?? '');
$state     = trim($_POST['state']      ?? '');
$zip       = trim($_POST['zip']        ?? '');
$shipping  = trim($_POST['shipping_method'] ?? 'local-island');
$payment   = trim($_POST['payment_method']  ?? 'bank');

if (!$firstName || !$lastName || !$email || !$address1 || !$city || !$country) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

$items = cart_items();
if (empty($items)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Your cart is empty.']);
    exit;
}

$shippingCost = match($shipping) {
    'intl-express'   => 24.00,
    'intl-overnight' => 58.00,
    default          => 0.00,
};

$subtotal = cart_subtotal($items);
$total    = $subtotal + $shippingCost;

$db = db();

// Generate unique order number: MC-YYYY-XXXX
$year = date('Y');
$last = $db->query("SELECT order_number FROM orders WHERE order_number LIKE 'MC-{$year}%' ORDER BY id DESC LIMIT 1")->fetchColumn();
$seq  = $last ? ((int)substr($last, -4) + 1) : 1;
$orderNumber = 'MC-' . $year . str_pad($seq, 4, '0', STR_PAD_LEFT);

$customerId = $_SESSION['customer_id'] ?? null;
$shipState  = $country === 'LK' ? $district : $state;
$shipAddr   = $address1 . ($address2 ? ', ' . $address2 : '');

$payMap = [
    'bank'   => 'bank_transfer',
    'friMi'  => 'frimi',
    'ezCash' => 'ezcash',
    'card'   => 'card',
    'paypal' => 'paypal',
];
$payDb = $payMap[$payment] ?? 'bank_transfer';

$db->prepare("
    INSERT INTO orders (
        order_number, customer_id,
        customer_name, customer_email, customer_phone,
        ship_address, ship_city, ship_state, ship_zip, ship_country,
        subtotal_usd, shipping_usd, tax_usd, total_usd,
        payment_method, payment_status, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'unpaid', 'pending')
")->execute([
    $orderNumber, $customerId,
    $firstName . ' ' . $lastName, $email, $phone ?: null,
    $shipAddr, $city, $shipState ?: null, $zip ?: null, $country,
    $subtotal, $shippingCost, $total,
    $payDb,
]);

$orderId = (int)$db->lastInsertId();

$stItem = $db->prepare("
    INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, unit_price, line_total)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
foreach ($items as $item) {
    $stItem->execute([
        $orderId,
        $item['product_id'],
        $item['name'],
        $item['sku'] ?? null,
        $item['quantity'],
        $item['price_usd'],
        $item['price_usd'] * $item['quantity'],
    ]);
    $db->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?')
       ->execute([$item['quantity'], $item['product_id']]);
}

cart_clear();

// Order confirmation email to customer 
$itemRows = '';
foreach ($items as $item) {
    $itemRows .= '<tr>
      <td style="padding:10px 0;border-bottom:1px solid #f0ece4;font-size:14px;">' . htmlspecialchars($item['name']) . '</td>
      <td style="padding:10px 0;border-bottom:1px solid #f0ece4;font-size:14px;text-align:center;">' . (int)$item['quantity'] . '</td>
      <td style="padding:10px 0;border-bottom:1px solid #f0ece4;font-size:14px;text-align:right;">$' . number_format($item['price_usd'] * $item['quantity'], 2) . '</td>
    </tr>';
}

$payLabels = [
    'bank_transfer' => 'Bank Transfer',
    'frimi'         => 'FriMi',
    'ezcash'        => 'EzCash',
    'card'          => 'Credit / Debit Card',
    'paypal'        => 'PayPal',
];
$payLabel = $payLabels[$payDb] ?? ucfirst($payDb);
$waNum    = preg_replace('/[^0-9]/', '', get_site_setting('whatsapp_number', ''));
$waLink   = $waNum ? 'https://wa.me/' . $waNum . '?text=' . urlencode("Hi, I placed order $orderNumber and I'm sending the payment confirmation.") : '';

$confirmBody = '
<p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#444;">
  Thank you, <strong>' . htmlspecialchars($firstName) . '</strong>! Your order has been received and is being processed.
</p>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#f9f7f3;border-radius:6px;padding:16px 20px;">
  <tr>
    <td style="font-size:13px;color:#888;padding-bottom:4px;">Order Number</td>
    <td style="font-size:15px;font-weight:700;color:#c8a84b;text-align:right;">' . htmlspecialchars($orderNumber) . '</td>
  </tr>
  <tr>
    <td style="font-size:13px;color:#888;padding-bottom:4px;">Payment Method</td>
    <td style="font-size:14px;text-align:right;">' . htmlspecialchars($payLabel) . '</td>
  </tr>
</table>
<h3 style="margin:0 0 12px;font-size:15px;font-weight:600;color:#1a1a1a;border-bottom:1px solid #ede9e0;padding-bottom:8px;">Order Summary</h3>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <thead>
    <tr>
      <th style="text-align:left;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.5px;padding-bottom:8px;">Item</th>
      <th style="text-align:center;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.5px;padding-bottom:8px;">Qty</th>
      <th style="text-align:right;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.5px;padding-bottom:8px;">Total</th>
    </tr>
  </thead>
  <tbody>' . $itemRows . '</tbody>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
  <tr>
    <td style="font-size:13px;color:#888;padding:4px 0;">Subtotal</td>
    <td style="font-size:13px;text-align:right;">$' . number_format($subtotal, 2) . '</td>
  </tr>
  <tr>
    <td style="font-size:13px;color:#888;padding:4px 0;">Shipping</td>
    <td style="font-size:13px;text-align:right;">' . ($shippingCost > 0 ? '$' . number_format($shippingCost, 2) : 'Free') . '</td>
  </tr>
  <tr>
    <td style="font-size:15px;font-weight:700;padding:8px 0;border-top:1px solid #ede9e0;">Total</td>
    <td style="font-size:15px;font-weight:700;text-align:right;padding:8px 0;border-top:1px solid #ede9e0;color:#c8a84b;">$' . number_format($total, 2) . '</td>
  </tr>
</table>
' . ($waLink ? '<p style="margin:0 0 10px;font-size:14px;color:#444;">Please send your payment confirmation via WhatsApp:</p>
<a href="' . $waLink . '" style="display:inline-block;background:#25d366;color:#fff;text-decoration:none;padding:12px 24px;border-radius:5px;font-size:14px;font-weight:600;">&#128232; Send via WhatsApp</a>' : '') . '
<p style="margin:24px 0 0;font-size:13px;color:#888;line-height:1.6;">
  Keep your order number <strong>' . htmlspecialchars($orderNumber) . '</strong> handy for reference.
  We will notify you once your order is confirmed and dispatched.
</p>';

send_mail($email, $firstName . ' ' . $lastName, 'Order Confirmation – ' . $orderNumber, mail_wrap('Order Received!', $confirmBody));

// New order notification to store admin 
$storeEmail = get_site_setting('store_email', '');
if ($storeEmail) {
    $adminBody = '
<p style="margin:0 0 16px;font-size:15px;color:#444;">A new order has been placed on the store.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;background:#f9f7f3;border-radius:6px;padding:16px 20px;">
  <tr><td style="font-size:13px;color:#888;padding-bottom:4px;">Order</td><td style="font-weight:700;color:#c8a84b;text-align:right;">' . htmlspecialchars($orderNumber) . '</td></tr>
  <tr><td style="font-size:13px;color:#888;padding-bottom:4px;">Customer</td><td style="text-align:right;">' . htmlspecialchars($firstName . ' ' . $lastName) . '</td></tr>
  <tr><td style="font-size:13px;color:#888;padding-bottom:4px;">Email</td><td style="text-align:right;">' . htmlspecialchars($email) . '</td></tr>
  <tr><td style="font-size:13px;color:#888;padding-bottom:4px;">Total</td><td style="font-weight:700;text-align:right;">$' . number_format($total, 2) . '</td></tr>
  <tr><td style="font-size:13px;color:#888;">Payment</td><td style="text-align:right;">' . htmlspecialchars($payLabel) . '</td></tr>
</table>
<p style="margin:0;font-size:13px;color:#888;">Log in to the admin panel to manage this order.</p>';
    send_mail($storeEmail, get_site_setting('store_name', 'Mirabella Ceylon'), 'New Order: ' . $orderNumber, mail_wrap('New Order Received', $adminBody));
}

echo json_encode([
    'ok'           => true,
    'order_number' => $orderNumber,
    'total_usd'    => $total,
]);
