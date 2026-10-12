<?php
/**
 * Uma Lafatik — shared helpers
 */
declare(strict_types=1);

/* ------------------------------------------------------------------ output */

/** Escape output for HTML context. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Current full URL (for canonical links / redirects). */
function current_url(): string
{
    $url = BASE_URL . ($_SERVER['REQUEST_URI'] ?? '/');
    return $url;
}

/** Build a site-relative URL. */
function url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

/** Asset URL helper. */
function asset(string $path): string
{
    return BASE_PATH . '/assets/' . ltrim($path, '/');
}

/* ------------------------------------------------------------ validation */

/** Trim + basic string cleanup. Does NOT escape — escape on output. */
function str_in(?string $value, int $max = 255): string
{
    $value = trim((string) $value);
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function email_in(?string $value): string
{
    return filter_var(trim((string) $value), FILTER_SANITIZE_EMAIL) ?: '';
}

function phone_in(?string $value): string
{
    // Allow digits, spaces, +, -, (), and dots
    $value = trim((string) $value);
    $value = preg_replace('/[^\d\s\+\-\(\)\.]/', '', $value) ?? '';
    return substr($value, 0, 25);
}

function int_in($value, int $min = 0, int $max = PHP_INT_MAX): int
{
    $v = filter_var($value, FILTER_VALIDATE_INT);
    if ($v === false) return $min;
    return max($min, min($max, $v));
}

function money_in($value): float
{
    $v = filter_var($value, FILTER_VALIDATE_FLOAT);
    return $v === false ? 0.0 : round(max(0.0, (float) $v), 2);
}

function is_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Validate a slug (letters, numbers, dashes). */
function slugify(string $text, int $max = 120): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-]/u', '', $text) ?? '';
    $text = preg_replace('/[\s\-]+/', '-', trim($text)) ?? '';
    return substr($text, 0, $max);
}

/* ------------------------------------------------------------ navigation */

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* ----------------------------------------------------------------- flash */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

function flash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = in_array($f['type'], ['success', 'error', 'info', 'warning'], true) ? $f['type'] : 'info';
    return '<div class="flash flash-' . $type . '" role="alert">' . e($f['msg']) . '</div>';
}

/* ------------------------------------------------------------------ CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(?string $token): bool
{
    if ($token === null || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
}

/* ------------------------------------------------------------- formatting */

function format_price($amount): string
{
    return CURRENCY_SYMBOL . number_format((float) $amount, 2);
}

function format_date(string $date, string $format = 'M j, Y'): string
{
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function format_time(string $time): string
{
    try {
        $dt = new DateTime($time);
        return $dt->format('g:i A');
    } catch (Exception $e) {
        return $time;
    }
}

/* ------------------------------------------------------------ pagination */

/**
 * @return array{page:int, per_page:int, offset:int, pages:int}
 */
function paginate(int $total, int $page = 1, int $perPage = ITEMS_PER_PAGE): array
{
    $perPage = max(1, $perPage);
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = max(1, min($pages, $page));
    return [
        'page'     => $page,
        'per_page' => $perPage,
        'offset'   => ($page - 1) * $perPage,
        'pages'    => $pages,
    ];
}

/** Render pagination links, preserving existing query params. */
function render_pagination(int $page, int $pages, string $baseQuery = ''): string
{
    if ($pages <= 1) {
        return '';
    }
    $params = $_GET;
    unset($params['page']);
    $qs = $params ? http_build_query($params) . '&' : '';
    $base = ($baseQuery !== '' ? $baseQuery : strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) . '?' . $qs;

    $out = '<nav class="pagination" aria-label="Pagination">';
    if ($page > 1) {
        $out .= '<a class="page-link" href="' . e($base . 'page=' . ($page - 1)) . '" aria-label="Previous">&lsaquo; Prev</a>';
    }
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    if ($start > 1) {
        $out .= '<a class="page-link" href="' . e($base . 'page=1') . '">1</a>';
        if ($start > 2) { $out .= '<span class="page-dots">&hellip;</span>'; }
    }
    for ($i = $start; $i <= $end; $i++) {
        $out .= $i === $page
            ? '<span class="page-link active" aria-current="page">' . $i . '</span>'
            : '<a class="page-link" href="' . e($base . 'page=' . $i) . '">' . $i . '</a>';
    }
    if ($end < $pages) {
        if ($end < $pages - 1) { $out .= '<span class="page-dots">&hellip;</span>'; }
        $out .= '<a class="page-link" href="' . e($base . 'page=' . $pages) . '">' . $pages . '</a>';
    }
    if ($page < $pages) {
        $out .= '<a class="page-link" href="' . e($base . 'page=' . ($page + 1)) . '" aria-label="Next">Next &rsaquo;</a>';
    }
    return $out . '</nav>';
}

/* -------------------------------------------------------------- settings */

/** Fetch a setting value from the DB (cached per request). */
function setting(string $key, $default = null)
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $stmt = getDB()->query('SELECT setting_key, setting_value FROM settings');
            foreach ($stmt->fetchAll() as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            $cache = [];
        }
    }

    if (!array_key_exists($key, $cache)) {
        return $default;
    }
    return $cache[$key];
}

