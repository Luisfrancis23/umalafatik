<?php
/**
 * Admin — reservations management
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/reservations.php');
    }
    $resId = int_in($_POST['reservation_id'] ?? 0, 1, PHP_INT_MAX);
    $action = str_in($_POST['reservation_action'] ?? '', 20);

    $stmt = $pdo->prepare('SELECT user_id, name, reservation_date FROM reservations WHERE id = ?');
    $stmt->execute([$resId]);
    $res = $stmt->fetch();

    if ($res) {
        if ($action === 'confirm') {
            $upd = $pdo->prepare('UPDATE reservations SET status = "confirmed", updated_at = NOW() WHERE id = ?');
            $upd->execute([$resId]);
            if ((int) $res['user_id'] > 0) {
                push_notification($pdo, (int) $res['user_id'], 'reservation',
                    'Your reservation for ' . format_date($res['reservation_date']) . ' has been confirmed. We look forward to hosting you!', null, $resId);
            }
            set_flash('success', 'Reservation confirmed. Customer notified.');
        } elseif ($action === 'cancel') {
            $upd = $pdo->prepare('UPDATE reservations SET status = "cancelled", updated_at = NOW() WHERE id = ?');
            $upd->execute([$resId]);
            if ((int) $res['user_id'] > 0) {
                push_notification($pdo, (int) $res['user_id'], 'reservation',
                    'Your reservation for ' . format_date($res['reservation_date']) . ' has been cancelled. Please call us to rebook.', null, $resId);
            }
            set_flash('success', 'Reservation cancelled. Customer notified.');
        } elseif ($action === 'complete') {
            $upd = $pdo->prepare('UPDATE reservations SET status = "completed", updated_at = NOW() WHERE id = ?');
            $upd->execute([$resId]);
            set_flash('success', 'Reservation marked as completed.');
        }
    }
    redirect('/admin/reservations.php');
}

$filter = str_in($_GET['filter'] ?? 'upcoming', 20);
$q = str_in($_GET['q'] ?? '', 100);
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

switch ($filter) {
    case 'past':
        $where = 'reservation_date < CURDATE()';
        break;
    case 'pending':
        $where = 'status = "pending"';
        break;
    case 'confirmed':
        $where = 'status = "confirmed"';
        break;
    case 'cancelled':
        $where = 'status = "cancelled"';
        break;
    case 'all':
        $where = '1 = 1';
        break;
    case 'upcoming':
    default:
        $where = 'reservation_date >= CURDATE() AND status IN ("pending","confirmed")';
        break;
}

$params = [];
if ($q !== '') {
    $where .= ' AND (name LIKE :q OR email LIKE :q OR phone LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE ' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 12;
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

$page_title = 'Reservations';
$nav_active = 'reservations';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<form class="toolbar" method="get" action="<?= url('/admin/reservations.php') ?>" role="search" aria-label="Search reservations">
  <div class="search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone…" aria-label="Search reservations">
  </div>
  <div class="filter-pills" role="group" aria-label="Filter reservations">
    <?php foreach (['upcoming' => 'Upcoming', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'past' => 'Past', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label): ?>
    <a class="pill <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('/admin/reservations.php?filter=' . $key . '&q=' . urlencode($q))) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <input type="hidden" name="q" value="<?= e($q) ?>">
</form>

<?php if ($reservations): ?>
<div class="panel">
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Guest</th><th>Date & Time</th><th>Guests</th><th>Seating</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $r): ?>
          <tr>
            <td>
              <strong><?= e($r['name']) ?></strong>
              <small style="display:block" class="text-muted"><?= e($r['email']) ?> · <?= e($r['phone']) ?></small>
              <?php if (!empty($r['special_requests'])): ?>
              <small style="display:block" class="text-muted">“<?= e($r['special_requests']) ?>”</small>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= e(format_date($r['reservation_date'], 'M j, Y')) ?></strong>
              <small style="display:block" class="text-muted"><?= e(format_time($r['reservation_time'])) ?></small>
            </td>
            <td class="num strong"><?= (int) $r['guests'] ?></td>
            <td style="text-transform:capitalize"><?= e($r['seating'] ?? 'Indoor') ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td>
              <div class="row-actions">
                <?php if (in_array($r['status'], ['pending', 'confirmed'], true) && strtotime((string) $r['reservation_date']) >= strtotime('today')): ?>
                <form method="post" action="<?= url('/admin/reservations.php') ?>" style="display:inline">
                  <?= csrf_input() ?>
                  <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="reservation_action" value="confirm">
                  <button type="submit" class="btn btn-terra btn-sm" <?= $r['status'] === 'confirmed' ? 'disabled' : '' ?>>Confirm</button>
                </form>
                <form method="post" action="<?= url('/admin/reservations.php') ?>" style="display:inline">
                  <?= csrf_input() ?>
                  <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="reservation_action" value="cancel">
                  <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                </form>
                <?php endif; ?>
                <?php if ($r['status'] === 'confirmed' && strtotime((string) $r['reservation_date']) < strtotime('today')): ?>
                <form method="post" action="<?= url('/admin/reservations.php') ?>" style="display:inline">
                  <?= csrf_input() ?>
                  <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="reservation_action" value="complete">
                  <button type="submit" class="btn btn-outline dark btn-sm">Mark Completed</button>
                </form>
                <?php endif; ?>
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
echo render_pagination($page, $pages, '/admin/reservations.php?' . http_build_query(array_filter(['filter' => $filter !== 'upcoming' ? $filter : null, 'q' => $q ?: null])));
?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
  <h3>No reservations</h3>
  <p><?= $q || $filter !== 'upcoming' ? 'Try different filters.' : 'New reservations will appear here.' ?></p>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
