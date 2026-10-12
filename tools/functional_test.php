<?php
/**
 * End-to-end functional test:
 * admin login → menu edit → customer register → add to cart → checkout →
 * order confirmation → admin status change → customer notification
 */
$base = 'http://127.0.0.1/umalafatik/';
$pass = 0;
$fail = 0;

function check(string $label, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "  PASS  $label\n"; }
    else { $fail++; echo "  FAIL  $label" . ($detail ? " — $detail" : '') . "\n"; }
}

function req(string $url, array $post = null, $cookies = null, array $extraHeaders = []): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
    ];
    if ($cookies !== null) {
        $opts[CURLOPT_COOKIEFILE] = $cookies;
        $opts[CURLOPT_COOKIEJAR] = $cookies;
    }
    if ($post !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    if ($extraHeaders) {
        $opts[CURLOPT_HTTPHEADER] = $extraHeaders;
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

// Fetch a page to extract CSRF token + session cookie
function getWithSession(string $url, string $cookieFile): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

function csrfFrom(string $html): string
{
    if (preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

$adminCookie = tempnam(sys_get_temp_dir(), 'adm_');
$custCookie = tempnam(sys_get_temp_dir(), 'cst_');

echo "== Admin login ==\n";
[$code, $html] = getWithSession($base . 'admin/login.php', $adminCookie);
$token = csrfFrom($html);
check('Admin login page loads', $code === 200 && $token !== '');

[$code, $html] = req($base . 'admin/login.php', [
    'csrf_token' => $token,
    'username' => 'admin',
    'password' => 'Admin@123',
], $adminCookie);
check('Admin login succeeds', $code === 200 && str_contains($html, 'Overview'), "code=$code");

echo "== Admin: add a dish ==\n";
[$code, $html] = getWithSession($base . 'admin/menu-item.php', $adminCookie);
$token = csrfFrom($html);
[$code, $html] = req($base . 'admin/menu-item.php', [
    'csrf_token' => $token,
    'category_id' => '1',
    'name' => 'Test Dish ' . date('His'),
    'description' => 'A functional test dish with a sufficiently long description.',
    'price' => '19.99',
    'image' => '',
    'is_featured' => '1',
    'is_available' => '1',
    'sort_order' => '99',
], $adminCookie);
check('Dish created', $code === 200 && str_contains($html, 'Dish added'), "code=$code");

echo "== Customer registration ==\n";
$email = 'tester' . date('His') . '@example.com';
[$code, $html] = getWithSession($base . 'customer/register.php', $custCookie);
$token = csrfFrom($html);
[$code, $html] = req($base . 'customer/register.php', [
    'csrf_token' => $token,
    'first_name' => 'Test',
    'last_name' => 'Customer',
    'email' => $email,
    'phone' => '+966 50 000 1234',
    'password' => 'Testpass123',
    'password_confirm' => 'Testpass123',
    'default_address' => '5 Test Street',
    'city' => 'Riyadh',
    'postal_code' => '12345',
], $custCookie);
check('Customer registration + auto-login', $code === 200 && str_contains($html, 'My Dashboard'), "code=$code");

echo "== Add to cart (AJAX) ==\n";
// CSRF token from the dashboard page
[$code, $html] = getWithSession($base . 'customer/menu.php', $custCookie);
$token = csrfFrom($html);
$ch = curl_init($base . 'api/cart.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['action' => 'add', 'menu_item_id' => '4', 'quantity' => '2', 'csrf_token' => $token]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $custCookie,
    CURLOPT_COOKIEJAR => $custCookie,
]);
$resp = json_decode((string) curl_exec($ch), true);
curl_close($ch);
check('Add item #4 (Royal Kabsa) x2', ($resp['success'] ?? false) === true && ($resp['count'] ?? 0) === 2, json_encode($resp));

// Add a second item
$ch = curl_init($base . 'api/cart.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['action' => 'add', 'menu_item_id' => '13', 'quantity' => '1', 'csrf_token' => $token]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $custCookie,
    CURLOPT_COOKIEJAR => $custCookie,
]);
$resp2 = json_decode((string) curl_exec($ch), true);
curl_close($ch);
check('Add item #13 (Velvet Kunafa)', ($resp2['success'] ?? false) === true && ($resp2['count'] ?? 0) === 3, json_encode($resp2));

echo "== Checkout ==\n";
[$code, $html] = getWithSession($base . 'customer/checkout.php', $custCookie);
$token = csrfFrom($html);
check('Checkout page shows cart items', $code === 200 && str_contains($html, 'Royal Kabsa') && str_contains($html, 'Velvet Kunafa'));

[$code, $html] = req($base . 'customer/checkout.php', [
    'csrf_token' => $token,
    'delivery_type' => 'delivery',
    'name' => 'Test Customer',
    'email' => $email,
    'phone' => '+966 50 000 1234',
    'address' => '5 Test Street, Al Faisaliah',
    'city' => 'Riyadh',
    'postal_code' => '12345',
    'notes' => 'Ring the bell.',
    'payment_method' => 'cod',
], $custCookie);
// Expect redirect to order-confirmation.php
check('Order placed, redirect to confirmation', $code === 200 && str_contains($html, 'order-confirmation') || str_contains($html, 'Your order is confirmed'), "code=$code");
preg_match('/order_number[^<]*<[^>]*>(UL-[^<]+)</', $html, $m);
$orderNumber = $m[1] ?? '';
check('Order number displayed', $orderNumber !== '', 'html snippet: ' . substr(strip_tags($html), 0, 200));

echo "== Order in DB ==\n";
$pdo = new PDO('mysql:host=127.0.0.1;dbname=umalafatik;charset=utf8mb4', 'root', '');
$stmt = $pdo->prepare('SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items FROM orders o WHERE o.order_number = ?');
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();
check('Order saved with 2 items', $order !== false && (int) $order['items'] === 2);
check('Order status is pending', $order['status'] === 'pending');
$expected = round(2 * 24.00 + 1 * 12.00, 2); // 60.00 subtotal -> free delivery
$tax = round($expected * 0.08, 2);
check('Totals computed correctly (free delivery over $50)', abs((float) $order['subtotal'] - $expected) < 0.01 && (float) $order['delivery_fee'] === 0.0 && abs((float) $order['total'] - round($expected + $tax, 2)) < 0.01, "subtotal={$order['subtotal']} fee={$order['delivery_fee']} total={$order['total']}");

echo "== Cart cleared after order ==\n";
$ch = curl_init($base . 'api/cart.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['action' => 'summary', 'csrf_token' => $token]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $custCookie,
    CURLOPT_COOKIEJAR => $custCookie,
]);
$resp3 = json_decode((string) curl_exec($ch), true);
curl_close($ch);
check('Cart count is 0 after checkout', ($resp3['count'] ?? -1) === 0, json_encode($resp3));

echo "== Admin updates order status ==\n";
[$code, $html] = getWithSession($base . 'admin/orders.php', $adminCookie);
$token = csrfFrom($html);
[$code, $html] = req($base . 'admin/orders.php', [
    'csrf_token' => $token,
    'order_id' => (string) $order['id'],
    'status' => 'confirmed',
    'update_status' => '1',
], $adminCookie);
$stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
$stmt->execute([$order['id']]);
check('Order status updated to confirmed', $stmt->fetchColumn() === 'confirmed');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = "order_status"');
$stmt->execute([$order['user_id']]);
check('Customer notification created', (int) $stmt->fetchColumn() >= 1);

echo "== Customer track page ==\n";
[$code, $html] = getWithSession($base . 'customer/track.php?order_id=' . $order['id'], $custCookie);
check('Track page shows live order + polling', $code === 200 && str_contains($html, 'UMALF_TRACK') && str_contains($html, $orderNumber));

echo "== Reservation flow ==\n";
[$code, $html] = getWithSession($base . 'reservation.php', $custCookie);
$token = csrfFrom($html);
[$code, $html] = req($base . 'reservation.php', [
    'csrf_token' => $token,
    'name' => 'Test Customer',
    'email' => $email,
    'phone' => '+966 50 000 1234',
    'guests' => '4',
    'date' => date('Y-m-d', strtotime('+2 days')),
    'time' => '19:30',
    'seating' => 'Indoor',
    'requests' => 'Window seat please.',
], $custCookie);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE email = ? AND guests = 4');
$stmt->execute([$email]);
check('Reservation stored', (int) $stmt->fetchColumn() >= 1);

echo "== Access control ==\n";
// Customer must not reach admin
$noCookie = tempnam(sys_get_temp_dir(), 'anon_');
[$code, $html] = getWithSession($base . 'admin/index.php', $noCookie);
check('Admin dashboard requires login (redirect)', $code === 200 && str_contains($html, 'Admin Sign In'));

// Admin must not reach customer dashboard
[$code, $html] = getWithSession($base . 'customer/index.php', $adminCookie);
check('Customer dashboard rejects admin session (redirect to login)', str_contains($html, 'Sign In') || str_contains($html, 'Please sign in'));

// Anonymous API call rejected
$ch = curl_init($base . 'api/cart.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['action' => 'add', 'menu_item_id' => '4', 'csrf_token' => 'invalid']),
    CURLOPT_RETURNTRANSFER => true,
]);
$resp4 = json_decode((string) curl_exec($ch), true);
curl_close($ch);
check('API rejects invalid CSRF', ($resp4['success'] ?? true) === false);

echo "== CSRF protection on forms ==\n";
[$code, $html] = req($base . 'admin/login.php', ['username' => 'admin', 'password' => 'Admin@123'], null);
check('Admin login without CSRF token is rejected', !str_contains($html, 'Overview'));

echo "\n========================================\n";
echo "RESULTS: $pass passed, $fail failed\n";
echo $fail === 0 ? "ALL TESTS PASSED\n" : "SOME TESTS FAILED\n";
