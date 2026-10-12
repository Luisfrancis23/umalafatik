<?php
/**
 * Uma Lafatik installer — creates the database and loads schema + seed data.
 * Usage: php sql/install.php
 */
$host = '127.0.0.1';
$user = 'root';
$pass = '';

// Connect without selecting a database first
$server = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$server->exec('CREATE DATABASE IF NOT EXISTS `umalafatik` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$server->exec('USE `umalafatik`');

$sql = (string) file_get_contents(__DIR__ . '/schema.sql');

// Remove the CREATE DATABASE / USE statements (already handled above)
$sql = preg_replace('/^CREATE DATABASE[^;]*;/msi', '', $sql);
$sql = preg_replace('/^USE\s+`?umalafatik`?;/msi', '', $sql);

// Split into individual statements (safe here: no ";\n" inside string literals)
$statements = array_filter(array_map('trim', explode(";\n", $sql)), function ($s) {
    return $s !== '' && str_starts_with($s, '--') === false || str_contains($s, 'CREATE') || str_contains($s, 'INSERT');
});

// Clean re-install: drop existing tables
$tables = ['notifications', 'payments', 'order_items', 'orders', 'cart_items', 'carts',
           'reservations', 'reviews', 'contact_messages', 'menu_items', 'categories',
           'users', 'admins', 'settings'];
$server->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach ($tables as $t) {
    $server->exec("DROP TABLE IF EXISTS `{$t}`");
}
$server->exec('SET FOREIGN_KEY_CHECKS = 1');

$count = 0;
foreach ($statements as $stmt) {
    // Skip pure comment lines
    $lines = array_filter(array_map('trim', explode("\n", $stmt)), fn($l) => $l !== '' && !str_starts_with($l, '--'));
    if (!$lines) continue;
    $server->exec(implode("\n", $lines));
    $count++;
}

echo "Schema created and seeded successfully ($count statements).\n";

// Verify
foreach (['admins', 'users', 'categories', 'menu_items', 'orders', 'reservations', 'reviews', 'settings', 'notifications'] as $table) {
    $n = $server->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    echo sprintf("  %-14s %d rows\n", $table, $n);
}
