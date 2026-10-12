<?php
/**
 * Customer — reservations management
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$user = current_customer();
$userId = (int) $user['id'];

// Cancel reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/customer/reservations.php');
    }
    $resId = int_in($_POST['cancel_reservation'] ?? 0, 1, PHP_INT_MAX);
    $stmt = $pdo->prepare('SELECT id, reservation_date FROM reservations WHERE id = ? AND user_id = ? AND status IN ("pending","confirmed")');
    $stmt->execute([$resId, $userId]);
    $res = $stmt->fetch();
    if ($res) {
        $upd = $pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE id = ?');
        $upd->execute([$resId]);
        push_notification($pdo, $userId, 'reservation', 'Your reservation for ' . format_date($res['reservation_date']) . ' has been cancelled.', null, $resId);
        set_flash('success', 'Reservation cancelled.');
    } else {
        set_flash('error', 'This reservation can no longer be cancelled.');
    }
    redirect('/customer/reservations.php');
}

$filter = str_in($_GET['filter'] ?? 'upcoming', 20);
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

switch ($filter) {
    case 'past':
        $where = 'user_id = ? AND reservation_date < CURDATE()';
        break;
    case 'cancelled':
        $where = 'user_id = ? AND status = "cancelled"';
        break;
    case 'upcoming':
    default:
        $where = 'user_id = ? AND reservation_date >= CURDATE() AND status IN ("pending","confirmed")';
        break;
}

$params = [$userId];
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE ' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 8;
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT * FROM reservations WHERE ' . $where . '
     ORDER BY reservation_date ' . ($filter === 'past' ? 'DESC' : 'ASC') . ', reservation_time ' . ($filter === 'past' ? 'DESC' : 'ASC') . '
     LIMIT :lim OFFSET :off'
);
$stmt->execute($params);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$reservations = $stmt->fetchAll();

$page_title = 'My Reservations';
$nav_active = 'reservations';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.6rem">
  <div class="filter-pills">
    <?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label): ?>
    <a class="pill <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('/customer/reservations.php?filter=' . $key)) ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
  <a href="<?= url('/reservation.php') ?>" class="btn btn-terra btn-sm">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
    New Reservation
  </a>
</div>

<?php if ($reservations): ?>
<?php foreach ($reservations as $r): ?>
<div class="res-card">
  <div class="res-date">
    <strong><?= (int) format_date($r['reservation_date'], 'j') ?></strong>
    <span><?= e(format_date($r['reservation_date'], 'M')) ?></span>
  </div>
  <div>
    <h3><?= (int) $r['guests'] ?> guest<?= $r['guests'] === 1 ? '' : 's' ?> · <?= e(format_time($r['reservation_time'])) ?></h3>
    <p>
      <?= e($r['seating'] ?? 'Indoor') ?> seating ·
      <?= status_badge($r['status']) ?>
    </p>
    <?php if (!empty($r['special_requests'])): ?>
    <p style="margin-top:0.4rem">“<?= e($r['special_requests']) ?>”</p>
    <?php endif; ?>
    <p class="text-muted" style="font-size:0.82rem; margin-top:0.4rem">
      Reference: <?= e($r['name']) ?> · <?= e($r['phone']) ?>
    </p>
  </div>
  <div class="res-actions">
    <?php if (in_array($r['status'], ['pending', 'confirmed'], true) && strtotime((string) $r['reservation_date']) >= strtotime('today')): ?>
    <form method="post" action="<?= url('/customer/reservations.php') ?>">
      <?= csrf_input() ?>
      <input type="hidden" name="cancel_reservation" value="<?= (int) $r['id'] ?>">
      <button type="submit" class="btn btn-danger btn-sm"
              onclick="return confirm('Cancel this reservation?')">Cancel</button>
    </form>
    <?php endif; ?>
    <a href="<?= url('/reservation.php') ?>" class="btn btn-outline dark btn-sm">New</a>
  </div>
</div>
<?php endforeach; ?>

<?php echo render_pagination($page, $pages, '/customer/reservations.php?filter=' . urlencode($filter)); ?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
  <h3>No reservations</h3>
  <p>Book a table for your next visit.</p>
  <a href="<?= url('/reservation.php') ?>" class="btn btn-terra" style="margin-top:1.2rem">Reserve a Table</a>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/customer_footer.php';
