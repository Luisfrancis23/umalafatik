<?php
/**
 * Admin — add / edit menu item
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

$itemId = int_in($_GET['id'] ?? 0, 1, PHP_INT_MAX);
$item = null;

if ($itemId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
        set_flash('error', 'Dish not found.');
        redirect('/admin/menu-items.php');
    }
}

$errors = [];
$form = [
    'category_id'  => $item ? (int) $item['category_id'] : 0,
    'name'         => $item ? (string) $item['name'] : '',
    'description'  => $item ? (string) $item['description'] : '',
    'price'        => $item ? (string) $item['price'] : '',
    'image'        => $item ? (string) $item['image'] : '',
    'is_featured'  => $item ? (int) $item['is_featured'] : 0,
    'is_available' => $item ? (int) $item['is_available'] : 1,
    'sort_order'   => $item ? (int) $item['sort_order'] : 0,
];
$currentImage = $form['image'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired.');
        redirect('/admin/menu-item.php' . ($itemId ? '?id=' . $itemId : ''));
    }

    $form['category_id']  = int_in($_POST['category_id'] ?? 0, 1, PHP_INT_MAX);
    $form['name']         = str_in($_POST['name'] ?? '', 120);
    $form['description']  = str_in($_POST['description'] ?? '', 1000);
    $form['price']        = money_in($_POST['price'] ?? 0);
    $form['is_featured']  = isset($_POST['is_featured']) ? 1 : 0;
    $form['is_available'] = isset($_POST['is_available']) ? 1 : 0;
    $form['sort_order']   = int_in($_POST['sort_order'] ?? 0, 0, 10000);

    if ($form['category_id'] < 1) $errors[] = 'Please choose a category.';
    if (strlen($form['name']) < 2) $errors[] = 'Please enter a dish name.';
    if (strlen($form['description']) < 10) $errors[] = 'Please describe the dish (min 10 characters).';
    if ($form['price'] <= 0) $errors[] = 'Please enter a valid price.';

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        try {
            $newPath = upload_image($_FILES['image'], 'menu');
            delete_upload($currentImage);
            $form['image'] = $newPath;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        if ($itemId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE menu_items SET category_id = ?, name = ?, slug = ?, description = ?, price = ?,
                 image = ?, is_featured = ?, is_available = ?, sort_order = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([
                $form['category_id'], $form['name'], slugify($form['name']), $form['description'],
                $form['price'], $form['image'], $form['is_featured'], $form['is_available'],
                $form['sort_order'], $itemId,
            ]);
            set_flash('success', 'Dish updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO menu_items (category_id, name, slug, description, price, image, is_featured, is_available, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $form['category_id'], $form['name'], slugify($form['name']), $form['description'],
                $form['price'], $form['image'], $form['is_featured'], $form['is_available'],
                $form['sort_order'],
            ]);
            set_flash('success', 'Dish added to the menu.');
        }
        redirect('/admin/menu-items.php');
    }
}

$cats = $pdo->query('SELECT * FROM categories WHERE status = "active" ORDER BY sort_order ASC')->fetchAll();

$page_title = $itemId > 0 ? 'Edit Dish' : 'Add Dish';
$nav_active = 'menu';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div style="max-width:820px">
  <div class="panel">
    <div class="panel-head">
      <h3><?= $itemId > 0 ? 'Edit Dish' : 'Add a New Dish' ?></h3>
      <a href="<?= url('/admin/menu-items.php') ?>" class="btn btn-outline dark btn-sm">← Back to Menu</a>
    </div>
    <div class="panel-body">
      <?php if ($errors): ?>
      <div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= url('/admin/menu-item.php' . ($itemId ? '?id=' . $itemId : '')) ?>" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <div class="form-grid">
          <div class="field full">
            <label for="name">Dish name</label>
            <input id="name" name="name" type="text" value="<?= e($form['name']) ?>" required maxlength="120">
          </div>
          <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
              <option value="">Select category…</option>
              <?php foreach ($cats as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= $form['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="price">Price (<?= e(CURRENCY_SYMBOL) ?>)</label>
            <input id="price" name="price" type="number" step="0.01" min="0.01" value="<?= e($form['price']) ?>" required>
          </div>
          <div class="field full">
            <label for="description">Description</label>
            <textarea id="description" name="description" required minlength="10" maxlength="1000"><?= e($form['description']) ?></textarea>
          </div>

          <div class="field full">
            <label for="itemImage">Dish photo</label>
            <div style="display:flex; gap:1.4rem; align-items:flex-start; flex-wrap:wrap">
              <div>
                <input id="itemImage" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" aria-describedby="imageHelp">
                <span class="hint" id="imageHelp">JPG, PNG, GIF or WebP — max 5 MB. Leave empty to keep the current photo.</span>
              </div>
              <div style="text-align:center">
                <img id="imagePreview" data-placeholder="<?= e(image_url($currentImage)) ?>"
                     src="<?= e(image_url($currentImage)) ?>"
                     alt="Current dish photo"
                     style="width:130px; height:130px; object-fit:cover; border-radius:14px; border:1px solid var(--cream-200); background:var(--cream-100)">
              </div>
            </div>
          </div>

          <div class="field">
            <label for="sort_order">Sort order</label>
            <input id="sort_order" name="sort_order" type="number" min="0" max="10000" value="<?= (int) $form['sort_order'] ?>">
            <span class="hint">Lower numbers appear first.</span>
          </div>
          <div class="field">
            <label>Options</label>
            <div style="display:grid; gap:0.7rem; padding-top:0.5rem">
              <label class="switch">
                <input type="checkbox" name="is_available" <?= $form['is_available'] ? 'checked' : '' ?>>
                <span class="track"></span>
                Available for ordering
              </label>
              <label class="switch">
                <input type="checkbox" name="is_featured" <?= $form['is_featured'] ? 'checked' : '' ?>>
                <span class="track"></span>
                Feature on homepage (Chef's Choice)
              </label>
            </div>
          </div>
        </div>

        <div class="form-actions" style="margin-top:1.6rem">
          <button type="submit" class="btn btn-terra"><?= $itemId > 0 ? 'Save Changes' : 'Add Dish' ?></button>
          <a href="<?= url('/admin/menu-items.php') ?>" class="btn btn-ghost dark">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
