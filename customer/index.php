<?php
/**
 * Customer dashboard — overview
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();
$user = current_customer();
$userId = (int) $user['id'];

// Stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status != "cancelled"');
$stmt->execute([$userId]);
$totalOrders = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ("pending","confirmed","preparing","ready","out_for_delivery")');
$stmt->execute([$userId]);
$activeOrders = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ("pending","confirmed") AND reservation_date >= CURDATE()');
$stmt->execute([$userId]);
$upcomingReservations = (int) $stmt->fetchColumn();

$unread = unread_notifications($pdo, $userId);

// Recent orders
$stmt = $pdo->prepare(
    'SELECT id, order_number, status, total, delivery_type, payment_status, created_at
     FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5'
);
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

// Upcoming reservation
$stmt = $pdo->prepare(
    'SELECT * FROM reservations
     WHERE user_id = ? AND status IN ("pending","confirmed") AND reservation_date >= CURDATE()
     ORDER BY reservation_date ASC, reservation_time ASC LIMIT 1'
);
$stmt->execute([$userId]);
$nextReservation = $stmt->fetch();

// Favourite category (from order items)
$stmt = $pdo->prepare(
    'SELECT c.name, COUNT(*) AS cnt
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN menu_items mi ON mi.id = oi.menu_item_id
     LEFT JOIN categories c ON c.id = mi.category_id
     WHERE o.user_id = ? AND o.status != "cancelled"
     GROUP BY c.id ORDER BY cnt DESC LIMIT 1'
);
$stmt->execute([$userId]);
$favCategory = $stmt->fetch();

$page_title = 'My Dashboard';
$nav_active = 'dashboard';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon terra">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
    </div>
    <div><div class="stat-num"><?= $totalOrders ?></div><div class="stat-label">Total Orders</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
    </div>
    <div><div class="stat-num"><?= $activeOrders ?></div><div class="stat-label">Active Orders</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
    </div>
    <div><div class="stat-num"><?= $upcomingReservations ?></div><div class="stat-label">Reservations</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
    </div>
    <div><div class="stat-num"><?= $unread ?></div><div class="stat-label">Notifications</div></div>
  </div>
</div>

<?php if ($activeOrders > 0): ?>
<div class="panel">
  <div class="panel-head">
    <h3>Track your order</h3>
    <div class="panel-tools">
      <a href="<?= url('/customer/track.php') ?>" class="btn btn-terra btn-sm">Track Order</a>
    </div>
  </div>
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Order</th><th>Placed</th><th>Type</th><th>Total</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $o): if (in_array($o['status'], ['pending','confirmed','preparing','ready','out_for_delivery'], true)): ?>
          <tr>
            <td class="strong"><?= e($o['order_number']) ?></td>
            <td><?= e(format_date($o['created_at'], 'M j, g:i A')) ?></td>
            <td style="text-transform:capitalize"><?= e($o['delivery_type']) ?></td>
            <td class="num strong"><?= format_price($o['total']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td><a href="<?= url('/customer/track.php?order_id=' . (int) $o['id']) ?>" class="btn btn-outline dark btn-sm">Track</a></td>
          </tr>
          <?php endif; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.8rem" class="dash-cols">
  <div class="panel">
    <div class="panel-head">
      <h3>Recent Orders</h3>
      <a href="<?= url('/customer/orders.php') ?>" class="text-muted" style="font-size:0.88rem">View all →</a>
    </div>
    <?php if ($recentOrders): ?>
    <div class="panel-body flush">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td class="strong"><?= e($o['order_number']) ?></td>
              <td><?= e(format_date($o['created_at'])) ?></td>
              <td class="num strong"><?= format_price($o['total']) ?></td>
              <td><?= status_badge($o['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php else: ?>
    <div class="panel-body empty-state" style="padding:2.5rem">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
      <h3>No orders yet</h3>
      <p>Your delicious journey starts here.</p>
      <a href="<?= url('/customer/menu.php') ?>" class="btn btn-terra btn-sm" style="margin-top:0.8rem">Order Now</a>
    </div>
    <?php endif; ?>
  </div>

  <div style="display:grid; gap:1.8rem; align-content:start">
    <?php if ($nextReservation): ?>
    <div class="panel">
      <div class="panel-head"><h3>Next Reservation</h3></div>
      <div class="panel-body">
        <div class="res-card" style="grid-template-columns:auto 1fr; margin-bottom:0">
          <div class="res-date">
            <strong><?= (int) format_date($nextReservation['reservation_date'], 'j') ?></strong>
            <span><?= e(format_date($nextReservation['reservation_date'], 'M')) ?></span>
          </div>
          <div>
            <h3><?= (int) $nextReservation['guests'] ?> guest<?= $nextReservation['guests'] === 1 ? '' : 's' ?> · <?= e(format_time($nextReservation['reservation_time'])) ?></h3>
            <p><?= e($nextReservation['seating']) ?> seating · <?= status_badge($nextReservation['status']) ?></p>
            <?php if (!empty($nextReservation['special_requests'])): ?>
            <p style="margin-top:0.4rem">“<?= e($nextReservation['special_requests']) ?>”</p>
            <?php endif; ?>
          </div>
        </div>
        <a href="<?= url('/customer/reservations.php') ?>" class="btn btn-outline dark btn-sm">Manage Reservations</a>
      </div>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h3>Quick Actions</h3></div>
      <div class="panel-body" style="display:grid; gap:0.8rem">
        <a href="<?= url('/customer/menu.php') ?>" class="dash-link" style="background:var(--cream-100); color:var(--ink)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.1 1.6 3.8 3.7 4L6.5 22h3l-.2-9C11.4 12.8 13 11.1 13 9V2h-2v7zm5-4v9h2.5v9h3V2c-3 0-5.5 2-5.5 5z"/></svg>
          <span>Browse the Menu</span>
        </a>
        <a href="<?= url('/customer/cart.php') ?>" class="dash-link" style="background:var(--cream-100); color:var(--ink)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.7 8.5a2 2 0 0 1-2 1.5H8.6a2 2 0 0 1-2-1.6L4.4 3.8A1 1 0 0 0 3.4 3H1"/></svg>
          <span>View Cart</span>
        </a>
        <a href="<?= url('/reservation.php') ?>" class="dash-link" style="background:var(--cream-100); color:var(--ink)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
          <span>Reserve a Table</span>
        </a>
        <a href="<?= url('/customer/profile.php') ?>" class="dash-link" style="background:var(--cream-100); color:var(--ink)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Edit Profile</span>
        </a>
      </div>
    </div>

    <?php if ($favCategory): ?>
    <div class="panel">
      <div class="panel-head"><h3>Your Taste</h3></div>
      <div class="panel-body">
        <p class="text-muted" style="margin-bottom:0.4rem">Your most-ordered category</p>
        <h3 style="color:var(--terra-dark)"><?= e($favCategory['name']) ?></h3>
        <a href="<?= url('/customer/menu.php') ?>" class="btn btn-gold btn-sm" style="margin-top:0.8rem">Order Again</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/customer_footer.php';
