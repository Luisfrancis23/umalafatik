<?php
/**
 * Uma Lafatik — authentication & session helpers
 */
declare(strict_types=1);

/* --------------------------------------------------------- customers */

function current_customer(): ?array
{
    static $cache = null;
    static $checkedId = -1;

    $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return null;
    }
    if ($cache !== null && $checkedId === (int) $userId) {
        return $cache;
    }
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ? AND status = "active" LIMIT 1');
    $stmt->execute([(int) $userId]);
    $cache = $stmt->fetch() ?: null;
    $checkedId = (int) $userId;
    if ($cache === null) {
        // Account blocked or deleted — log out
        session_unset();
        session_destroy();
    }
    return $cache;
}

function is_logged_in(): bool
{
    return current_customer() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('info', 'Please sign in to continue.');
        redirect('/customer/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/customer/'));
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('/customer/');
    }
}

/** Verify customer credentials. Returns user row or throws LogicException with message. */
function customer_login(string $email, string $password): array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always hash something to blunt timing attacks
    if (!$user || !password_verify($password, $user['password'])) {
        password_hash($password, PASSWORD_DEFAULT);
        throw new LogicException('Invalid email or password.');
    }
    if (!password_verify($password, $user['password'])) {
        throw new LogicException('Invalid email or password.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];

    $upd = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $upd->execute([$user['id']]);

    // Merge any guest cart into the account
    merge_guest_cart($pdo, (int) $user['id']);

    return $user;
}

/* -------------------------------------------------------------- admins */

function current_admin(): ?array
{
    static $cache = null;

    $adminId = $_SESSION['admin_id'] ?? null;
    if ($adminId === null) {
        return null;
    }
    if ($cache !== null && (int) $cache['id'] === (int) $adminId) {
        return $cache;
    }
    $stmt = getDB()->prepare('SELECT * FROM admins WHERE id = ? AND status = "active" LIMIT 1');
    $stmt->execute([(int) $adminId]);
    $cache = $stmt->fetch() ?: null;
    if ($cache === null) {
        session_unset();
        session_destroy();
    }
    return $cache;
}

function is_admin_logged_in(): bool
{
    return current_admin() !== null;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        set_flash('error', 'Please log in to access the admin dashboard.');
        redirect('/admin/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/admin/'));
    }
}

function admin_login(string $username, string $password): array
{
    $pdo = getDB();

    // Simple throttling: 5 failed attempts => 5 minute lockout
    $lockKey = 'admin_login_lock';
    if (!empty($_SESSION[$lockKey]) && $_SESSION[$lockKey] > time()) {
        throw new LogicException('Too many failed attempts. Please try again in a few minutes.');
    }

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? AND status = "active" LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        password_hash($password, PASSWORD_DEFAULT);
        $_SESSION['admin_fail'] = (($_SESSION['admin_fail'] ?? 0) + 1);
        if (($_SESSION['admin_fail'] ?? 0) >= 5) {
            $_SESSION[$lockKey] = time() + 300;
            $_SESSION['admin_fail'] = 0;
        }
        throw new LogicException('Invalid username or password.');
    }

    unset($_SESSION['admin_fail'], $_SESSION[$lockKey]);

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];

    $upd = $pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?');
    $upd->execute([$admin['id']]);

    return $admin;
}

/* ------------------------------------------------------------ logout */

function logout_all(): void
{
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    session_destroy();
    session_start();
    session_regenerate_id(true);
}
