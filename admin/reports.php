<?php
/**
 * Admin — sales & order reports
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

$range = in_array($_GET['range'] ?? '', ['7', '30', '90', 'all'], true) ? $_GET['range'] : '30';
$rangeDays = $range === 'all' ? 36500 : (int) $range;

// Summary
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue, COALESCE(AVG(total), 0) AS avg_order
     FROM orders WHERE status != 'cancelled' AND created_at >= CURDATE() - INTERVAL :days DAY"
);
$stmt->bindValue(':days', $rangeDays, PDO::PARAM_INT);
$stmt->execute();
$summary = $stmt->fetch();

// Daily series
$chartDays = min($rangeDays, 30);
$stmt = $pdo->prepare(
    "SELECT DATE(created_at) AS d, COALESCE(SUM(total), 0) AS sales, COUNT(*) AS cnt
     FROM orders
     WHERE status != 'cancelled' AND created_at >= CURDATE() - INTERVAL :days DAY
     GROUP BY DATE(created_at) ORDER BY d ASC"
);
$stmt->bindValue(':days', $chartDays, PDO::PARAM_INT);
$stmt->execute();
$chartRows = $stmt->fetchAll();
$chart = [];
$maxSales = 1.0;
foreach ($chartRows as $r) {
    $chart[$r['d']] = ['sales' => (float) $r['sales'], 'cnt' => (int) $r['cnt']];
    $maxSales = max($maxSales, (float) $r['sales']);
}

// Top dishes
$stmt = $pdo->prepare(
    'SELECT oi.item_name, SUM(oi.quantity) AS qty, SUM(oi.total_price) AS revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.status != "cancelled" AND o.created_at >= CURDATE() - INTERVAL :days DAY
     GROUP BY oi.item_name ORDER BY revenue DESC LIMIT 10'
);
$stmt->bindValue(':days', $rangeDays, PDO::PARAM_INT);
$stmt->execute();
$topDishes = $stmt->fetchAll();
$maxQty = $topDishes ? (int) $topDishes[0]['qty'] : 1;

// Payment methods
$stmt = $pdo->prepare(
    "SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS revenue
     FROM orders WHERE status != 'cancelled' AND created_at >= CURDATE() - INTERVAL :days DAY
     GROUP BY payment_method ORDER BY cnt DESC"
);
$stmt->bindValue(':days', $rangeDays, PDO::PARAM_INT);
$stmt->execute();
$payMethods = $stmt->fetchAll();

// Delivery vs pickup
$stmt = $pdo->prepare(
    "SELECT delivery_type, COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS revenue
     FROM orders WHERE status != 'cancelled' AND created_at >= CURDATE() - INTERVAL :days DAY
     GROUP BY delivery_type"
);
$stmt->bindValue(':days', $rangeDays, PDO::PARAM_INT);
$stmt->execute();
$deliverySplit = $stmt->fetchAll();

// Status distribution
$stmt = $pdo->prepare(
    "SELECT status, COUNT(*) AS cnt FROM orders
     WHERE created_at >= CURDATE() - INTERVAL :days DAY
     GROUP BY status ORDER BY cnt DESC"
);
$stmt->bindValue(':days', $rangeDays, PDO::PARAM_INT);
$stmt->execute();
$statusDist = $stmt->fetchAll();

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $pdo->prepare(
        'SELECT o.order_number, o.customer_name, o.email, o.phone, o.delivery_type,
                o.subtotal, o.delivery_fee, o.tax, o.total, o.payment_method,
                o.payment_status, o.status, o.created_at
         FROM orders o
         WHERE o.status != "cancelled" AND o.created_at >= CURDATE() - INTERVAL :days DAY
         ORDER BY o.id DESC'
    );
    $stmt->bindValue(':days', $rangeDays, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="umalafatik-orders-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Order #', 'Customer', 'Email', 'Phone', 'Type', 'Subtotal', 'Delivery', 'Tax', 'Total', 'Payment', 'Payment Status', 'Status', 'Placed At']);
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}

$page_title = 'Reports';
$nav_active = 'reports';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="toolbar">
  <div class="filter-pills" role="group" aria-label="Report range">
    <?php foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'all' => 'All time'] as $key => $label): ?>
    <a class="pill <?= $range === $key ? 'active' : '' ?>" href="<?= e(url('/admin/reports.php?range=' . $key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <a href="<?= e(url('/admin/reports.php?range=' . urlencode($range) . '&export=csv')) ?>" class="btn btn-outline dark btn-sm">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
    Export CSV
  </a>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon terra">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
    </div>
    <div><div class="stat-num"><?= (int) $summary['orders'] ?></div><div class="stat-label">Orders</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div><div class="stat-num"><?= format_price((float) $summary['revenue']) ?></div><div class="stat-label">Revenue</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
    </div>
    <div><div class="stat-num"><?= format_price((float) $summary['avg_order']) ?></div><div class="stat-label">Avg. Order Value</div></div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Revenue Trend</h3></div>
  <div class="panel-body">
    <div class="bar-chart" role="img" aria-label="Revenue trend chart">
      <?php
      $start = new DateTime('-' . ($chartDays - 1) . ' days');
      for ($i = 0; $i < $chartDays; $i++):
        $d = $start->format('Y-m-d');
        $entry = $chart[$d] ?? ['sales' => 0.0, 'cnt' => 0];
        $h = $entry['sales'] > 0 ? max(6, round(($entry['sales'] / $maxSales) * 100)) : 3;
      ?>
      <div class="bar-col">
        <div class="bar" style="height:<?= $h ?>%">
          <span class="bar-tip"><?= e(format_date($d, 'M j')) ?> · <?= format_price($entry['sales']) ?> · <?= $entry['cnt'] ?> orders</span>
        </div>
        <span class="bar-label"><?= $chartDays > 14 ? e(format_date($d, 'j M')) : e(format_date($d, 'D j')) ?></span>
      </div>
      <?php
      $start->modify('+1 day');
      endfor;
      ?>
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns:1.4fr 1fr; gap:1.8rem" class="dash-cols">
  <div class="panel">
    <div class="panel-head"><h3>Top Dishes by Revenue</h3></div>
    <div class="panel-body flush">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>#</th><th>Dish</th><th class="num">Qty sold</th><th class="num">Revenue</th><th style="width:30%">Share</th></tr></thead>
          <tbody>
            <?php foreach ($topDishes as $i => $t): ?>
            <tr>
              <td class="text-muted"><?= $i + 1 ?></td>
              <td class="strong"><?= e($t['item_name']) ?></td>
              <td class="num"><?= (int) $t['qty'] ?></td>
              <td class="num strong"><?= format_price((float) $t['revenue']) ?></td>
              <td>
                <div style="height:8px; background:var(--cream-100); border-radius:99px; overflow:hidden">
                  <div style="height:100%; width:<?= max(6, round((int) $t['qty'] / $maxQty * 100)) ?>%; background:linear-gradient(90deg, var(--gold), var(--terra)); border-radius:99px"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="display:grid; gap:1.8rem; align-content:start">
    <div class="panel">
      <div class="panel-head"><h3>Payment Methods</h3></div>
      <div class="panel-body flush">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Method</th><th class="num">Orders</th><th class="num">Revenue</th></tr></thead>
            <tbody>
              <?php foreach ($payMethods as $pm): ?>
              <tr>
                <td class="strong"><?= e(PAYMENT_METHODS[$pm['payment_method']] ?? $pm['payment_method']) ?></td>
                <td class="num"><?= (int) $pm['cnt'] ?></td>
                <td class="num strong"><?= format_price((float) $pm['revenue']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Delivery vs Pickup</h3></div>
      <div class="panel-body">
        <?php
        $totalOrders = 0;
        foreach ($deliverySplit as $ds) { $totalOrders += (int) $ds['cnt']; }
        foreach ($deliverySplit as $ds):
          $pct = $totalOrders > 0 ? round((int) $ds['cnt'] / $totalOrders * 100) : 0;
        ?>
        <div style="margin-bottom:1rem">
          <div style="display:flex; justify-content:space-between; margin-bottom:0.35rem">
            <strong style="text-transform:capitalize; font-size:0.92rem"><?= e($ds['delivery_type']) ?></strong>
            <span class="text-muted" style="font-size:0.85rem"><?= (int) $ds['cnt'] ?> orders · <?= $pct ?>% · <?= format_price((float) $ds['revenue']) ?></span>
          </div>
          <div style="height:8px; background:var(--cream-100); border-radius:99px; overflow:hidden">
            <div style="height:100%; width:<?= $pct ?>%; background:linear-gradient(90deg, var(--gold), var(--terra)); border-radius:99px"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Order Statuses</h3></div>
      <div class="panel-body" style="display:flex; flex-wrap:wrap; gap:0.6rem">
        <?php foreach ($statusDist as $sd): ?>
        <span class="badge badge-<?= e($sd['status']) ?>" style="font-size:0.85rem; padding:0.45rem 1rem">
          <?= e(ORDER_STATUSES[$sd['status']] ?? $sd['status']) ?> · <?= (int) $sd['cnt'] ?>
        </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
