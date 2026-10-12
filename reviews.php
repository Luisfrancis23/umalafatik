<?php
/**
 * Uma Lafatik — Customer reviews
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDB();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired. Please try again.');
        redirect('/reviews.php');
    }
    if (!is_logged_in()) {
        set_flash('error', 'Please sign in to leave a review.');
        redirect('/customer/login.php?next=' . urlencode('/reviews.php'));
    }

    $user = current_customer();
    $rating = int_in($_POST['rating'] ?? 0, 1, 5);
    $title = str_in($_POST['title'] ?? '', 160);
    $comment = str_in($_POST['comment'] ?? '', 2000);
    $menuItemId = int_in($_POST['menu_item_id'] ?? 0, 0, PHP_INT_MAX) ?: null;

    $errors = [];
    if ($rating < 1) $errors[] = 'Please choose a rating.';
    if (strlen($comment) < 10) $errors[] = 'Please write at least 10 characters.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO reviews (user_id, menu_item_id, author_name, rating, title, comment, status)
             VALUES (?, ?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([
            (int) $user['id'],
            $menuItemId,
            trim($user['first_name'] . ' ' . $user['last_name']),
            $rating,
            $title,
            $comment,
        ]);
        set_flash('success', 'Thank you! Your review is awaiting moderation and will appear once approved.');
        redirect('/reviews.php');
    } else {
        $formError = implode('<br>', $errors);
    }
}

// Approved reviews
$page = max(1, int_in($_GET['page'] ?? 1, 1, 10000));
$perPage = 9;
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE status = "approved"');
$stmt->execute();
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    'SELECT * FROM reviews WHERE status = "approved" ORDER BY created_at DESC LIMIT :lim OFFSET :off'
);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Average rating
$avg = (float) $pdo->query('SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE status = "approved"')->fetchColumn();
$avgCount = (int) $pdo->query('SELECT COUNT(*) FROM reviews WHERE status = "approved"')->fetchColumn();

// Menu items for "review a dish"
$items = $pdo->query('SELECT id, name FROM menu_items WHERE is_available = 1 ORDER BY name ASC')->fetchAll();

$page_title = 'Guest Reviews — ' . SITE_NAME;
$page_desc = 'What our guests say about Uma Lafatik — read verified reviews and share your own dining experience.';
$canonical = BASE_URL . '/reviews.php';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
$active_page = 'reviews';
?>

<section class="section" style="padding-top:9rem">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Reviews</p>
      <h2>What our guests say</h2>
      <?php if ($avgCount > 0): ?>
      <div style="display:flex; align-items:center; justify-content:center; gap:0.7rem; margin-top:1rem">
        <?= stars((int) round($avg)) ?>
        <strong style="font-family:var(--font-display); font-size:1.4rem"><?= number_format($avg, 1) ?></strong>
        <span class="text-muted">from <?= $avgCount ?> review<?= $avgCount === 1 ? '' : 's' ?></span>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($reviews): ?>
    <div class="card-grid">
      <?php foreach ($reviews as $i => $r): ?>
      <blockquote class="review-card reveal" data-delay="<?= min($i % 3, 2) ?>">
        <?= stars((int) $r['rating']) ?>
        <?php if (!empty($r['title'])): ?><h3><?= e($r['title']) ?></h3><?php endif; ?>
        <p>“<?= e($r['comment']) ?>”</p>
        <div class="review-meta">
          <span class="review-avatar" aria-hidden="true"><?= e(mb_substr((string) $r['author_name'], 0, 1)) ?></span>
          <div>
            <strong><?= e($r['author_name']) ?></strong>
            <small><?= e(format_date($r['created_at'])) ?></small>
          </div>
        </div>
      </blockquote>
      <?php endforeach; ?>
    </div>
    <?php
    echo render_pagination($page, $pages, '/reviews.php');
    ?>
    <?php else: ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <h3>No reviews yet</h3>
      <p>Be the first to share your experience.</p>
    </div>
    <?php endif; ?>

    <!-- Leave a review -->
    <div class="section-head reveal" style="margin-top:4.5rem">
      <p class="eyebrow">Share</p>
      <h2>Leave a review</h2>
    </div>

    <?php if (is_logged_in()): ?>
    <div class="form-card reveal" style="max-width:760px; margin-inline:auto">
      <?php if (!empty($formError)): ?>
      <div class="flash flash-error" role="alert"><?= $formError ?></div>
      <?php endif; ?>
      <form method="post" action="<?= url('/reviews.php') ?>">
        <?= csrf_input() ?>
        <div class="form-grid">
          <div class="field">
            <label for="rating">Your rating</label>
            <select id="rating" name="rating" required>
              <option value="">Select…</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
              <option value="<?= $i ?>"><?= $i ?> — <?= ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][$i] ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="field">
            <label for="menu_item_id">Dish (optional)</label>
            <select id="menu_item_id" name="menu_item_id">
              <option value="">Overall experience</option>
              <?php foreach ($items as $item): ?>
              <option value="<?= (int) $item['id'] ?>"><?= e($item['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field full">
            <label for="title">Title</label>
            <input id="title" name="title" type="text" maxlength="160" placeholder="e.g. An unforgettable evening" required>
          </div>
          <div class="field full">
            <label for="comment">Your review</label>
            <textarea id="comment" name="comment" placeholder="Tell us about your visit — the food, the ambience, the service…" required minlength="10"></textarea>
            <span class="hint">Minimum 10 characters. Reviews are moderated before publishing.</span>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-terra">Submit Review</button>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="form-card reveal" style="max-width:560px; margin-inline:auto; text-align:center">
      <h3 style="margin-bottom:0.6rem">Sign in to leave a review</h3>
      <p class="text-muted" style="margin-bottom:1.6rem">Create a free account to share your experience and earn member perks.</p>
      <div class="section-actions" style="justify-content:center">
        <a href="<?= url('/customer/login.php?next=' . urlencode('/reviews.php')) ?>" class="btn btn-terra">Sign In</a>
        <a href="<?= url('/customer/register.php') ?>" class="btn btn-outline dark">Create Account</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
