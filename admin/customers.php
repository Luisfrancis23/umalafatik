<?php
/**
 * Admin — customer accounts
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

// Block / activate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/customers.php');
    }
    $userId = int_in($_POST['user_id'] ?? 0, 1, PHP_INT_MAX);
    $action = str_in($_POST['user_action'] ?? '', 20);

    $stmt = $pdo->prepare('SELECT first_name, status FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if ($u) {
        if ($action === 'block' && (string) $u['status'] !== 'blocked') {
            $upd = $pdo->prepare('UPDATE users SET status = "blocked" WHERE id = ?');
            $upd->execute([$userId]);
            set_flash('success', $u['first_name'] . ' has been blocked.');
        } elseif ($action === 'activate' && (string) $u['status'] !== 'active') {
            $upd = $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?');
            $upd->execute([$userId]);
            set_flash('success', $u['first_name'] . ' has been activated.');
        }
    }
    redirect('/admin/customers.php');
}

$q = str_in($_GET['q'] ?? '', 100);
$statusFilter = str_in($_GET['status'] ?? '', 10);
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

$where = ['1 = 1'];
$params = [];
if ($statusFilter === 'active' || $statusFilter === 'blocked') {
    $where[] = 'status = :st';
    $params[':st'] = $statusFilter;
}
if ($q !== '') {
    $where[] = '(first_name LIKE :q OR last_name LIKE :q OR email LIKE :q OR phone LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM users ' . $whereSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 15;
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT u.*,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.status != "cancelled") AS order_count,
            (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.user_id = u.id AND o.status != "cancelled") AS total_spent
     FROM users u ' . $whereSql . '
     ORDER BY u.id DESC
     LIMIT :lim OFFSET :off'
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

// Customer detail view
$detail = null;
if (isset($_GET['view'])) {
    $viewId = int_in($_GET['view'], 1, PHP_INT_MAX);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$viewId]);
    $detail = $stmt->fetch();
}

$page_title = 'Customers';
$nav_active = 'customers';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($detail): ?>
<div class="panel" style="max-width:760px">
  <div class="panel-head">
    <h3><?= e($detail['first_name']) ?> <?= e($detail['last_name']) ?></h3>
    <a href="<?= url('/admin/customers.php') ?>" class="btn btn-outline dark btn-sm">← All Customers</a>
  </div>
  <div class="panel-body">
    <dl class="detail-list">
      <div class="detail-row"><dt>Email</dt><dd><a href="mailto:<?= e($detail['email']) ?>"><?= e($detail['email']) ?></a></dd></div>
      <div class="detail-row"><dt>Phone</dt><dd><?= e($detail['phone']) ?></dd></div>
      <div class="detail-row"><dt>Status</dt><dd><span class="badge <?= $detail['status'] === 'active' ? 'badge-completed' : 'badge-blocked' ?>"><?= e($detail['status']) ?></span></dd></div>
      <div class="detail-row"><dt>Member since</dt><dd><?= e(format_date($detail['created_at'], 'M j, Y g:i A')) ?></dd></div>
      <div class="detail-row"><dt>Last login</dt><dd><?= $detail['last_login'] ? e(format_date($detail['last_login'], 'M j, Y g:i A')) : 'Never' ?></dd></div>
      <div class="detail-row"><dt>Default address</dt><dd><?= e($detail['default_address'] ?: '—') ?><?= $detail['city'] ? ', ' . e($detail['city']) : '' ?> <?= e($detail['postal_code'] ?? '') ?></dd></div>
    </dl>
    <div class="form-actions" style="margin-top:1.4rem">
      <form method="post" action="<?= url('/admin/customers.php') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="user_id" value="<?= (int) $detail['id'] ?>">
        <input type="hidden" name="user_action" value="<?= $detail['status'] === 'active' ? 'block' : 'activate' ?>">
        <button type="submit" class="btn <?= $detail['status'] === 'active' ? 'btn-danger' : 'btn-terra' ?> btn-sm"
                data-confirm="<?= $detail['status'] === 'active' ? 'Block this customer? They will be unable to sign in.' : 'Activate this customer account?' ?>">
          <?= $detail['status'] === 'active' ? 'Block Account' : 'Activate Account' ?>
        </button>
      </form>
      <a href="<?= url('/admin/orders.php?q=' . urlencode($detail['email'])) ?>" class="btn btn-outline dark btn-sm">View Orders</a>
    </div>
  </div>
</div>
<?php else: ?>

<form class="toolbar" method="get" action="<?= url('/admin/customers.php') ?>" role="search" aria-label="Search customers">
  <div class="search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone…" aria-label="Search customers">
  </div>
  <div class="filter-pills" role="group" aria-label="Filter by status">
    <a class="pill <?= $statusFilter === '' ? 'active' : '' ?>" href="<?= e(url('/admin/customers.php?q=' . urlencode($q))) ?>">All</a>
    <a class="pill <?= $statusFilter === 'active' ? 'active' : '' ?>" href="<?= e(url('/admin/customers.php?status=active&q=' . urlencode($q))) ?>">Active</a>
    <a class="pill <?= $statusFilter === 'blocked' ? 'active' : '' ?>" href="<?= e(url('/admin/customers.php?status=blocked&q=' . urlencode($q))) ?>">Blocked</a>
  </div>
  <input type="hidden" name="q" value="<?= e($q) ?>">
</form>

<?php if ($customers): ?>
<div class="panel">
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Customer</th><th>Contact</th><th class="num">Orders</th><th class="num">Total spent</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
          <tr>
            <td>
              <div class="cell-dish">
                <span class="user-avatar" style="width:42px; height:42px" aria-hidden="true"><?= e(mb_substr($c['first_name'], 0, 1) . mb_substr($c['last_name'], 0, 1)) ?></span>
                <div>
                  <strong><?= e($c['first_name']) ?> <?= e($c['last_name']) ?></strong>
                  <small class="text-muted">since <?= e(format_date($c['created_at'], 'M Y')) ?></small>
                </div>
              </div>
            </td>
            <td>
              <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>
              <small style="display:block" class="text-muted"><?= e($c['phone']) ?></small>
            </td>
            <td class="num"><?= (int) $c['order_count'] ?></td>
            <td class="num strong"><?= format_price((float) $c['total_spent']) ?></td>
            <td><span class="badge <?= $c['status'] === 'active' ? 'badge-completed' : 'badge-blocked' ?>"><?= e($c['status']) ?></span></td>
            <td>
              <div class="row-actions">
                <a href="<?= url('/admin/customers.php?view=' . (int) $c['id']) ?>" class="btn btn-outline dark btn-sm">View</a>
                <form method="post" action="<?= url('/admin/customers.php') ?>" style="display:inline">
                  <?= csrf_input() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $c['id'] ?>">
                  <input type="hidden" name="user_action" value="<?= $c['status'] === 'active' ? 'block' : 'activate' ?>">
                  <button type="submit" class="btn <?= $c['status'] === 'active' ? 'btn-danger' : 'btn-terra' ?> btn-sm"
                          data-confirm="<?= $c['status'] === 'active' ? 'Block this customer?' : 'Activate this account?' ?>">
                    <?= $c['status'] === 'active' ? 'Block' : 'Activate' ?>
                  </button>
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
echo render_pagination($page, $pages, '/admin/customers.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null])));
?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
  <h3>No customers found</h3>
  <p><?= $q ? 'Try a different search.' : 'Customer accounts will appear here.' ?></p>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
