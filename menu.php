<?php
/**
 * Uma Lafatik — Menu page (categories, search, filter, pagination)
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDB();

// Filters
$catSlug = str_in($_GET['cat'] ?? '', 90);
$q = str_in($_GET['q'] ?? '', 100);
$sort = str_in($_GET['sort'] ?? '', 20);
$availableOnly = isset($_GET['available']) ? (int) $_GET['available'] : 1;
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));

$where = ['mi.is_available = :avail'];
$params = [':avail' => $availableOnly ? 1 : 0];

if ($catSlug !== '') {
    $where[] = 'c.slug = :slug';
    $params[':slug'] = $catSlug;
}
if ($q !== '') {
    $where[] = '(mi.name LIKE :q OR mi.description LIKE :q OR c.name LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$orderBy = 'mi.sort_order ASC, mi.id ASC';
$sortOptions = [
    'featured' => 'mi.is_featured DESC, mi.sort_order ASC',
    'price_asc' => 'mi.price ASC',
    'price_desc' => 'mi.price DESC',
    'name' => 'mi.name ASC',
    'newest' => 'mi.created_at DESC',
];
if (isset($sortOptions[$sort])) {
    $orderBy = $sortOptions[$sort];
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// Total + pagination
$countStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM menu_items mi
     LEFT JOIN categories c ON c.id = mi.category_id ' . $whereSql
);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 12;
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT mi.*, c.name AS category_name, c.slug AS category_slug
     FROM menu_items mi
     LEFT JOIN categories c ON c.id = mi.category_id ' . $whereSql . '
     ORDER BY ' . $orderBy . '
     LIMIT :limit OFFSET :offset'
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$dishes = $stmt->fetchAll();

// Categories for pills
$cats = $pdo->query(
    'SELECT * FROM categories WHERE status = "active" ORDER BY sort_order ASC'
)->fetchAll();

$page_title = 'Menu — ' . SITE_NAME;
$page_desc = 'Browse the full Uma Lafatik menu: starters, main courses, grill, pasta, desserts and beverages. Order online for delivery or pickup.';
$canonical = BASE_URL . '/menu.php';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
$active_page = 'menu';
?>

<section class="section" style="padding-top:8rem">
  <div class="container">
    <div class="section-head reveal" style="margin-bottom:2.4rem">
      <p class="eyebrow">The Menu</p>
      <h2>Cooked with patience, served with love</h2>
      <p>Every dish is prepared to order. Filter by category, search your cravings, and add favourites to your cart.</p>
    </div>

    <!-- Toolbar -->
    <form class="toolbar reveal" method="get" action="<?= url('/menu.php') ?>" role="search" aria-label="Menu search">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search dishes, ingredients…" aria-label="Search menu">
      </div>
      <div class="filter-pills" role="group" aria-label="Category">
        <a class="pill <?= $catSlug === '' ? 'active' : '' ?>" href="<?= e(url('/menu.php?q=' . urlencode($q) . '&sort=' . urlencode($sort))) ?>">All</a>
        <?php foreach ($cats as $c): ?>
        <a class="pill <?= $catSlug === $c['slug'] ? 'active' : '' ?>"
           href="<?= e(url('/menu.php?cat=' . urlencode($c['slug']) . '&q=' . urlencode($q) . '&sort=' . urlencode($sort))) ?>">
          <?= e($c['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <select name="sort" aria-label="Sort dishes" onchange="this.form.submit()">
        <option value="featured" <?= $sort === 'featured' || $sort === '' ? 'selected' : '' ?>>Featured</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
      </select>
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <input type="hidden" name="cat" value="<?= e($catSlug) ?>">
      <input type="hidden" name="available" value="1">
    </form>

    <?php if ($dishes): ?>
    <div class="card-grid">
      <?php foreach ($dishes as $i => $dish): ?>
      <article class="dish-card reveal" data-delay="<?= min($i % 3, 2) ?>">
        <div class="dish-img">
          <img src="<?= e(image_url($dish['image'])) ?>" alt="<?= e($dish['name']) ?>" loading="lazy">
          <?php if ($dish['is_featured']): ?><span class="chip gold" style="position:absolute;top:0.8rem;left:0.8rem">Chef's Choice</span><?php endif; ?>
        </div>
        <div class="dish-body">
          <div class="dish-top">
            <h3><?= e($dish['name']) ?></h3>
            <span class="price"><?= format_price($dish['price']) ?></span>
          </div>
          <?php if (!empty($dish['category_name'])): ?>
          <span class="chip terra"><?= e($dish['category_name']) ?></span>
          <?php endif; ?>
          <p><?= e(mb_strimwidth((string) $dish['description'], 0, 120, '…')) ?></p>
          <div class="dish-foot">
            <?php if (is_logged_in()): ?>
            <button class="add-btn" data-add-id="<?= (int) $dish['id'] ?>" type="button">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
              <span>Add to Cart</span>
            </button>
            <?php else: ?>
            <a class="add-btn" href="<?= url('/customer/login.php?next=' . urlencode('/menu.php')) ?>">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
              <span>Add to Cart</span>
            </a>
            <?php endif; ?>
            <span class="text-muted" style="font-size:0.85rem">
              <?php if (!$availableOnly): ?>
                <?= $dish['is_available'] ? '<span class="badge badge-completed">Available</span>' : '<span class="badge badge-cancelled">Unavailable</span>' ?>
              <?php endif; ?>
            </span>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <?php
    $qs = http_build_query(array_filter([
      'cat' => $catSlug ?: null,
      'q' => $q ?: null,
      'sort' => $sort ?: null,
      'available' => $availableOnly,
    ], fn ($v) => $v !== null && $v !== ''));
    $base = '/menu.php' . ($qs ? '?' . $qs : '');
    echo render_pagination($page, $pages, $base);
    ?>
    <?php else: ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M8 11h6"/></svg>
      <h3>No dishes found</h3>
      <p>Try a different search term or browse another category.</p>
      <a href="<?= url('/menu.php') ?>" class="btn btn-terra" style="margin-top:1.2rem">Clear Filters</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php
$page_scripts = ['cart.js'];
require_once __DIR__ . '/includes/footer.php';
