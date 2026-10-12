<?php
/**
 * Admin — dashboard overview
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

// --- Stats ---
$today = date('Y-m-d');

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled'");
$todayOrders = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'");
$totalSales = (float) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled'");
$todaySales = (float) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM users');
$totalCustomers = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status IN ('pending','confirmed') AND reservation_date >= CURDATE()");
$upcomingReservations = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','preparing','ready','out_for_delivery')");
$activeOrders = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
$pendingReviews = (int) $stmt->fetchColumn();

// --- Popular dishes (last 30 days) ---
$stmt = $pdo->query(
    'SELECT oi.item_name, SUM(oi.quantity) AS qty, SUM(oi.total_price) AS revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.status != "cancelled" AND o.created_at >= NOW() - INTERVAL 30 DAY
     GROUP BY oi.item_name
     ORDER BY qty DESC LIMIT 5'
);
$popular = $stmt->fetchAll();
$maxQty = $popular ? (int) $popular[0]['qty'] : 1;

// --- Sales chart (last 14 days) ---
$stmt = $pdo->query(
    "SELECT DATE(created_at) AS d, COALESCE(SUM(total), 0) AS sales, COUNT(*) AS cnt
     FROM orders
     WHERE status != 'cancelled' AND created_at >= CURDATE() - INTERVAL 13 DAY
     GROUP BY DATE(created_at) ORDER BY d ASC"
);
$chartRows = $stmt->fetchAll();
$chart = [];
$maxSales = 1.0;
foreach ($chartRows as $r) {
    $chart[$r['d']] = ['sales' => (float) $r['sales'], 'cnt' => (int) $r['cnt']];
    $maxSales = max($maxSales, (float) $r['sales']);
}

// --- Recent orders ---
$stmt = $pdo->query(
    'SELECT id, order_number, customer_name, status, total, delivery_type, created_at
     FROM orders ORDER BY id DESC LIMIT 8'
);
$recentOrders = $stmt->fetchAll();

// --- Latest reservations ---
$stmt = $pdo->query(
    'SELECT * FROM reservations ORDER BY id DESC LIMIT 5'
);
$latestReservations = $stmt->fetchAll();

// --- Low availability ---
$stmt = $pdo->query('SELECT * FROM menu_items WHERE is_available = 0 ORDER BY id DESC LIMIT 5');
$unavailable = $stmt->fetchAll();

$page_title = 'Overview';
$nav_active = 'dashboard';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon terra">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
    </div>
    <div>
      <div class="stat-num"><?= $todayOrders ?></div>
      <div class="stat-label">Orders Today</div>
      <div class="text-muted" style="font-size:0.78rem"><?= $activeOrders ?> active now</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div>
      <div class="stat-num"><?= format_price($todaySales) ?></div>
      <div class="stat-label">Sales Today</div>
      <div class="text-muted" style="font-size:0.78rem"><?= format_price($totalSales) ?> all time</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div>
      <div class="stat-num"><?= $totalCustomers ?></div>
      <div class="stat-label">Customers</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
    </div>
    <div>
      <div class="stat-num"><?= $upcomingReservations ?></div>
      <div class="stat-label">Reservations</div>
      <div class="text-muted" style="font-size:0.78rem"><?= $pendingReviews ?> review(s) awaiting approval</div>
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns:1.6fr 1fr; gap:1.8rem" class="dash-cols">
  <div style="display:grid; gap:1.8rem; align-content:start">
    <!-- Sales chart -->
    <div class="panel">
      <div class="panel-head">
        <h3>Sales — Last 14 Days</h3>
        <a href="<?= url('/admin/reports.php') ?>" class="btn btn-outline dark btn-sm">Full Reports</a>
      </div>
      <div class="panel-body">
        <div class="bar-chart" role="img" aria-label="Sales chart for the last 14 days">
          <?php
          $start = new DateTime('-13 days');
          for ($i = 0; $i < 14; $i++):
            $d = $start->format('Y-m-d');
            $entry = $chart[$d] ?? ['sales' => 0.0, 'cnt' => 0];
            $h = $entry['sales'] > 0 ? max(6, round(($entry['sales'] / $maxSales) * 100)) : 3;
          ?>
          <div class="bar-col">
            <div class="bar" style="height:<?= $h ?>%">
              <span class="bar-tip"><?= e(format_date($d, 'M j')) ?> · <?= format_price($entry['sales']) ?> · <?= $entry['cnt'] ?> orders</span>
            </div>
            <span class="bar-label"><?= e(format_date($d, 'j M')) ?></span>
          </div>
          <?php
          $start->modify('+1 day');
          endfor;
          ?>
        </div>
      </div>
    </div>

    <!-- Recent orders -->
    <div class="panel">
      <div class="panel-head">
        <h3>Recent Orders</h3>
        <a href="<?= url('/admin/orders.php') ?>" class="btn btn-outline dark btn-sm">View All</a>
      </div>
      <div class="panel-body flush">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>Order</th><th>Customer</th><th>Placed</th><th class="num">Total</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td class="strong tracking-id"><?= e($o['order_number']) ?></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= e(format_date($o['created_at'], 'M j, g:i A')) ?></td>
                <td class="num strong"><?= format_price($o['total']) ?></td>
                <td><?= status_badge($o['status']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div style="display:grid; gap:1.8rem; align-content:start">
    <!-- Popular dishes -->
    <div class="panel">
      <div class="panel-head">
        <h3>Popular Dishes</h3>
        <span class="text-muted" style="font-size:0.8rem">Last 30 days</span>
      </div>
      <div class="panel-body">
        <?php if ($popular): ?>
        <?php foreach ($popular as $i => $p): ?>
        <div style="margin-bottom:1rem">
          <div style="display:flex; justify-content:space-between; margin-bottom:0.35rem">
            <strong style="font-size:0.92rem"><?= e($p['item_name']) ?></strong>
            <span class="text-muted" style="font-size:0.85rem"><?= (int) $p['qty'] ?> sold · <?= format_price((float) $p['revenue']) ?></span>
          </div>
          <div style="height:8px; background:var(--cream-100); border-radius:99px; overflow:hidden">
            <div style="height:100%; width:<?= max(8, round((int) $p['qty'] / $maxQty * 100)) ?>%; background:linear-gradient(90deg, var(--gold), var(--terra)); border-radius:99px"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="text-muted">No orders yet — the charts will come alive with your first orders.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Reservations -->
    <div class="panel">
      <div class="panel-head">
        <h3>Latest Reservations</h3>
        <a href="<?= url('/admin/reservations.php') ?>" class="btn btn-outline dark btn-sm">Manage</a>
      </div>
      <div class="panel-body" style="display:grid; gap:0.8rem">
        <?php if ($latestReservations): ?>
        <?php foreach ($latestReservations as $r): ?>
        <div class="res-card" style="grid-template-columns:auto 1fr; margin-bottom:0; box-shadow:none">
          <div class="res-date">
            <strong><?= (int) format_date($r['reservation_date'], 'j') ?></strong>
            <span><?= e(format_date($r['reservation_date'], 'M')) ?></span>
          </div>
          <div>
            <h3 style="font-size:1.05rem"><?= e($r['name']) ?> · <?= (int) $r['guests'] ?> guests</h3>
            <p><?= e(format_time($r['reservation_time'])) ?> · <?= e($r['seating'] ?? 'Indoor') ?> · <?= status_badge($r['status']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="text-muted">No reservations yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Unavailable items -->
    <?php if ($unavailable): ?>
    <div class="panel">
      <div class="panel-head">
        <h3>Unavailable Items</h3>
        <a href="<?= url('/admin/menu-items.php') ?>" class="btn btn-outline dark btn-sm">Menu Items</a>
      </div>
      <div class="panel-body flush">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Dish</th><th>Price</th></tr></thead>
            <tbody>
              <?php foreach ($unavailable as $u): ?>
              <tr>
                <td class="strong"><?= e($u['name']) ?></td>
                <td class="num"><?= format_price($u['price']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
