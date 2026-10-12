<?php
/**
 * Admin — order detail: view items, payment, change status, cancel
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

$orderId = int_in($_GET['id'] ?? 0, 1, PHP_INT_MAX);

$stmt = $pdo->prepare(
    'SELECT o.*, u.first_name AS user_first, u.last_name AS user_last,
            p.transaction_id, p.method AS pay_method, p.status AS pay_status, p.paid_at
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     LEFT JOIN payments p ON p.order_id = o.id
     WHERE o.id = ?
     LIMIT 1'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('/admin/orders.php');
}

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$orderId]);
$orderItems = $items->fetchAll();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/order-detail.php?id=' . $orderId);
    }

    if (isset($_POST['update_status'])) {
        $newStatus = str_in($_POST['status'] ?? '', 30);
        if (array_key_exists($newStatus, ORDER_STATUSES)) {
            $upd = $pdo->prepare('UPDATE orders SET status = ?, status_updated_at = NOW(), updated_at = NOW() WHERE id = ?');
            $upd->execute([$newStatus, $orderId]);

            if ((int) $order['user_id'] > 0) {
                push_notification(
                    $pdo,
                    (int) $order['user_id'],
                    'order_status',
                    'Order ' . $order['order_number'] . ' status: ' . ORDER_STATUSES[$newStatus] . '.',
                    $orderId
                );
            }
            set_flash('success', 'Order status updated to "' . ORDER_STATUSES[$newStatus] . '". Customer notified.');
            redirect('/admin/order-detail.php?id=' . $orderId);
        }
    }

    if (isset($_POST['update_payment'])) {
        $newPay = str_in($_POST['payment_status'] ?? '', 20);
        if (in_array($newPay, ['pending', 'paid', 'failed', 'refunded'], true)) {
            $upd = $pdo->prepare('UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$newPay, $orderId]);
            if ((int) $order['pay_method'] !== 0) {
                $payUpd = $pdo->prepare('UPDATE payments SET status = ?, paid_at = ? WHERE order_id = ?');
                $payUpd->execute([$newPay, $newPay === 'paid' ? date('Y-m-d H:i:s') : null, $orderId]);
            }
            set_flash('success', 'Payment status updated.');
            redirect('/admin/order-detail.php?id=' . $orderId);
        }
    }
}

$page_title = 'Order ' . $order['order_number'];
$nav_active = 'orders';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="order-hero">
  <div>
    <div class="oh-label">Order</div>
    <div class="oh-num"><?= e($order['order_number']) ?></div>
    <div style="margin-top:0.4rem" class="text-muted">Placed <?= e(format_date($order['created_at'], 'M j, Y g:i A')) ?></div>
  </div>
  <div style="text-align:center">
    <div class="oh-label">Status</div>
    <div style="margin-top:0.4rem"><?= status_badge($order['status']) ?></div>
    <div class="text-muted" style="font-size:0.8rem; margin-top:0.3rem">
      <?= e(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']) ?>
      · <?= e($order['delivery_type']) ?>
    </div>
  </div>
  <div style="text-align:right">
    <div class="oh-label">Total</div>
    <div class="oh-total"><?= format_price($order['total']) ?></div>
    <div class="text-muted" style="font-size:0.8rem; margin-top:0.3rem">
      Payment: <?= e($order['payment_status']) ?>
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns:1.5fr 1fr; gap:1.8rem" class="dash-cols">
  <div style="display:grid; gap:1.8rem; align-content:start">
    <div class="panel">
      <div class="panel-head"><h3>Items</h3></div>
      <div class="panel-body flush">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Dish</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Total</th></tr></thead>
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
                <td colspan="3" class="text-muted" style="text-align:right; padding:1rem 1.1rem">
                  Subtotal <?= format_price($order['subtotal']) ?>
                  &nbsp;·&nbsp; Delivery <?= $order['delivery_fee'] > 0 ? format_price($order['delivery_fee']) : 'Free' ?>
                  &nbsp;·&nbsp; Tax <?= format_price($order['tax']) ?>
                  &nbsp;·&nbsp; Discount <?= format_price($order['discount']) ?>
                </td>
                <td class="num strong" style="padding:1rem 1.1rem; font-size:1.15rem"><?= format_price($order['total']) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Customer & Delivery</h3></div>
      <div class="panel-body">
        <dl class="detail-list">
          <div class="detail-row"><dt>Customer</dt><dd><?= e($order['customer_name']) ?><?= $order['user_first'] ? ' <span class="text-muted">(account)</span>' : '' ?></dd></div>
          <div class="detail-row"><dt>Email</dt><dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd></div>
          <div class="detail-row"><dt>Phone</dt><dd><a href="tel:<?= e($order['phone']) ?>"><?= e($order['phone']) ?></a></dd></div>
          <?php if ((string) $order['delivery_type'] === 'delivery'): ?>
          <div class="detail-row"><dt>Address</dt><dd><?= e($order['address']) ?><br><?= e($order['city']) ?> <?= e($order['postal_code']) ?></dd></div>
          <?php else: ?>
          <div class="detail-row"><dt>Pickup</dt><dd><?= e(SITE_ADDRESS) ?></dd></div>
          <?php endif; ?>
          <?php if (!empty($order['notes'])): ?>
          <div class="detail-row"><dt>Notes</dt><dd>“<?= e($order['notes']) ?>”</dd></div>
          <?php endif; ?>
        </dl>
      </div>
    </div>
  </div>

  <div style="display:grid; gap:1.8rem; align-content:start">
    <div class="panel">
      <div class="panel-head"><h3>Update Status</h3></div>
      <div class="panel-body">
        <form method="post" action="<?= url('/admin/order-detail.php?id=' . $orderId) ?>">
          <?= csrf_input() ?>
          <input type="hidden" name="update_status" value="1">
          <div class="field">
            <label for="status">Order status</label>
            <select id="status" name="status">
              <?php foreach (ORDER_STATUSES as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= $key === (string) $order['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="hint">The customer receives an in-app notification.</span>
          </div>
          <div class="form-actions" style="margin-top:1rem">
            <button type="submit" class="btn btn-terra">Update Status</button>
          </div>
        </form>
        <div class="timeline" style="margin-top:1.6rem">
          <?php
          $currentIdx = array_search((string) $order['status'], ORDER_STEPS, true);
          foreach (ORDER_STEPS as $i => $step):
            $state = $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'current' : '');
          ?>
          <li class="tl-step <?= $state ?>">
            <span class="tl-dot"><?= $i < $currentIdx ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>' : '' ?></span>
            <h4><?= e(ORDER_STATUSES[$step]) ?></h4>
          </li>
          <?php endforeach; ?>
        </div>
        <?php if ((string) $order['status'] === 'cancelled'): ?>
        <p class="text-muted" style="font-size:0.85rem">Cancelled <?= e(format_date((string) $order['updated_at'], 'M j, Y g:i A')) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Payment</h3></div>
      <div class="panel-body">
        <dl class="detail-list">
          <div class="detail-row"><dt>Method</dt><dd><?= e(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']) ?></dd></div>
          <div class="detail-row"><dt>Status</dt><dd><?= status_badge($order['payment_status'] === 'paid' ? 'completed' : ($order['payment_status'] === 'pending' ? 'pending' : 'cancelled')) ?></dd></div>
          <div class="detail-row"><dt>Amount</dt><dd><?= format_price($order['total']) ?></dd></div>
          <?php if (!empty($order['transaction_id'])): ?>
          <div class="detail-row"><dt>Transaction</dt><dd class="tracking-id" style="font-size:0.95rem"><?= e($order['transaction_id']) ?></dd></div>
          <?php endif; ?>
          <?php if (!empty($order['paid_at'])): ?>
          <div class="detail-row"><dt>Paid at</dt><dd><?= e(format_date($order['paid_at'], 'M j, Y g:i A')) ?></dd></div>
          <?php endif; ?>
        </dl>
        <form method="post" action="<?= url('/admin/order-detail.php?id=' . $orderId) ?>" style="margin-top:1.2rem">
          <?= csrf_input() ?>
          <input type="hidden" name="update_payment" value="1">
          <div class="field">
            <label for="payment_status">Payment status</label>
            <select id="payment_status" name="payment_status">
              <?php foreach (['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $k => $v): ?>
              <option value="<?= e($k) ?>" <?= $k === (string) $order['payment_status'] ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-actions" style="margin-top:0.8rem">
            <button type="submit" class="btn btn-outline dark btn-sm">Update Payment</button>
          </div>
        </form>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Customer Account</h3></div>
      <div class="panel-body">
        <?php if ((int) $order['user_id'] > 0): ?>
        <p class="text-muted" style="margin-bottom:1rem">
          Account customer: <strong><?= e($order['customer_name']) ?></strong>
        </p>
        <a href="<?= url('/admin/customers.php?q=' . urlencode($order['email'])) ?>" class="btn btn-outline dark btn-sm">View Customer</a>
        <?php else: ?>
        <p class="text-muted">Guest order (no account).</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
