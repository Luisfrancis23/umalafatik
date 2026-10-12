<?php
/**
 * Admin — menu items list (toggle availability, delete, edit)
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

// Actions via GET (with CSRF stored in session and passed as token)
if (isset($_GET['action'])) {
    $action = str_in($_GET['action'], 20);
    $id = int_in($_GET['id'] ?? 0, 1, PHP_INT_MAX);
    $token = str_in($_GET['token'] ?? '', 64);

    if (!hash_equals(csrf_token(), $token)) {
        set_flash('error', 'Invalid request.');
        redirect('/admin/menu-items.php');
    }

    if ($action === 'toggle' && $id > 0) {
        $stmt = $pdo->prepare('SELECT name, is_available FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if ($item) {
            $newVal = (int) $item['is_available'] === 1 ? 0 : 1;
            $upd = $pdo->prepare('UPDATE menu_items SET is_available = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$newVal, $id]);
            set_flash('success', '"' . $item['name'] . '" is now ' . ($newVal ? 'available' : 'unavailable') . '.');
        }
        redirect('/admin/menu-items.php');
    }

    if ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare('SELECT name, image FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if ($item) {
            try {
                $del = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
                $del->execute([$id]);
                delete_upload($item['image']);
                set_flash('success', '"' . $item['name'] . '" deleted.');
            } catch (PDOException $e) {
                set_flash('error', 'Cannot delete — this dish is referenced by existing orders.');
            }
        }
        redirect('/admin/menu-items.php');
    }
}

// Filters
$catFilter = int_in($_GET['cat'] ?? 0, 0, PHP_INT_MAX);
$q = str_in($_GET['q'] ?? '', 100);
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

$where = ['1 = 1'];
$params = [];
if ($catFilter > 0) {
    $where[] = 'mi.category_id = :cat';
    $params[':cat'] = $catFilter;
}
if ($q !== '') {
    $where[] = '(mi.name LIKE :q OR mi.description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM menu_items mi ' . $whereSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 12;
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT mi.*, c.name AS category_name
     FROM menu_items mi
     LEFT JOIN categories c ON c.id = mi.category_id ' . $whereSql . '
     ORDER BY mi.sort_order ASC, mi.id DESC
     LIMIT :lim OFFSET :off'
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$cats = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC')->fetchAll();

$page_title = 'Menu Items';
$nav_active = 'menu';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<form class="toolbar" method="get" action="<?= url('/admin/menu-items.php') ?>" role="search" aria-label="Search menu items">
  <div class="search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search dishes…" aria-label="Search dishes">
  </div>
  <div class="filter-pills" role="group" aria-label="Filter by category">
    <a class="pill <?= $catFilter === 0 ? 'active' : '' ?>" href="<?= e(url('/admin/menu-items.php?q=' . urlencode($q))) ?>">All</a>
    <?php foreach ($cats as $c): ?>
    <a class="pill <?= $catFilter === (int) $c['id'] ? 'active' : '' ?>"
       href="<?= e(url('/admin/menu-items.php?cat=' . urlencode($c['id']) . '&q=' . urlencode($q))) ?>">
      <?= e($c['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <a href="<?= url('/admin/menu-item.php') ?>" class="btn btn-terra btn-sm">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    Add Dish
  </a>
  <input type="hidden" name="q" value="<?= e($q) ?>">
</form>

<?php if ($items): ?>
<div class="panel">
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Dish</th><th>Category</th><th class="num">Price</th><th>Featured</th><th>Availability</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td>
              <div class="cell-dish">
                <img class="thumb" src="<?= e(image_url($it['image'])) ?>" alt="" loading="lazy">
                <div>
                  <strong><?= e($it['name']) ?></strong>
                  <small class="text-muted"><?= e(mb_strimwidth((string) $it['description'], 0, 60, '…')) ?></small>
                </div>
              </div>
            </td>
            <td><span class="chip terra"><?= e($it['category_name'] ?? '—') ?></span></td>
            <td class="num strong"><?= format_price($it['price']) ?></td>
            <td><?= (int) $it['is_featured'] ? '<span class="chip gold">Chef\'s Choice</span>' : '<span class="text-muted">—</span>' ?></td>
            <td>
              <a href="<?= e(url('/admin/menu-items.php?action=toggle&id=' . (int) $it['id'] . '&token=' . urlencode(csrf_token()) . '&' . http_build_query(array_filter(['cat' => $catFilter ?: null, 'q' => $q ?: null])))) ?>"
                 class="badge <?= (int) $it['is_available'] ? 'badge-completed' : 'badge-cancelled' ?>"
                 title="Click to toggle availability">
                <?= (int) $it['is_available'] ? 'Available' : 'Unavailable' ?>
              </a>
            </td>
            <td>
              <div class="row-actions">
                <a href="<?= url('/admin/menu-item.php?id=' . (int) $it['id']) ?>" class="btn btn-outline dark btn-sm">Edit</a>
                <a href="<?= e(url('/admin/menu-items.php?action=delete&id=' . (int) $it['id'] . '&token=' . urlencode(csrf_token()) . '&' . http_build_query(['cat' => $catFilter ?: null, 'q' => $q ?: null]))) ?>"
                   class="btn btn-danger btn-sm"
                   data-confirm="Delete &quot;<?= e(addslashes((string) $it['name'])) ?>&quot;? This cannot be undone.">Delete</a>
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
echo render_pagination($page, $pages, '/admin/menu-items.php?' . http_build_query(array_filter(['cat' => $catFilter ?: null, 'q' => $q ?: null])));
?>
<?php else: ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.1 1.6 3.8 3.7 4L6.5 22h3l-.2-9C11.4 12.8 13 11.1 13 9V2h-2v7zm5-4v9h2.5v9h3V2c-3 0-5.5 2-5.5 5z"/></svg>
  <h3>No dishes found</h3>
  <p>Add your first dish to the menu.</p>
  <a href="<?= url('/admin/menu-item.php') ?>" class="btn btn-terra" style="margin-top:1.2rem">Add Dish</a>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
