<?php
/**
 * Customer — checkout
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$user = current_customer();
$items = get_cart_items($pdo);

if (empty($items)) {
    set_flash('info', 'Your cart is empty. Add some dishes first.');
    redirect('/customer/menu.php');
}

// Remove unavailable items
$items = array_filter($items, fn ($it) => (int) $it['is_available'] === 1);
if (empty($items)) {
    clear_cart($pdo);
    set_flash('error', 'The items in your cart are no longer available.');
    redirect('/customer/menu.php');
}

$errors = [];
$form = [
    'delivery_type' => 'delivery',
    'name'    => trim($user['first_name'] . ' ' . $user['last_name']),
    'email'   => (string) $user['email'],
    'phone'   => (string) $user['phone'],
    'address' => (string) ($user['default_address'] ?? ''),
    'city'    => (string) ($user['city'] ?? ''),
    'postal_code' => (string) ($user['postal_code'] ?? ''),
    'notes'   => '',
    'payment_method' => 'cod',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired. Please try again.');
        redirect('/customer/checkout.php');
    }

    $form['delivery_type'] = in_array($_POST['delivery_type'] ?? '', ['delivery', 'pickup'], true) ? $_POST['delivery_type'] : 'delivery';
    $form['name']    = str_in($_POST['name'] ?? '', 120);
    $form['email']   = email_in($_POST['email'] ?? '');
    $form['phone']   = phone_in($_POST['phone'] ?? '');
    $form['address'] = str_in($_POST['address'] ?? '', 255);
    $form['city']    = str_in($_POST['city'] ?? '', 80);
    $form['postal_code'] = str_in($_POST['postal_code'] ?? '', 20);
    $form['notes']   = str_in($_POST['notes'] ?? '', 500);
    $form['payment_method'] = in_array($_POST['payment_method'] ?? '', ['cod', 'card', 'bank'], true) ? $_POST['payment_method'] : 'cod';

    if (strlen($form['name']) < 2) $errors[] = 'Please enter your full name.';
    if (!is_valid_email($form['email'])) $errors[] = 'Please enter a valid email address.';
    if (strlen($form['phone']) < 7) $errors[] = 'Please enter a valid phone number.';
    if ($form['delivery_type'] === 'delivery') {
        if (strlen($form['address']) < 6) $errors[] = 'Please enter your delivery address.';
        if (strlen($form['city']) < 2) $errors[] = 'Please enter your city.';
    }

    if (!$errors) {
        // Recompute totals from the DB at the moment of purchase
        $freshTotals = cart_totals($pdo, $form['delivery_type']);

        try {
            $pdo->beginTransaction();

            $orderNumber = generate_order_number($pdo);

            $stmt = $pdo->prepare(
                'INSERT INTO orders
                 (order_number, user_id, customer_name, email, phone, delivery_type, address, city, postal_code, notes,
                  subtotal, delivery_fee, tax, discount, total, payment_method, payment_status, status, status_updated_at, order_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, "pending", NOW(), NOW())'
            );
            $stmt->execute([
                $orderNumber,
                (int) $user['id'],
                $form['name'],
                $form['email'],
                $form['phone'],
                $form['delivery_type'],
                $form['delivery_type'] === 'delivery' ? $form['address'] : null,
                $form['delivery_type'] === 'delivery' ? $form['city'] : null,
                $form['delivery_type'] === 'delivery' ? $form['postal_code'] : null,
                $form['notes'] ?: null,
                $freshTotals['subtotal'],
                $freshTotals['delivery_fee'],
                $freshTotals['tax'],
                $freshTotals['total'],
                $form['payment_method'],
                $form['payment_method'] === 'cod' ? 'pending' : 'paid',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            // Order items (snapshot name + price)
            $insItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $it) {
                $insItem->execute([
                    $orderId,
                    (int) $it['item_id'],
                    $it['name'],
                    (int) $it['quantity'],
                    (float) $it['price'],
                    round((float) $it['price'] * (int) $it['quantity'], 2),
                ]);
            }

            // Payment record
            $txnId = $form['payment_method'] === 'cod' ? null : 'PAY-' . strtoupper(bin2hex(random_bytes(6)));
            $insPay = $pdo->prepare(
                'INSERT INTO payments (order_id, transaction_id, amount, method, status, paid_at)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insPay->execute([
                $orderId,
                $txnId,
                $freshTotals['total'],
                $form['payment_method'],
                $form['payment_method'] === 'cod' ? 'pending' : 'paid',
                $form['payment_method'] === 'cod' ? null : date('Y-m-d H:i:s'),
            ]);

            // Clear the cart
            clear_cart($pdo);

            // Notify the customer
            push_notification(
                $pdo,
                (int) $user['id'],
                'order_status',
                'Order ' . $orderNumber . ' received! The restaurant will confirm shortly.',
                $orderId
            );

            $pdo->commit();

            redirect('/customer/order-confirmation.php?order_id=' . $orderId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            set_flash('error', 'Could not place your order. Please try again.');
            redirect('/customer/cart.php');
        }
    }
}

$totals = cart_totals($pdo, $form['delivery_type']);

$page_title = 'Checkout';
$nav_active = 'cart';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<h2 style="margin-bottom:1.6rem">Checkout</h2>

<?php if ($errors): ?>
<div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<form method="post" action="<?= url('/customer/checkout.php') ?>" id="checkoutForm">
  <?= csrf_input() ?>

  <div class="cart-layout">
    <div style="display:grid; gap:1.8rem; align-content:start">

      <!-- Delivery / pickup -->
      <div class="panel">
        <div class="panel-head"><h3>Delivery or Pickup</h3></div>
        <div class="panel-body">
          <div class="option-tiles" role="radiogroup" aria-label="Delivery type">
            <label class="option-tile<?= $form['delivery_type'] === 'delivery' ? ' selected' : '' ?>">
              <input type="radio" name="delivery_type" value="delivery" <?= $form['delivery_type'] === 'delivery' ? 'checked' : '' ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 8h14v9H1zM15 11h4l3 3v3h-7z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/></svg>
              Delivery
              <small class="text-muted"><?= $totals['delivery_fee'] > 0 ? format_price($totals['delivery_fee']) : 'Free over ' . format_price(setting('free_delivery_min', FREE_DELIVERY_MIN)) ?></small>
            </label>
            <label class="option-tile<?= $form['delivery_type'] === 'pickup' ? ' selected' : '' ?>">
              <input type="radio" name="delivery_type" value="pickup" <?= $form['delivery_type'] === 'pickup' ? 'checked' : '' ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6v11a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/></svg>
              Pickup
              <small class="text-muted">Ready in ~30 min · free</small>
            </label>
          </div>
        </div>
      </div>

      <!-- Customer details -->
      <div class="panel">
        <div class="panel-head"><h3>Your Details</h3></div>
        <div class="panel-body">
          <div class="form-grid">
            <div class="field">
              <label for="name">Full name</label>
              <input id="name" name="name" type="text" value="<?= e($form['name']) ?>" required maxlength="120">
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" value="<?= e($form['email']) ?>" required maxlength="120">
            </div>
            <div class="field">
              <label for="phone">Phone</label>
              <input id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" required maxlength="25">
            </div>
            <div class="field" id="cityField">
              <label for="city">City</label>
              <input id="city" name="city" type="text" value="<?= e($form['city']) ?>" maxlength="80">
            </div>
            <div class="field full" id="addressField">
              <label for="address">Delivery address</label>
              <input id="address" name="address" type="text" value="<?= e($form['address']) ?>" maxlength="255" placeholder="Street, building, apartment…">
            </div>
            <div class="field" id="postalField">
              <label for="postal_code">Postal code</label>
              <input id="postal_code" name="postal_code" type="text" value="<?= e($form['postal_code']) ?>" maxlength="20">
            </div>
            <div class="field full">
              <label for="notes">Order notes <span class="text-muted">(optional)</span></label>
              <textarea id="notes" name="notes" maxlength="500" placeholder="Allergies, spice level, gate code, ring the bell…"><?= e($form['notes']) ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment -->
      <div class="panel">
        <div class="panel-head"><h3>Payment Method</h3></div>
        <div class="panel-body">
          <div class="option-tiles" role="radiogroup" aria-label="Payment method">
            <label class="option-tile<?= $form['payment_method'] === 'cod' ? ' selected' : '' ?>">
              <input type="radio" name="payment_method" value="cod" <?= $form['payment_method'] === 'cod' ? 'checked' : '' ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 12h.01M18 12h.01"/></svg>
              Cash on Delivery
              <small class="text-muted">Pay when it arrives</small>
            </label>
            <label class="option-tile<?= $form['payment_method'] === 'card' ? ' selected' : '' ?>">
              <input type="radio" name="payment_method" value="card" <?= $form['payment_method'] === 'card' ? 'checked' : '' ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>
              Card
              <small class="text-muted">Secure (demo)</small>
            </label>
            <label class="option-tile<?= $form['payment_method'] === 'bank' ? ' selected' : '' ?>">
              <input type="radio" name="payment_method" value="bank" <?= $form['payment_method'] === 'bank' ? 'checked' : '' ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3l9 7H3zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
              Bank Transfer
              <small class="text-muted">Transfer to our account</small>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <aside class="summary" aria-label="Order summary">
      <h3>Your Order</h3>
      <?php foreach ($items as $it): ?>
      <div class="summary-row">
        <span><?= (int) $it['quantity'] ?> × <?= e($it['name']) ?></span>
        <span><?= format_price((float) $it['price'] * (int) $it['quantity']) ?></span>
      </div>
      <?php endforeach; ?>
      <div class="summary-row" style="border-top:1px dashed var(--cream-200); margin-top:0.7rem; padding-top:0.9rem">
        <span>Subtotal</span><span><?= format_price($totals['subtotal']) ?></span>
      </div>
      <div class="summary-row"><span>Delivery fee</span><span><?= $totals['delivery_fee'] > 0 ? format_price($totals['delivery_fee']) : 'Free' ?></span></div>
      <div class="summary-row"><span>Tax</span><span><?= format_price($totals['tax']) ?></span></div>
      <div class="summary-row total"><span>Total</span><span><?= format_price($totals['total']) ?></span></div>
      <button type="submit" class="btn btn-terra btn-wide" style="margin-top:1.4rem">
        Place Order · <?= format_price($totals['total']) ?>
      </button>
      <p class="hint text-muted" style="margin-top:1rem; font-size:0.8rem; text-align:center">
        By placing this order you agree to our terms.
      </p>
    </aside>
  </div>
</form>

<?php
$page_scripts = ['cart.js'];
require_once __DIR__ . '/../includes/customer_footer.php';
