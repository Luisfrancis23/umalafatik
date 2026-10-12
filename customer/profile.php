<?php
/**
 * Customer — profile management
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$user = current_customer();
$userId = (int) $user['id'];

$errors = [];
$tab = in_array($_GET['tab'] ?? '', ['details', 'security'], true) ? $_GET['tab'] : 'details';

// Update details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/customer/profile.php');
    }

    $firstName = str_in($_POST['first_name'] ?? '', 60);
    $lastName  = str_in($_POST['last_name'] ?? '', 60);
    $email     = email_in($_POST['email'] ?? '');
    $phone     = phone_in($_POST['phone'] ?? '');
    $address   = str_in($_POST['default_address'] ?? '', 255);
    $city      = str_in($_POST['city'] ?? '', 80);
    $postal    = str_in($_POST['postal_code'] ?? '', 20);

    if (strlen($firstName) < 2) $errors[] = 'Please enter your first name.';
    if (strlen($lastName) < 2) $errors[] = 'Please enter your last name.';
    if (!is_valid_email($email)) $errors[] = 'Please enter a valid email address.';
    if (strlen($phone) < 7) $errors[] = 'Please enter a valid phone number.';

    if (!$errors) {
        // Email uniqueness (excluding self)
        $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
        $chk->execute([$email, $userId]);
        if ((int) $chk->fetchColumn() > 0) {
            $errors[] = 'This email is already used by another account.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?,
                 default_address = ?, city = ?, postal_code = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$firstName, $lastName, $email, $phone, $address, $city, $postal, $userId]);
            set_flash('success', 'Profile updated successfully.');
            redirect('/customer/profile.php');
        }
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/customer/profile.php?tab=security');
    }

    $current = (string) ($_POST['current_password'] ?? '');
    $new     = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['new_password_confirm'] ?? '');

    if (!password_verify($current, (string) $user['password'])) {
        $errors[] = 'Your current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }

    if (!$errors) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$hash, $userId]);
        set_flash('success', 'Password changed successfully.');
        redirect('/customer/profile.php?tab=security');
    }
}

// Order stats
$stmt = $pdo->prepare('SELECT COUNT(*), COALESCE(SUM(total), 0) FROM orders WHERE user_id = ? AND status != "cancelled"');
$stmt->execute([$userId]);
[$orderCount, $totalSpent] = $stmt->fetch(PDO::FETCH_NUM);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ?');
$stmt->execute([$userId]);
$resCount = (int) $stmt->fetchColumn();

$page_title = 'My Profile';
$nav_active = 'profile';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<div class="profile-head">
  <span class="profile-avatar" aria-hidden="true"><?= e(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1)) ?></span>
  <div style="flex:1">
    <h2><?= e($user['first_name']) ?> <?= e($user['last_name']) ?></h2>
    <p>Member since <?= e(format_date($user['created_at'], 'F Y')) ?> · <?= (int) $orderCount ?> orders · <?= format_price((float) $totalSpent) ?> total · <?= (int) $resCount ?> reservations</p>
  </div>
  <div class="filter-pills">
    <a class="pill <?= $tab === 'details' ? 'active' : '' ?>" href="<?= url('/customer/profile.php') ?>">Details</a>
    <a class="pill <?= $tab === 'security' ? 'active' : '' ?>" href="<?= url('/customer/profile.php?tab=security') ?>">Security</a>
  </div>
</div>

<?php if ($errors): ?>
<div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<?php if ($tab === 'details'): ?>
<div class="panel" style="max-width:820px">
  <div class="panel-head"><h3>Personal Details</h3></div>
  <div class="panel-body">
    <form method="post" action="<?= url('/customer/profile.php') ?>">
      <?= csrf_input() ?>
      <input type="hidden" name="update_details" value="1">
      <div class="form-grid">
        <div class="field">
          <label for="first_name">First name</label>
          <input id="first_name" name="first_name" type="text" value="<?= e($user['first_name']) ?>" required maxlength="60">
        </div>
        <div class="field">
          <label for="last_name">Last name</label>
          <input id="last_name" name="last_name" type="text" value="<?= e($user['last_name']) ?>" required maxlength="60">
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= e($user['email']) ?>" required maxlength="120">
        </div>
        <div class="field">
          <label for="phone">Phone</label>
          <input id="phone" name="phone" type="tel" value="<?= e($user['phone']) ?>" required maxlength="25">
        </div>
        <div class="field full">
          <label for="default_address">Default delivery address</label>
          <input id="default_address" name="default_address" type="text" value="<?= e($user['default_address'] ?? '') ?>" maxlength="255">
        </div>
        <div class="field">
          <label for="city">City</label>
          <input id="city" name="city" type="text" value="<?= e($user['city'] ?? '') ?>" maxlength="80">
        </div>
        <div class="field">
          <label for="postal_code">Postal code</label>
          <input id="postal_code" name="postal_code" type="text" value="<?= e($user['postal_code'] ?? '') ?>" maxlength="20">
        </div>
      </div>
      <div class="form-actions" style="margin-top:1.2rem">
        <button type="submit" class="btn btn-terra">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="panel" style="max-width:560px">
  <div class="panel-head"><h3>Change Password</h3></div>
  <div class="panel-body">
    <form method="post" action="<?= url('/customer/profile.php?tab=security') ?>">
      <?= csrf_input() ?>
      <input type="hidden" name="update_password" value="1">
      <div class="field" style="margin-bottom:1.2rem">
        <label for="current_password">Current password</label>
        <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
      </div>
      <div class="field" style="margin-bottom:1.2rem">
        <label for="new_password">New password</label>
        <input id="new_password" name="new_password" type="password" required minlength="8" autocomplete="new-password">
        <span class="hint">At least 8 characters.</span>
      </div>
      <div class="field" style="margin-bottom:1.4rem">
        <label for="new_password_confirm">Confirm new password</label>
        <input id="new_password_confirm" name="new_password_confirm" type="password" required minlength="8" autocomplete="new-password">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-terra">Change Password</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="panel" style="max-width:820px; margin-top:1.8rem">
  <div class="panel-head"><h3>Account</h3></div>
  <div class="panel-body" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center">
    <p class="text-muted" style="margin:0; flex:1; min-width:240px">
      Signed in as <strong><?= e($user['email']) ?></strong>. Your data is used only to run your orders and reservations.
    </p>
    <a href="<?= url('/') ?>" class="btn btn-outline dark">Back to Website</a>
    <a href="<?= url('/customer/logout.php') ?>" class="btn btn-ghost dark" style="color:#b4443c">Sign Out</a>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/customer_footer.php';