/* -------------------------------------------------------------- orders */

/** Generate a unique order number like UL-2603-K7XQ2M */
function generate_order_number(PDO $pdo): string
{
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < 20; $i++) {
        $suffix = '';
        for ($j = 0; $j < 6; $j++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $number = 'UL-' . date('ym') . '-' . $suffix;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_number = ?');
        $stmt->execute([$number]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $number;
        }
    }
    // Extremely unlikely fallback
    return 'UL-' . date('ym') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/** Push an in-app notification to a user. */
function push_notification(PDO $pdo, int $userId, string $type, string $message, ?int $orderId = null, ?int $reservationId = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, order_id, reservation_id, type, message)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $orderId, $reservationId, $type, $message]);
}

/** Count unread notifications for a user. */
function unread_notifications(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/** Status badge HTML. */
function status_badge(string $status, string $kind = 'order'): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(ORDER_STATUSES[$status] ?? $status) . '</span>';
}

/** Star rating HTML. */
function stars(int $rating, bool $numeric = false): string
{
    $rating = max(1, min(5, $rating));
    $full  = str_repeat('<svg class="star star-full" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.2 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z"/></svg>', $rating);
    $empty = str_repeat('<svg class="star star-empty" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.2 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z"/></svg>', 5 - $rating);
    $out = '<span class="stars" role="img" aria-label="' . $rating . ' out of 5 stars">' . $full . $empty . '</span>';
    return $numeric ? $out : $out;
}

/**
 * Handle an image upload. Returns the stored relative path or throws.
 *
 * @param array $file $_FILES entry
 * @param string $subfolder e.g. 'menu'
 * @return string relative path like "uploads/menu/xxxx.jpg"
 */
function upload_image(array $file, string $subfolder = 'menu'): string
{
    if (!isset($file['error']) || !is_int($file['error'])) {
        throw new RuntimeException('Invalid upload data.');
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file was uploaded.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('The uploaded file is too large.');
        default:
            throw new RuntimeException('Upload failed with error code ' . $file['error'] . '.');
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('Image must be 5 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        throw new RuntimeException('Only JPG, PNG, GIF and WebP images are allowed.');
    }

    $dir = UPLOAD_DIR . '/' . $subfolder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create upload directory.');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }

    return 'uploads/' . $subfolder . '/' . $name;
}

/** Delete an uploaded file if it exists and is not a default asset. */
function delete_upload(?string $path): void
{
    if (!$path) return;
    if (str_starts_with($path, 'uploads/')) {
        $full = APP_ROOT . '/' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

/** Default dish image path when none set. */
function default_dish_image(): string
{
    return asset('images/dishes/default.svg');
}

/** Resolve a stored image reference to a full URL. */
function image_url(?string $path, string $fallback = ''): string
{
    if (!$path) {
        return $fallback ?: default_dish_image();
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'assets/')) {
        return str_starts_with($path, 'assets/') ? BASE_PATH . '/' . $path : $path;
    }
    return BASE_PATH . '/' . $path;
}
