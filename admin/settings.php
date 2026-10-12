<?php
/**
 * Admin — site settings
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/settings.php');
    }

    $deliveryFee = money_in($_POST['delivery_fee'] ?? 0);
    $freeMin = money_in($_POST['free_delivery_min'] ?? 0);
    $taxRate = money_in($_POST['tax_rate'] ?? 0) / 100;
    $maxGuests = int_in($_POST['max_guests'] ?? 20, 1, 100);
    $openingHours = str_in($_POST['opening_hours'] ?? '', 500);
    $facebook = str_in($_POST['facebook_url'] ?? '', 255);
    $instagram = str_in($_POST['instagram_url'] ?? '', 255);
    $twitter = str_in($_POST['twitter_url'] ?? '', 255);
    $tiktok = str_in($_POST['tiktok_url'] ?? '', 255);

    if ($taxRate < 0 || $taxRate > 1) {
        $errors[] = 'Tax rate must be between 0 and 100.';
    }

    if (!$errors) {
        $settings = [
            'delivery_fee'      => number_format($deliveryFee, 2),
            'free_delivery_min' => number_format($freeMin, 2),
            'tax_rate'          => number_format($taxRate, 4),
            'max_guests'        => (string) $maxGuests,
            'opening_hours'     => $openingHours,
            'facebook_url'      => $facebook,
            'instagram_url'     => $instagram,
            'twitter_url'       => $twitter,
            'tiktok_url'        => $tiktok,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = :v'
        );
        foreach ($settings as $key => $value) {
            $stmt->execute([':k' => $key, ':v' => $value]);
        }

        set_flash('success', 'Settings saved.');
        redirect('/admin/settings.php');
    }
}

// Contact messages count
$contactCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

$page_title = 'Settings';
$nav_active = 'settings';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div style="max-width:820px">
  <?php if ($errors): ?>
  <div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
  <?php endif; ?>

  <div class="panel">
    <div class="panel-head"><h3>Ordering & Delivery</h3></div>
    <div class="panel-body">
      <form method="post" action="<?= url('/admin/settings.php') ?>">
        <?= csrf_input() ?>
        <div class="form-grid">
          <div class="field">
            <label for="delivery_fee">Delivery fee (<?= e(CURRENCY_SYMBOL) ?>)</label>
            <input id="delivery_fee" name="delivery_fee" type="number" step="0.01" min="0" value="<?= e(number_format((float) setting('delivery_fee', DELIVERY_FEE), 2)) ?>">
          </div>
          <div class="field">
            <label for="free_delivery_min">Free delivery over (<?= e(CURRENCY_SYMBOL) ?>)</label>
            <input id="free_delivery_min" name="free_delivery_min" type="number" step="0.01" min="0" value="<?= e(number_format((float) setting('free_delivery_min', FREE_DELIVERY_MIN), 2)) ?>">
          </div>
          <div class="field">
            <label for="tax_rate">Tax rate (%)</label>
            <input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" value="<?= e(number_format((float) setting('tax_rate', TAX_RATE) * 100, 2)) ?>">
          </div>
          <div class="field">
            <label for="max_guests">Max guests per reservation</label>
            <input id="max_guests" name="max_guests" type="number" min="1" max="100" value="<?= e((string) setting('max_guests', 20)) ?>">
          </div>
          <div class="field full">
            <label for="opening_hours">Opening hours</label>
            <textarea id="opening_hours" name="opening_hours" maxlength="500" style="min-height:90px"><?= e((string) setting('opening_hours', '')) ?></textarea>
            <span class="hint">Shown on the contact page.</span>
          </div>
        </div>
        <div class="form-actions" style="margin-top:1.2rem">
          <button type="submit" class="btn btn-terra">Save Settings</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Social Media</h3></div>
    <div class="panel-body">
      <form method="post" action="<?= url('/admin/settings.php') ?>">
        <?= csrf_input() ?>
        <div class="form-grid">
          <div class="field">
            <label for="facebook_url">Facebook URL</label>
            <input id="facebook_url" name="facebook_url" type="url" value="<?= e((string) setting('facebook_url', '')) ?>" placeholder="https://facebook.com/…">
          </div>
          <div class="field">
            <label for="instagram_url">Instagram URL</label>
            <input id="instagram_url" name="instagram_url" type="url" value="<?= e((string) setting('instagram_url', '')) ?>" placeholder="https://instagram.com/…">
          </div>
          <div class="field">
            <label for="twitter_url">X (Twitter) URL</label>
            <input id="twitter_url" name="twitter_url" type="url" value="<?= e((string) setting('twitter_url', '')) ?>" placeholder="https://x.com/…">
          </div>
          <div class="field">
            <label for="tiktok_url">TikTok URL</label>
            <input id="tiktok_url" name="tiktok_url" type="url" value="<?= e((string) setting('tiktok_url', '')) ?>" placeholder="https://tiktok.com/@…">
          </div>
        </div>
        <div class="form-actions" style="margin-top:1.2rem">
          <button type="submit" class="btn btn-terra">Save Settings</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Contact Messages</h3></div>
    <div class="panel-body">
      <p class="text-muted" style="margin-bottom:1.2rem">
        <?= $contactCount > 0 ? '<strong>' . $contactCount . '</strong> unread message(s) from the website contact form.' : 'No unread messages.' ?>
      </p>
      <?php
      $msgs = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 8')->fetchAll();
      if ($msgs): ?>
      <div class="panel-body flush" style="padding:0">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>From</th><th>Topic</th><th>Message</th><th>Received</th></tr></thead>
            <tbody>
              <?php foreach ($msgs as $m): ?>
              <tr>
                <td class="strong"><?= e($m['name']) ?>
                  <small style="display:block" class="text-muted"><?= e($m['email']) ?><?= $m['phone'] ? ' · ' . e($m['phone']) : '' ?></small>
                </td>
                <td><span class="chip terra"><?= e($m['subject']) ?></span></td>
                <td class="text-muted"><?= e(mb_strimwidth((string) $m['message'], 0, 90, '…')) ?></td>
                <td><?= e(format_date($m['created_at'], 'M j, g:i A')) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php else: ?>
      <p class="text-muted">No messages yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>System</h3></div>
    <div class="panel-body">
      <dl class="detail-list">
        <div class="detail-row"><dt>PHP</dt><dd><?= e(PHP_VERSION) ?></dd></div>
        <div class="detail-row"><dt>Database</dt><dd>MariaDB / MySQL via PDO (utf8mb4)</dd></div>
        <div class="detail-row"><dt>Default admin</dt><dd>username: <code>admin</code> · password: <code>Admin@123</code> <span class="text-muted">(change it!)</span></dd></div>
        <div class="detail-row"><dt>Demo customer</dt><dd>email: <code>customer@demo.com</code> · password: <code>Demo@123</code></dd></div>
      </dl>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
