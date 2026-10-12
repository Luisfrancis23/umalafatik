<?php
/**
 * Uma Lafatik — Application configuration
 * Native PHP · No frameworks
 */
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Error handling (disable display in production, keep logging)
// ---------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '1');   // set to '0' on a production server
ini_set('log_errors', '1');

// ---------------------------------------------------------------------------
// Paths & URLs
// ---------------------------------------------------------------------------
define('APP_ROOT', dirname(__DIR__));

$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
$appRootUrl = str_replace('\\', '/', APP_ROOT);
$basePath = $docRoot !== '' ? str_replace($docRoot, '', $appRootUrl) : '';
$basePath = '/' . trim($basePath, '/');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('BASE_PATH', $basePath);                       // e.g. /umalafatik
define('BASE_URL', rtrim($scheme . $host . $basePath, '/'));
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// ---------------------------------------------------------------------------
// Site constants
// ---------------------------------------------------------------------------
define('SITE_NAME', 'Uma Lafatik');
define('SITE_TAGLINE', 'A Taste Worth Remembering');
define('SITE_EMAIL', 'hello@umalafatik.com');
define('SITE_PHONE', '+966 50 000 0000');
define('SITE_ADDRESS', '12 Palm Avenue, Downtown District');

define('CURRENCY_SYMBOL', '$');
define('DELIVERY_FEE', 5.00);          // default, can be overridden in settings
define('FREE_DELIVERY_MIN', 50.00);
define('TAX_RATE', 0.08);              // 8%
define('ITEMS_PER_PAGE', 10);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

define('ORDER_STATUSES', [
    'pending'         => 'Pending',
    'confirmed'       => 'Confirmed',
    'preparing'       => 'Preparing',
    'ready'           => 'Ready',
    'out_for_delivery'=> 'Out for Delivery',
    'completed'       => 'Completed',
    'cancelled'       => 'Cancelled',
]);
define('ORDER_STEPS', ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed']);
define('PAYMENT_METHODS', [
    'cod'   => 'Cash on Delivery',
    'card'  => 'Credit / Debit Card',
    'bank'  => 'Bank Transfer',
]);

// ---------------------------------------------------------------------------
// Secure session
// ---------------------------------------------------------------------------
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => BASE_PATH !== '/' ? BASE_PATH : '/',
    'domain'   => '',
    'secure'   => $scheme === 'https://',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('UMALF_SESSION');
session_start();

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
