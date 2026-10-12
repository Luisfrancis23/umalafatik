<?php
$base = 'http://127.0.0.1/umalafatik/';
$pages = [
    // public
    'index.php', 'menu.php', 'about.php', 'gallery.php', 'reviews.php',
    'reservation.php', 'contact.php',
    // customer
    'customer/login.php', 'customer/register.php', 'customer/index.php',
    'customer/menu.php', 'customer/cart.php', 'customer/checkout.php',
    'customer/orders.php', 'customer/track.php', 'customer/notifications.php',
    'customer/reservations.php', 'customer/profile.php', 'customer/logout.php',
    // admin
    'admin/login.php', 'admin/index.php', 'admin/orders.php',
    'admin/order-detail.php', 'admin/menu-items.php', 'admin/menu-item.php',
    'admin/categories.php', 'admin/reservations.php', 'admin/customers.php',
    'admin/reviews.php', 'admin/reports.php', 'admin/settings.php', 'admin/logout.php',
    // api
    'api/order-status.php', 'api/notifications.php',
];

$fail = 0;
foreach ($pages as $p) {
    $ch = curl_init($base . $p);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $ok = $code === 200 || $code === 302;
    if (!$ok) { $fail++; }
    printf("%-32s HTTP %d  %s\n", $p, $code, $err ?: ($ok ? 'OK' : 'UNEXPECTED'));
}
echo $fail ? "\n$fail FAILURES\n" : "\nAll pages respond correctly.\n";
