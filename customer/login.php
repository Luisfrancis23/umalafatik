<?php
/**
 * Customer — sign in
 */
require_once __DIR__ . '/../config/config.php';

require_guest();

$error = '';
$next = str_in($_GET['next'] ?? '', 500);
if ($next === '' || !str_starts_with($next, '/')) {
    $next = '/customer/';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired. Please try again.');
        redirect('/customer/login.php');
    }

    $email = email_in($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!is_valid_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } else {
        try {
            customer_login($email, $password);
            set_flash('success', 'Welcome back, ' . explode(' ', trim((string) ($_SESSION['name'] ?? ''))) . '!');
            redirect($next);
        } catch (LogicException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$page_title = 'Sign In — ' . SITE_NAME;
$page_desc = 'Sign in to your Uma Lafatik account to order food, track orders and manage reservations.';
$canonical = BASE_URL . '/customer/login.php';
$extra_css = [url('/assets/css/dashboard.css')];

require_once __DIR__ . '/../includes/head.php';
?>

<section class="auth-wrap">
  <div class="auth-card">
    <a href="<?= url('/') ?>" class="brand">
      <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
        <rect x="2" y="2" width="44" height="44" rx="12" fill="#241e1a"/>
        <path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="#d4a24e" transform="translate(3.5 0) scale(0.86)"/>
        <path d="M30 12v24" stroke="#d4a24e" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <span class="brand-name">Uma <em>Lafatik</em></span>
    </a>
    <p class="auth-sub">Welcome back — your table and your favourites are waiting.</p>

    <?php if ($error): ?>
    <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <?= flash() ?>

    <form method="post" action="<?= url('/customer/login.php') ?>">
      <?= csrf_input() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="field" style="margin-bottom:1.2rem">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email" autofocus>
      </div>
      <div class="field" style="margin-bottom:1.6rem">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-terra btn-wide">Sign In</button>
    </form>

    <p class="auth-alt">New to Uma Lafatik? <a href="<?= url('/customer/register.php') ?>">Create an account</a></p>
    <p class="auth-alt" style="margin-top:0.8rem"><a href="<?= url('/customer/track.php') ?>">Track an order without an account</a></p>
  </div>
</section>

</body>
</html>
