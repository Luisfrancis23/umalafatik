<?php
/**
 * Customer — registration
 */
require_once __DIR__ . '/../config/config.php';

require_guest();

$errors = [];
$form = [
    'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '',
    'password' => '', 'password_confirm' => '',
    'default_address' => '', 'city' => '', 'postal_code' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired. Please try again.');
        redirect('/customer/register.php');
    }

    $form['first_name'] = str_in($_POST['first_name'] ?? '', 60);
    $form['last_name']  = str_in($_POST['last_name'] ?? '', 60);
    $form['email']      = email_in($_POST['email'] ?? '');
    $form['phone']      = phone_in($_POST['phone'] ?? '');
    $form['password']   = (string) ($_POST['password'] ?? '');
    $form['password_confirm'] = (string) ($_POST['password_confirm'] ?? '');
    $form['default_address']  = str_in($_POST['default_address'] ?? '', 255);
    $form['city']             = str_in($_POST['city'] ?? '', 80);
    $form['postal_code']      = str_in($_POST['postal_code'] ?? '', 20);

    if (strlen($form['first_name']) < 2) $errors[] = 'Please enter your first name.';
    if (strlen($form['last_name']) < 2) $errors[] = 'Please enter your last name.';
    if (!is_valid_email($form['email'])) $errors[] = 'Please enter a valid email address.';
    if (strlen($form['phone']) < 7) $errors[] = 'Please enter a valid phone number.';
    if (strlen($form['password']) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($form['password'] !== $form['password_confirm']) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $pdo = getDB();
        // Email uniqueness
        $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $chk->execute([$form['email']]);
        if ((int) $chk->fetchColumn() > 0) {
            $errors[] = 'An account with this email already exists. <a href="' . url('/customer/login.php') . '">Sign in instead</a>.';
        } else {
            $hash = password_hash($form['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, phone, password, default_address, city, postal_code, email_verified_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $form['first_name'], $form['last_name'], $form['email'], $form['phone'],
                $hash, $form['default_address'], $form['city'], $form['postal_code'],
            ]);

            // Auto sign-in
            try {
                customer_login($form['email'], $form['password']);
            } catch (Throwable $e) {
                // Non-fatal — user can sign in manually
            }

            set_flash('success', 'Welcome to Uma Lafatik, ' . e($form['first_name']) . '! Your account is ready.');
            redirect('/customer/');
        }
    }
}

$page_title = 'Create Account — ' . SITE_NAME;
$page_desc = 'Create your Uma Lafatik account to order online, track orders and manage reservations.';
$canonical = BASE_URL . '/customer/register.php';
$extra_css = [url('/assets/css/dashboard.css')];

require_once __DIR__ . '/../includes/head.php';
?>

<section class="auth-wrap">
  <div class="auth-card" style="width:min(520px, 94%)">
    <a href="<?= url('/') ?>" class="brand">
      <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
        <rect x="2" y="2" width="44" height="44" rx="12" fill="#241e1a"/>
        <path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="#d4a24e" transform="translate(3.5 0) scale(0.86)"/>
        <path d="M30 12v24" stroke="#d4a24e" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <span class="brand-name">Uma <em>Lafatik</em></span>
    </a>
    <p class="auth-sub">Join our family — order, track and save your favourites.</p>

    <?php if ($errors): ?>
    <div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>
    <?= flash() ?>

    <form method="post" action="<?= url('/customer/register.php') ?>">
      <?= csrf_input() ?>
      <div class="form-grid">
        <div class="field">
          <label for="first_name">First name</label>
          <input id="first_name" name="first_name" type="text" value="<?= e($form['first_name']) ?>" required maxlength="60" autocomplete="given-name">
        </div>
        <div class="field">
          <label for="last_name">Last name</label>
          <input id="last_name" name="last_name" type="text" value="<?= e($form['last_name']) ?>" required maxlength="60" autocomplete="family-name">
        </div>
        <div class="field full">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= e($form['email']) ?>" required maxlength="120" autocomplete="email">
        </div>
        <div class="field full">
          <label for="phone">Phone</label>
          <input id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" required maxlength="25" autocomplete="tel">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
          <span class="hint">At least 8 characters.</span>
        </div>
        <div class="field">
          <label for="password_confirm">Confirm password</label>
          <input id="password_confirm" name="password_confirm" type="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="field full">
          <label for="default_address">Default address <span class="text-muted">(for delivery)</span></label>
          <input id="default_address" name="default_address" type="text" value="<?= e($form['default_address']) ?>" maxlength="255" autocomplete="street-address">
        </div>
        <div class="field">
          <label for="city">City</label>
          <input id="city" name="city" type="text" value="<?= e($form['city']) ?>" maxlength="80" autocomplete="address-level2">
        </div>
        <div class="field">
          <label for="postal_code">Postal code</label>
          <input id="postal_code" name="postal_code" type="text" value="<?= e($form['postal_code']) ?>" maxlength="20" autocomplete="postal-code">
        </div>
      </div>
      <div class="form-actions" style="margin-top:1.4rem">
        <button type="submit" class="btn btn-terra btn-wide">Create Account</button>
      </div>
      <p class="hint text-muted" style="margin-top:0.8rem; font-size:0.8rem">
        By creating an account you agree to our terms and privacy policy.
      </p>
    </form>

    <p class="auth-alt">Already have an account? <a href="<?= url('/customer/login.php') ?>">Sign in</a></p>
  </div>
</section>

</body>
</html>
