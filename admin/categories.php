<?php
/**
 * Admin — categories management (add, edit, delete)
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

$errors = [];
$editing = null;
$form = ['name' => '', 'description' => '', 'sort_order' => 0, 'status' => 'active'];

// Add / edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/categories.php');
    }

    $catId = int_in($_POST['category_id'] ?? 0, 0, PHP_INT_MAX);
    $form['name']        = str_in($_POST['name'] ?? '', 80);
    $form['description'] = str_in($_POST['description'] ?? '', 255);
    $form['sort_order']  = int_in($_POST['sort_order'] ?? 0, 0, 10000);
    $form['status']      = in_array($_POST['status'] ?? '', ['active', 'hidden'], true) ? $_POST['status'] : 'active';

    if (strlen($form['name']) < 2) {
        $errors[] = 'Please enter a category name.';
    }

    if (!$errors) {
        $slug = slugify($form['name']);
        if ($catId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ?, status = ? WHERE id = ?'
            );
            $stmt->execute([$form['name'], $slug, $form['description'], $form['sort_order'], $form['status'], $catId]);
            set_flash('success', 'Category updated.');
        } else {
            // Unique slug check
            $chk = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = ?');
            $chk->execute([$slug]);
            if ((int) $chk->fetchColumn() > 0) {
                $slug = $slug . '-' . strtolower(bin2hex(random_bytes(3)));
            }
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name, slug, description, sort_order, status) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$form['name'], $slug, $form['description'], $form['sort_order'], $form['status']]);
            set_flash('success', 'Category created.');
        }
        redirect('/admin/categories.php');
    }
}

// Load for editing
if (isset($_GET['edit'])) {
    $editId = int_in($_GET['edit'], 1, PHP_INT_MAX);
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
    if ($editing) {
        $form = [
            'name'        => (string) $editing['name'],
            'description' => (string) $editing['description'],
            'sort_order'  => (int) $editing['sort_order'],
            'status'      => (string) $editing['status'],
        ];
    }
}

// Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $catId = int_in($_GET['id'] ?? 0, 1, PHP_INT_MAX);
    $token = str_in($_GET['token'] ?? '', 64);
    if (!hash_equals(csrf_token(), $token)) {
        set_flash('error', 'Invalid request.');
    } else {
        // Ensure no menu items reference it
        $chk = $pdo->prepare('SELECT COUNT(*) FROM menu_items WHERE category_id = ?');
        $chk->execute([$catId]);
        if ((int) $chk->fetchColumn() > 0) {
            set_flash('error', 'This category still has dishes. Move or delete them first.');
        } else {
            $del = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $del->execute([$catId]);
            set_flash('success', 'Category deleted.');
        }
    }
    redirect('/admin/categories.php');
}

$cats = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM menu_items mi WHERE mi.category_id = c.id) AS dish_count
     FROM categories c ORDER BY c.sort_order ASC, c.id ASC'
)->fetchAll();

$page_title = 'Categories';
$nav_active = 'categories';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div style="display:grid; grid-template-columns:380px 1fr; gap:1.8rem; align-items:start" class="dash-cols">
  <div class="panel">
    <div class="panel-head"><h3><?= $editing ? 'Edit Category' : 'New Category' ?></h3></div>
    <div class="panel-body">
      <?php if ($errors): ?>
      <div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= url('/admin/categories.php') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="category_id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
        <div class="field" style="margin-bottom:1.2rem">
          <label for="name">Name</label>
          <input id="name" name="name" type="text" value="<?= e($form['name']) ?>" required maxlength="80">
        </div>
        <div class="field" style="margin-bottom:1.2rem">
          <label for="description">Description</label>
          <input id="description" name="description" type="text" value="<?= e($form['description']) ?>" maxlength="255">
        </div>
        <div class="field" style="margin-bottom:1.2rem">
          <label for="sort_order">Sort order</label>
          <input id="sort_order" name="sort_order" type="number" min="0" max="10000" value="<?= (int) $form['sort_order'] ?>">
        </div>
        <div class="field" style="margin-bottom:1.4rem">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="hidden" <?= $form['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-terra"><?= $editing ? 'Save Changes' : 'Create Category' ?></button>
          <?php if ($editing): ?>
          <a href="<?= url('/admin/categories.php') ?>" class="btn btn-ghost dark">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>All Categories</h3></div>
    <div class="panel-body flush">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Dishes</th><th>Sort</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($cats as $c): ?>
            <tr>
              <td class="strong"><?= e($c['name']) ?>
                <?php if (!empty($c['description'])): ?>
                <small style="display:block" class="text-muted"><?= e($c['description']) ?></small>
                <?php endif; ?>
              </td>
              <td class="num"><?= (int) $c['dish_count'] ?></td>
              <td class="num"><?= (int) $c['sort_order'] ?></td>
              <td><span class="badge <?= $c['status'] === 'active' ? 'badge-completed' : 'badge-hidden' ?>"><?= e($c['status']) ?></span></td>
              <td>
                <div class="row-actions">
                  <a href="<?= url('/admin/categories.php?edit=' . (int) $c['id']) ?>" class="btn btn-outline dark btn-sm">Edit</a>
                  <a href="<?= e(url('/admin/categories.php?action=delete&id=' . (int) $c['id'] . '&token=' . urlencode(csrf_token()))) ?>"
                     class="btn btn-danger btn-sm"
                     data-confirm="Delete category &quot;<?= e(addslashes((string) $c['name'])) ?>&quot;?">Delete</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
