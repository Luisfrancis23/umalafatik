<?php
/**
 * Admin — secure login
 */
require_once __DIR__ . '/../config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Form session expired. Please try again.';
    } else {
        $username = str_in($_POST['username'] ?? '', 50);
        $password = (string) ($_POST['password'] ?? '');

        try {
            admin_login($username, $password);
            redirect('/admin/');
        } catch (LogicException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$page_title = 'Admin Sign In — ' . SITE_NAME;
$page_desc = 'Administrative access for Uma Lafatik.';
$canonical = BASE_URL . '/admin/login.php';
$extra_css = [url('/assets/css/dashboard.css')];

require_once __DIR__ . '/../includes/head.php';
?>

<section class="auth-wrap">
  <div class="auth-card">
    <a href="<?= url('/') ?>" class="brand" style="justify-content:center; width:100%">
      <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
        <rect x="2" y="2" width="44" height="44" rx="12" fill="#241e1a"/>
        <path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="#d4a24e" transform="translate(3.5 0) scale(0.86)"/>
        <path d="M30 12v24" stroke="#d4a24e" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <span class="brand-name">Uma <em>Lafatik</em> <small style="font-size:0.7rem; background:var(--terra); color:#fff; padding:0.15rem 0.5rem; border-radius:999px; vertical-align:middle; letter-spacing:0.12em; text-transform:uppercase; font-family:var(--font-body)">Admin</small></span>
    </a>
    <p class="auth-sub">Staff sign in</p>

    <?php if ($error): ?>
    <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/admin/login.php') ?>">
      <?= csrf_input() ?>
      <div class="field" style="margin-bottom:1.2rem">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required maxlength="50" autocomplete="username" autofocus>
      </div>
      <div class="field" style="margin-bottom:1.6rem">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-terra btn-wide">Sign In to Dashboard</button>
    </form>

    <p class="auth-alt">
      <a href="<?= url('/') ?>">← Back to website</a>
    </p>
  </div>
</section>

</body>
</html>
