<?php
/**
 * Customer — order confirmation
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$user = current_customer();
$orderId = int_in($_GET['order_id'] ?? 0, 1, PHP_INT_MAX);

$stmt = $pdo->prepare(
    'SELECT o.*, p.transaction_id, p.method AS payment_method_used, p.status AS payment_status_used
     FROM orders o
     LEFT JOIN payments p ON p.order_id = o.id
     WHERE o.id = ? AND o.user_id = ?
     LIMIT 1'
);
$stmt->execute([$orderId, (int) $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('/customer/orders.php');
}

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$orderId]);
$orderItems = $items->fetchAll();

$page_title = 'Order Confirmed';
$nav_active = 'orders';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<div class="center" style="max-width:640px; margin-inline:auto">
  <div style="width:84px; height:84px; margin:0 auto 1.4rem; border-radius:50%; background:rgba(122,139,90,0.16); display:grid; place-items:center; color:#4a5a32">
    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
  </div>
  <p class="eyebrow">Thank you, <?= e($user['first_name']) ?></p>
  <h2 style="margin-bottom:0.6rem">Your order is confirmed</h2>
  <p class="text-muted" style="margin-bottom:2rem">
    We've sent the details to <strong><?= e($order['email']) ?></strong>.
    <?php if ((string) $order['delivery_type'] === 'pickup'): ?>
    Your pickup will be ready in about 30 minutes.
    <?php else: ?>
    Your delivery is on its way to being prepared.
    <?php endif; ?>
  </p>

  <div class="order-hero" style="text-align:left">
    <div>
      <div class="oh-label">Order number</div>
      <div class="oh-num"><?= e($order['order_number']) ?></div>
      <div style="margin-top:0.5rem"><?= status_badge($order['status']) ?></div>
    </div>
    <div style="text-align:right">
      <div class="oh-label">Total</div>
      <div class="oh-total"><?= format_price($order['total']) ?></div>
      <div class="text-muted" style="font-size:0.85rem; margin-top:0.3rem">
        <?= e(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']) ?>
        · <?= e($order['delivery_type']) ?>
      </div>
    </div>
  </div>

  <div class="panel" style="text-align:left">
    <div class="panel-head"><h3>Order Details</h3></div>
    <div class="panel-body flush">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Dish</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Total</th></tr></thead>
          <tbody>
            <?php foreach ($orderItems as $oi): ?>
            <tr>
              <td class="strong"><?= e($oi['item_name']) ?></td>
              <td class="num"><?= (int) $oi['quantity'] ?></td>
              <td class="num"><?= format_price($oi['unit_price']) ?></td>
              <td class="num strong"><?= format_price($oi['total_price']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="strong text-muted" style="text-align:right; padding:1rem 1.1rem">
                Subtotal <?= format_price($order['subtotal']) ?> · Delivery <?= $order['delivery_fee'] > 0 ? format_price($order['delivery_fee']) : 'Free' ?> · Tax <?= format_price($order['tax']) ?>
              </td>
              <td class="num strong" style="padding:1rem 1.1rem; font-size:1.15rem"><?= format_price($order['total']) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <div class="panel-body" style="border-top:1px solid var(--cream-100)">
      <dl class="detail-list">
        <div class="detail-row"><dt>Customer</dt><dd><?= e($order['customer_name']) ?></dd></div>
        <div class="detail-row"><dt>Contact</dt><dd><?= e($order['email']) ?> · <?= e($order['phone']) ?></dd></div>
        <?php if ((string) $order['delivery_type'] === 'delivery' && $order['address']): ?>
        <div class="detail-row"><dt>Deliver to</dt><dd><?= e($order['address']) ?>, <?= e($order['city']) ?> <?= e($order['postal_code']) ?></dd></div>
        <?php else: ?>
        <div class="detail-row"><dt>Pickup at</dt><dd><?= e(SITE_ADDRESS) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($order['notes'])): ?>
        <div class="detail-row"><dt>Notes</dt><dd>“<?= e($order['notes']) ?>”</dd></div>
        <?php endif; ?>
        <div class="detail-row"><dt>Payment</dt><dd><?= e(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']) ?> (<?= e($order['payment_status']) ?>)</dd></div>
      </dl>
    </div>
  </div>

  <div class="section-actions" style="justify-content:center; margin-top:2rem">
    <a href="<?= url('/customer/track.php?order_id=' . $orderId) ?>" class="btn btn-terra">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
      Track Order
    </a>
    <a href="<?= url('/customer/orders.php') ?>" class="btn btn-outline dark">View Order History</a>
    <a href="<?= url('/customer/menu.php') ?>" class="btn btn-gold">Order More</a>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/customer_footer.php';
