<?php
/**
 * Customer — order history
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$user = current_customer();
$userId = (int) $user['id'];

// Reorder action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/customer/orders.php');
    }
    $reorderId = int_in($_POST['reorder'] ?? 0, 1, PHP_INT_MAX);
    $stmt = $pdo->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? AND status != "cancelled"');
    $stmt->execute([$reorderId, $userId]);
    if ($stmt->fetch()) {
        $items = $pdo->prepare('SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?');
        $items->execute([$reorderId]);
        $added = 0;
        foreach ($items->fetchAll() as $oi) {
            if ((int) $oi['menu_item_id'] > 0) {
                try {
                    add_to_cart($pdo, (int) $oi['menu_item_id'], (int) $oi['quantity']);
                    $added++;
                } catch (RuntimeException $e) {
                    // Item unavailable — skip
                }
            }
        }
        if ($added > 0) {
            set_flash('success', $added . ' item(s) added back to your cart.');
        } else {
            set_flash('info', 'Those items are no longer available.');
        }
    }
    redirect('/customer/cart.php');
}

// Filters
$statusFilter = str_in($_GET['status'] ?? '', 20);
$q = str_in($_GET['q'] ?? '', 40);
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

$where = ['o.user_id = :uid'];
$params = [':uid' => $userId];
if (in_array($statusFilter, array_keys(ORDER_STATUSES), true)) {
    $where[] = 'o.status = :st';
    $params[':st'] = $statusFilter;
}
if ($q !== '') {
    $where[] = 'o.order_number LIKE :q';
    $params[':q'] = '%' . $q . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM orders o ' . $whereSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 10;
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT o.id, o.order_number, o.status, o.total, o.delivery_type, o.payment_method, o.payment_status, o.created_at,
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

$page_title = 'My Orders';
$nav_active = 'orders';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<form class="toolbar" method="get" action="<?= url('/customer/orders.php') ?>" role="search" aria-label="Search orders">
  <div class="search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by order number…" aria-label="Search by order number">
  </div>
  <div class="filter-pills" role="group" aria-label="Filter by status">
    <a class="pill <?= $statusFilter === '' ? 'active' : '' ?>" href="<?= e(url('/customer/orders.php?q=' . urlencode($q))) ?>">All</a>
    <?php foreach (ORDER_STATUSES as $key => $label): ?>
    <a class="pill <?= $statusFilter === $key ? 'active' : '' ?>"
       href="<?= e(url('/customer/orders.php?status=' . urlencode($key) . '&q=' . urlencode($q))) ?>">
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
            <th>Order</th><th>Placed</th><th>Items</th><th>Type</th>
            <th class="num">Total</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td class="strong tracking-id"><?= e($o['order_number']) ?></td>
            <td><?= e(format_date($o['created_at'], 'M j, Y g:i A')) ?></td>
            <td><?= (int) $o['item_count'] ?></td>
            <td style="text-transform:capitalize"><?= e($o['delivery_type']) ?></td>
            <td class="num strong"><?= format_price($o['total']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td>
              <div class="row-actions">
                <?php if (in_array($o['status'], ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery'], true)): ?>
                <a href="<?= url('/customer/track.php?order_id=' . (int) $o['id']) ?>" class="btn btn-terra btn-sm">Track</a>
                <?php endif; ?>
                <form method="post" action="<?= url('/customer/orders.php') ?>" style="display:inline">
                  <?= csrf_input() ?>
                  <input type="hidden" name="reorder" value="<?= (int) $o['id'] ?>">
                  <button type="submit" class="btn btn-outline dark btn-sm">Reorder</button>
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
echo render_pagination($page, $pages, '/customer/orders.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null])));
?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
  <h3>No orders found</h3>
  <p><?= $q || $statusFilter ? 'Try different filters.' : 'Your delicious journey starts here.' ?></p>
  <a href="<?= url('/customer/menu.php') ?>" class="btn btn-terra" style="margin-top:1.2rem">Order Now</a>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/customer_footer.php';
