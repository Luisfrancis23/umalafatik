<?php
/**
 * Admin — review moderation
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

// Moderate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/reviews.php');
    }
    $reviewId = int_in($_POST['review_id'] ?? 0, 1, PHP_INT_MAX);
    $action = str_in($_POST['review_action'] ?? '', 20);

    if (in_array($action, ['approve', 'reject', 'delete'], true)) {
        if ($action === 'delete') {
            $del = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
            $del->execute([$reviewId]);
            set_flash('success', 'Review deleted.');
        } else {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $upd = $pdo->prepare('UPDATE reviews SET status = ? WHERE id = ?');
            $upd->execute([$newStatus, $reviewId]);
            set_flash('success', 'Review ' . ($action === 'approve' ? 'approved and published' : 'rejected') . '.');
        }
    }
    redirect('/admin/reviews.php');
}

$filter = str_in($_GET['filter'] ?? 'pending', 20);
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

$statusMap = ['pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected', 'all' => 'all'];
$filter = $statusMap[$filter] ?? 'pending';

$params = [];
$where = $filter === 'all' ? '1 = 1' : 'r.status = :st';
if ($filter !== 'all') {
    $params[':st'] = $filter;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM reviews r WHERE ' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 12;
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT r.*, mi.name AS dish_name
     FROM reviews r
     LEFT JOIN menu_items mi ON mi.id = r.menu_item_id
     WHERE ' . $where . '
     ORDER BY r.created_at DESC
     LIMIT :lim OFFSET :off'
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

$pendingCount = (int) $pdo->query('SELECT COUNT(*) FROM reviews WHERE status = "pending"')->fetchColumn();

$page_title = 'Reviews';
$nav_active = 'reviews';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.4rem">
  <div class="filter-pills">
    <a class="pill <?= $filter === 'pending' ? 'active' : '' ?>" href="<?= url('/admin/reviews.php?filter=pending') ?>">Pending<?= $pendingCount ? ' (' . $pendingCount . ')' : '' ?></a>
    <a class="pill <?= $filter === 'approved' ? 'active' : '' ?>" href="<?= url('/admin/reviews.php?filter=approved') ?>">Approved</a>
    <a class="pill <?= $filter === 'rejected' ? 'active' : '' ?>" href="<?= url('/admin/reviews.php?filter=rejected') ?>">Rejected</a>
    <a class="pill <?= $filter === 'all' ? 'active' : '' ?>" href="<?= url('/admin/reviews.php?filter=all') ?>">All</a>
  </div>
  <p class="text-muted" style="margin:0; font-size:0.9rem">Approved reviews appear on the public website.</p>
</div>

<?php if ($reviews): ?>
<?php foreach ($reviews as $r): ?>
<div class="panel">
  <div class="panel-body" style="display:flex; gap:1.4rem; align-items:flex-start; flex-wrap:wrap">
    <span class="user-avatar" style="width:46px; height:46px; flex-shrink:0" aria-hidden="true">
      <?= e(mb_substr((string) $r['author_name'], 0, 1)) ?>
    </span>
    <div style="flex:1; min-width:240px">
      <div style="display:flex; align-items:center; gap:0.9rem; flex-wrap:wrap">
        <strong><?= e($r['author_name']) ?></strong>
        <?= stars((int) $r['rating']) ?>
        <?php if (!empty($r['dish_name'])): ?>
        <span class="chip terra"><?= e($r['dish_name']) ?></span>
        <?php endif; ?>
        <span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span>
        <span class="text-muted" style="font-size:0.82rem"><?= e(format_date($r['created_at'], 'M j, Y g:i A')) ?></span>
      </div>
      <?php if (!empty($r['title'])): ?>
      <h3 style="margin:0.5rem 0 0.2rem; font-size:1.2rem"><?= e($r['title']) ?></h3>
      <?php endif; ?>
      <p class="text-muted" style="margin:0.2rem 0 0">“<?= e($r['comment']) ?>”</p>
    </div>
    <?php if ((string) $r['status'] === 'pending'): ?>
    <div style="display:grid; gap:0.5rem">
      <form method="post" action="<?= url('/admin/reviews.php') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
        <input type="hidden" name="review_action" value="approve">
        <button type="submit" class="btn btn-terra btn-sm">Approve</button>
      </form>
      <form method="post" action="<?= url('/admin/reviews.php') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
        <input type="hidden" name="review_action" value="reject">
        <button type="submit" class="btn btn-outline dark btn-sm">Reject</button>
      </form>
      <form method="post" action="<?= url('/admin/reviews.php') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
        <input type="hidden" name="review_action" value="delete">
        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this review permanently?">Delete</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php echo render_pagination($page, $pages, '/admin/reviews.php?filter=' . urlencode($filter)); ?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M9 9l2 2 4-4"/></svg>
  <h3>No reviews here</h3>
  <p>Reviews from customers will appear here for moderation.</p>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
