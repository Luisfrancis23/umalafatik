<?php
/**
 * Customer — notifications
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$user = current_customer();
$userId = (int) $user['id'];

// Mark read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/customer/notifications.php');
    }
    if (isset($_POST['read_all'])) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
        set_flash('success', 'All notifications marked as read.');
    } elseif (isset($_POST['read_one'])) {
        $nid = int_in($_POST['read_one'] ?? 0, 1, PHP_INT_MAX);
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$nid, $userId]);
    }
    redirect('/customer/notifications.php');
}

$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));
$perPage = 15;

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$countStmt->execute([$userId]);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT :lim OFFSET :off'
);
$stmt->execute([$userId]);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

$unread = unread_notifications($pdo, $userId);

$page_title = 'Notifications';
$nav_active = 'notifications';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.6rem">
  <p class="text-muted" style="margin:0">
    <?= $unread > 0 ? '<strong>' . $unread . '</strong> unread notification(s).' : 'You are all caught up.' ?>
  </p>
  <?php if ($unread > 0): ?>
  <form method="post" action="<?= url('/customer/notifications.php') ?>">
    <?= csrf_input() ?>
    <button type="submit" name="read_all" value="1" class="btn btn-outline dark btn-sm">Mark all as read</button>
  </form>
  <?php endif; ?>
</div>

<?php if ($notifications): ?>
<?php foreach ($notifications as $n): ?>
<div class="notif<?= !(int) $n['is_read'] ? ' unread' : '' ?>">
  <span class="notif-icon">
    <?php if ((string) $n['type'] === 'order_status'): ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/></svg>
    <?php elseif ((string) $n['type'] === 'reservation'): ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
    <?php else: ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
    <?php endif; ?>
  </span>
  <div style="flex:1">
    <p><?= e($n['message']) ?></p>
    <small><?= e(format_date($n['created_at'], 'M j, Y g:i A')) ?></small>
  </div>
  <div class="notif-actions" style="display:flex; gap:0.5rem; align-items:center">
    <?php if ((int) $n['order_id'] > 0): ?>
    <a href="<?= url('/customer/track.php?order_id=' . (int) $n['order_id']) ?>" class="btn btn-terra btn-sm">View Order</a>
    <?php endif;
    if ((int) $n['reservation_id'] > 0): ?>
    <a href="<?= url('/customer/reservations.php') ?>" class="btn btn-outline dark btn-sm">View Reservation</a>
    <?php endif; ?>
    <?php if (!(int) $n['is_read']): ?>
    <form method="post" action="<?= url('/customer/notifications.php') ?>">
      <?= csrf_input() ?>
      <input type="hidden" name="read_one" value="<?= (int) $n['id'] ?>">
      <button type="submit" class="btn btn-ghost dark btn-sm" title="Mark as read">✓</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php echo render_pagination($page, $pages, '/customer/notifications.php'); ?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
  <h3>No notifications yet</h3>
  <p>Order updates, reservation confirmations and more will appear here.</p>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/customer_footer.php';
