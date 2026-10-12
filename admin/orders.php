<?php
/**
 * Admin — orders list with search, filter, pagination, status update
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

// Status update via inline form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/orders.php');
    }
    $oid = int_in($_POST['order_id'] ?? 0, 1, PHP_INT_MAX);
    $newStatus = str_in($_POST['status'] ?? '', 30);

    if (array_key_exists($newStatus, ORDER_STATUSES)) {
        $stmt = $pdo->prepare('SELECT user_id, order_number, status FROM orders WHERE id = ?');
        $stmt->execute([$oid]);
        $order = $stmt->fetch();
        if ($order && (string) $order['status'] !== $newStatus) {
            $upd = $pdo->prepare('UPDATE orders SET status = ?, status_updated_at = NOW(), updated_at = NOW() WHERE id = ?');
            $upd->execute([$newStatus, $oid]);

            // Notify the customer
            if ((int) $order['user_id'] > 0) {
                push_notification(
                    $pdo,
                    (int) $order['user_id'],
                    'order_status',
                    'Order ' . $order['order_number'] . ' status: ' . ORDER_STATUSES[$newStatus] . '.',
                    $oid
                );
            }
            set_flash('success', 'Order #' . $oid . ' updated to "' . ORDER_STATUSES[$newStatus] . '". Customer notified.');
        }
    }
    redirect('/admin/orders.php');
}

// Filters
$statusFilter = str_in($_GET['status'] ?? '', 20);
$q = str_in($_GET['q'] ?? '', 60);
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

$where = ['1 = 1'];
$params = [];
if (in_array($statusFilter, array_keys(ORDER_STATUSES), true)) {
    $where[] = 'o.status = :st';
    $params[':st'] = $statusFilter;
}
if ($q !== '') {
    $where[] = '(o.order_number LIKE :q OR o.customer_name LIKE :q OR o.email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM orders o ' . $whereSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 15;
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT o.id, o.order_number, o.customer_name, o.email, o.phone, o.status, o.total,
            o.delivery_type, o.payment_method, o.payment_status, o.created_at,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o ' . $whereSql . '
     ORDER BY o.id DESC
     LIMIT :lim OFFSET :off'
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$page_title = 'Orders';
$nav_active = 'orders';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<form class="toolbar" method="get" action="<?= url('/admin/orders.php') ?>" role="search" aria-label="Search orders">
  <div class="search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search order #, customer, email…" aria-label="Search orders">
  </div>
  <div class="filter-pills" role="group" aria-label="Filter by status">
    <a class="pill <?= $statusFilter === '' ? 'active' : '' ?>" href="<?= e(url('/admin/orders.php?q=' . urlencode($q))) ?>">All</a>
    <?php foreach (ORDER_STATUSES as $key => $label): ?>
    <a class="pill <?= $statusFilter === $key ? 'active' : '' ?>"
       href="<?= e(url('/admin/orders.php?status=' . urlencode($key) . '&q=' . urlencode($q))) ?>">
      <?= e($label) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <input type="hidden" name="q" value="<?= e($q) ?>">
</form>

<?php if ($orders): ?>
<div class="panel">
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order</th><th>Customer</th><th>Placed</th><th>Items</th>
            <th class="num">Total</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td class="strong tracking-id"><?= e($o['order_number']) ?></td>
            <td>
              <strong><?= e($o['customer_name']) ?></strong>
              <small style="display:block" class="text-muted"><?= e($o['email']) ?></small>
            </td>
            <td><?= e(format_date($o['created_at'], 'M j, g:i A')) ?></td>
            <td><?= (int) $o['item_count'] ?></td>
            <td class="num strong"><?= format_price($o['total']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td>
              <div class="row-actions">
                <a href="<?= url('/admin/order-detail.php?id=' . (int) $o['id']) ?>" class="btn btn-outline dark btn-sm">Details</a>
                <form method="post" action="<?= url('/admin/orders.php') ?>" style="display:inline"
                      onsubmit="return confirm('Update order status? The customer will be notified.')">
                  <?= csrf_input() ?>
                  <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                  <select name="status" aria-label="New status for order <?= e($o['order_number']) ?>"
                          onchange="this.form.submit()" style="padding:0.4rem 0.6rem; border-radius:10px; border:1.5px solid var(--cream-200); background:var(--white); font-size:0.85rem">
                    <option value="">Set status…</option>
                    <?php foreach (ORDER_STATUSES as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $key === (string) $o['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="update_status" value="1">
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
echo render_pagination($page, $pages, '/admin/orders.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null])));
?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
  <h3>No orders found</h3>
  <p><?= $q || $statusFilter ? 'Try different filters.' : 'Orders will appear here as they come in.' ?></p>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
