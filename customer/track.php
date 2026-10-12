<?php
/**
 * Customer — track an order (live status)
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$user = current_customer();
$userId = (int) $user['id'];

$orderId = int_in($_GET['order_id'] ?? 0, 1, PHP_INT_MAX);
$order = null;

// If no order given, use the most recent active order
if ($orderId < 1) {
    $stmt = $pdo->prepare(
        'SELECT id FROM orders
         WHERE user_id = ? AND status IN ("pending","confirmed","preparing","ready","out_for_delivery")
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$userId]);
    $orderId = (int) $stmt->fetchColumn();
}

if ($orderId > 0) {
    $stmt = $pdo->prepare(
        'SELECT o.* FROM orders o WHERE o.id = ? AND o.user_id = ? LIMIT 1'
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
}

// Order picker list (recent 6)
$stmt = $pdo->prepare(
    'SELECT id, order_number, status FROM orders
     WHERE user_id = ? ORDER BY id DESC LIMIT 6'
);
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

// Items of current order
$orderItems = [];
if ($order) {
    $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $it->execute([(int) $order['id']]);
    $orderItems = $it->fetchAll();
}

$page_title = 'Track Order';
$nav_active = 'track';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<?php if (!$order): ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
  <h3>No active orders</h3>
  <p>Place an order and track it here in real time.</p>
  <a href="<?= url('/customer/menu.php') ?>" class="btn btn-terra" style="margin-top:1.2rem">Order Now</a>
</div>
<?php else: ?>
<div class="order-hero">
  <div>
    <div class="oh-label">Order</div>
    <div class="oh-num"><?= e($order['order_number']) ?></div>
  </div>
  <div style="text-align:center">
    <div class="oh-label">Current status</div>
    <div style="margin-top:0.4rem"><span class="badge badge-<?= e($order['status']) ?>" id="trackStatus"><?= e(ORDER_STATUSES[$order['status']] ?? $order['status']) ?></span></div>
  </div>
  <div style="text-align:right">
    <div class="oh-label">Total</div>
    <div class="oh-total"><?= format_price($order['total']) ?></div>
  </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:2rem" class="dash-cols">
  <div class="panel">
    <div class="panel-head">
      <h3>Live Tracking</h3>
      <span class="text-muted" style="font-size:0.85rem">Updates automatically</span>
    </div>
    <div class="panel-body">
      <ol class="timeline" id="trackTimeline"></ol>

      <?php if ((string) $order['delivery_type'] === 'delivery' && $order['address']): ?>
      <div style="margin-top:1.8rem; padding:1.2rem; background:var(--cream-100); border-radius:14px">
        <strong style="font-size:0.85rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--muted)">Deliver to</strong>
        <p style="margin-top:0.3rem"><?= e($order['address']) ?>, <?= e($order['city']) ?> <?= e($order['postal_code']) ?></p>
        <p class="text-muted" style="font-size:0.88rem"><?= e($order['customer_name']) ?> · <?= e($order['phone']) ?></p>
      </div>
      <?php else: ?>
      <div style="margin-top:1.8rem; padding:1.2rem; background:var(--cream-100); border-radius:14px">
        <strong style="font-size:0.85rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--muted)">Pickup at</strong>
        <p style="margin-top:0.3rem"><?= e(SITE_ADDRESS) ?></p>
        <p class="text-muted" style="font-size:0.88rem">Show your order number at the counter: <strong><?= e($order['order_number']) ?></strong></p>
      </div>
      <?php endif; ?>

      <?php if (!empty($order['notes'])): ?>
      <p class="text-muted" style="margin-top:1.4rem"><strong>Notes:</strong> “<?= e($order['notes']) ?>”</p>
      <?php endif; ?>

      <?php if ((string) $order['status'] === 'cancelled'): ?>
      <div class="flash flash-warning" style="margin-top:1.4rem">This order was cancelled. If you believe this is a mistake, please call us.</div>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:grid; gap:1.8rem; align-content:start">
    <div class="panel">
      <div class="panel-head"><h3>Order Items</h3></div>
      <div class="panel-body flush">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Dish</th><th class="num">Qty</th><th class="num">Total</th></tr></thead>
            <tbody>
              <?php foreach ($orderItems as $oi): ?>
              <tr>
                <td class="strong"><?= e($oi['item_name']) ?></td>
                <td class="num"><?= (int) $oi['quantity'] ?></td>
                <td class="num strong"><?= format_price($oi['total_price']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="panel-body" style="border-top:1px solid var(--cream-100)">
        <div class="summary-row"><span>Subtotal</span><span><?= format_price($order['subtotal']) ?></span></div>
        <div class="summary-row"><span>Delivery</span><span><?= $order['delivery_fee'] > 0 ? format_price($order['delivery_fee']) : 'Free' ?></span></div>
        <div class="summary-row"><span>Tax</span><span><?= format_price($order['tax']) ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= format_price($order['total']) ?></span></div>
        <p class="hint text-muted" style="margin-top:0.8rem; font-size:0.8rem">
          Payment: <?= e(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']) ?>
          (<?= e($order['payment_status']) ?>)
        </p>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Recent Orders</h3></div>
      <div class="panel-body" style="display:grid; gap:0.5rem">
        <?php foreach ($recentOrders as $ro): ?>
        <a href="<?= url('/customer/track.php?order_id=' . (int) $ro['id']) ?>"
           class="dash-link" style="background:var(--cream-100); color:var(--ink); <?= (int) $ro['id'] === (int) $order['id'] ? 'border-left:3px solid var(--gold)' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
          <span style="flex:1"><?= e($ro['order_number']) ?></span>
          <span class="badge badge-<?= e($ro['status']) ?>" style="font-size:0.66rem"><?= e(ORDER_STATUSES[$ro['status']] ?? $ro['status']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$page_scripts = ['order-track.js'];
if ($order): ?>
<script>
  window.UMALF_TRACK = {
    order_id: <?= (int) $order['id'] ?>,
    initial_status: <?= json_encode((string) $order['status']) ?>
  };
</script>
<?php endif;
require_once __DIR__ . '/../includes/customer_footer.php';
