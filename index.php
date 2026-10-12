<?php
/**
 * Uma Lafatik — Homepage
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDB();

// Featured dishes
$featured = [];
$stmt = $pdo->prepare(
    'SELECT mi.*, c.name AS category_name
     FROM menu_items mi
     LEFT JOIN categories c ON c.id = mi.category_id
     WHERE mi.is_featured = 1 AND mi.is_available = 1
     ORDER BY mi.sort_order ASC, mi.id ASC LIMIT 8'
);
$stmt->execute();
$featured = $stmt->fetchAll();

// Approved reviews
$reviews = [];
$stmt = $pdo->prepare(
    'SELECT * FROM reviews WHERE status = "approved" ORDER BY created_at DESC LIMIT 3'
);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Gallery preview
$galleryPreview = [
    ['file' => 'assets/images/gallery/interior.svg', 'caption' => 'The Dining Room'],
    ['file' => 'assets/images/gallery/plating.svg', 'caption' => 'Chef’s Plating'],
    ['file' => 'assets/images/gallery/spices.svg', 'caption' => 'House Spices'],
    ['file' => 'assets/images/gallery/table-setting.svg', 'caption' => 'Table Setting'],
    ['file' => 'assets/images/gallery/dessert-counter.svg', 'caption' => 'Dessert Counter'],
    ['file' => 'assets/images/gallery/lounge.svg', 'caption' => 'The Lounge'],
];

$page_title = SITE_NAME . ' — ' . SITE_TAGLINE;
$page_desc = 'Uma Lafatik — exceptional food, warm hospitality and a memorable dining experience. Browse the menu, reserve a table or order online for delivery and pickup.';
$page_schema = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => SITE_NAME,
    'servesCuisine' => ['Middle Eastern', 'Mediterranean', 'Fine Dining'],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => SITE_ADDRESS],
    'telephone' => SITE_PHONE,
    'email' => SITE_EMAIL,
    'priceRange' => '$$',
    'slogan' => SITE_TAGLINE,
]);

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';

$active_page = 'home';
?>

<!-- ============================ HERO ============================ -->
<section class="hero" id="home">
  <div class="container hero-grid">
    <div>
      <p class="eyebrow">Est. with love · Premium Dining</p>
      <h1>A Taste Worth <em>Remembering</em></h1>
      <p class="hero-sub">Exceptional food, warm hospitality, and moments you will cherish — crafted with love at Uma Lafatik.</p>
      <div class="hero-btns">
        <a href="<?= url('/reservation.php') ?>" class="btn btn-gold">Reserve a Table</a>
        <a href="<?= url('/menu.php') ?>" class="btn btn-outline">View Menu</a>
      </div>
      <div class="hero-btns" style="margin-top:1rem">
        <a href="<?= url('/customer/menu.php') ?>" class="btn btn-terra">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.1 1.6 3.8 3.7 4L6.5 22h3l-.2-9C11.4 12.8 13 11.1 13 9V2h-2v7zm5-4v9h2.5v9h3V2c-3 0-5.5 2-5.5 5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Order Now
        </a>
        <a href="<?= url('/about.php') ?>" class="btn btn-outline">Discover Our Story</a>
      </div>
      <div class="hero-meta">
        <div><strong>18+</strong><span>Signature Dishes</span></div>
        <div><strong>4.9</strong><span>Guest Rating</span></div>
        <div><strong>12k+</strong><span>Happy Guests</span></div>
      </div>
    </div>
    <div class="hero-art">
      <img src="<?= asset('images/hero-plate.svg') ?>" alt="Uma Lafatik signature dish — Royal Kabsa with saffron rice" width="600" height="760">
      <div class="hero-badge">
        <?= stars(5) ?>
        <div>
          <strong>Royal Kabsa</strong>
          <small>Guest favourite · $24.00</small>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================ WELCOME ============================ -->
<section class="section" id="about">
  <div class="container two-col">
    <div class="intro-img reveal">
      <img src="<?= asset('images/gallery/interior.svg') ?>" alt="The warm, candlelit dining room at Uma Lafatik" loading="lazy">
    </div>
    <div class="reveal" data-delay="1">
      <p class="eyebrow">Welcome to Uma Lafatik</p>
      <h2>Where every plate tells a story</h2>
      <p style="color:var(--muted); margin:1.2rem 0 1.6rem">
        Uma Lafatik is more than a restaurant — it is a gathering place where every dish carries a
        family recipe and every guest leaves as family. From our candlelit tables to our fragrant
        kitchen, we craft each evening with care, flavour and heart.
      </p>
      <p style="color:var(--muted); margin-bottom:2rem">
        Step into a world of warm terracotta walls, soft gold light and aromas of saffron and
        smoked cardamom. Whether you are celebrating a milestone or simply savouring a quiet
        evening, we are honoured to host you.
      </p>
      <div class="section-actions">
        <a href="<?= url('/about.php') ?>" class="btn btn-terra">Discover Our Story</a>
        <a href="<?= url('/menu.php') ?>" class="btn btn-outline dark">View Menu</a>
      </div>
    </div>
  </div>
</section>

<!-- ============================ FEATURED DISHES ============================ -->
<section class="section alt" id="dishes">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Signature Selection</p>
      <h2>Featured Dishes</h2>
      <p>Hand-crafted by our chefs with ingredients sourced at dawn and recipes passed down through generations.</p>
    </div>
    <div class="card-grid">
      <?php foreach ($featured as $i => $dish): ?>
      <article class="dish-card reveal" data-delay="<?= min($i % 3, 2) ?>">
        <div class="dish-img">
          <img src="<?= e(image_url($dish['image'])) ?>" alt="<?= e($dish['name']) ?>" loading="lazy">
        </div>
        <div class="dish-body">
          <div class="dish-top">
            <h3><?= e($dish['name']) ?></h3>
            <span class="price"><?= format_price($dish['price']) ?></span>
          </div>
          <?php if (!empty($dish['category_name'])): ?>
          <span class="chip terra"><?= e($dish['category_name']) ?></span>
          <?php endif; ?>
          <p><?= e(mb_strimwidth((string) $dish['description'], 0, 110, '…')) ?></p>
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
            <a href="<?= url('/customer/menu.php') ?>" class="text-muted" style="font-size:0.88rem">Order Now →</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="center" style="margin-top:2.6rem">
      <a href="<?= url('/menu.php') ?>" class="btn btn-outline dark">View Full Menu</a>
    </div>
  </div>
</section>

<!-- ============================ STORY TEASER ============================ -->
<section class="section" id="story">
  <div class="container two-col reverse">
    <div class="reveal">
      <p class="eyebrow">Our Philosophy</p>
      <h2>Our Story</h2>
      <p style="color:var(--muted); margin:1.2rem 0">
        Uma Lafatik began as a family dream: to share the flavours of home with the elegance of a
        fine dining table. We believe great food is built on honest ingredients, time-honoured
        recipes and genuine warmth.
      </p>
      <p style="color:var(--muted); margin-bottom:2rem">
        Every plate leaves our kitchen with the same care we would give a dish for our own family —
        because here, you are family. Our philosophy is simple: source the best, cook with patience,
        and serve with love.
      </p>
      <a href="<?= url('/about.php') ?>" class="btn btn-terra">Discover Our Story</a>
    </div>
    <div class="intro-img reveal" data-delay="1">
      <img src="<?= asset('images/gallery/plating.svg') ?>" alt="Chef carefully plating a signature dish" loading="lazy">
    </div>
  </div>

  <div class="container">
    <div class="story-grid">
      <div class="value-card reveal">
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8 6 6 8.5 6 12a6 6 0 0 0 12 0c0-3.5-2-6-6-10z"/><path d="M12 22c-2 0-3.5-1.5-3.5-3.5S10 15 12 12c2 3 3.5 4.5 3.5 6.5S14 22 12 22z"/></svg>
        </div>
        <h3>Honest Ingredients</h3>
        <p>Saffron, smoked cardamom and olive oil from trusted farms — nothing shortcuts, ever.</p>
      </div>
      <div class="value-card reveal" data-delay="1">
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
        </div>
        <h3>Time-Honoured Recipes</h3>
        <p>Slow-cooked, hand-stirred, patient. Recipes our grandmothers would proudly recognise.</p>
      </div>
      <div class="value-card reveal" data-delay="2">
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
        </div>
        <h3>Genuine Warmth</h3>
        <p>From the first greeting to the last bite, we treat every guest like family.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================ GALLERY TEASER ============================ -->
<section class="section alt" id="gallery">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Moments</p>
      <h2>A Glimpse Inside</h2>
    </div>
    <div class="gallery-grid">
      <?php foreach ($galleryPreview as $g): ?>
      <div class="gallery-item" data-caption="<?= e($g['caption']) ?>">
        <img src="<?= e(BASE_PATH . '/' . $g['file']) ?>" alt="<?= e($g['caption']) ?> at Uma Lafatik" loading="lazy">
      </div>
      <?php endforeach; ?>
    </div>
    <div class="center" style="margin-top:2.4rem">
      <a href="<?= url('/gallery.php') ?>" class="btn btn-outline dark">View Gallery</a>
    </div>
  </div>
</section>

<!-- ============================ REVIEWS TEASER ============================ -->
<section class="section" id="reviews">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Guest Words</p>
      <h2>What Our Guests Say</h2>
    </div>
    <div class="card-grid">
      <?php foreach ($reviews as $r): ?>
      <blockquote class="review-card reveal">
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
    <div class="center" style="margin-top:2.4rem">
      <a href="<?= url('/reviews.php') ?>" class="btn btn-outline dark">Read All Reviews</a>
    </div>
  </div>
</section>

<!-- ============================ HOURS & LOCATION ============================ -->
<section class="section alt" id="hours">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Visit Us</p>
      <h2>Opening Hours & Location</h2>
    </div>
    <div class="info-grid">
      <div class="reveal">
        <h3 style="margin-bottom:1rem">Opening Hours</h3>
        <table class="hours-table">
          <?php
          $today = (int) date('N'); // 1=Mon ... 7=Sun
          $hourRows = [
            ['Monday – Thursday', '12:00 – 23:00', [1, 2, 3, 4]],
            ['Friday – Saturday', '12:00 – 00:00', [5, 6]],
            ['Sunday', '13:00 – 22:00', [7]],
          ];
          foreach ($hourRows as $row):
            $isToday = in_array($today, $row[2], true);
          ?>
          <tr class="<?= $isToday ? 'today' : '' ?>">
            <td><?= e($row[0]) ?><?= $isToday ? ' · today' : '' ?></td>
            <td><?= e($row[1]) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <p class="text-muted" style="margin-top:1.1rem; font-size:0.92rem">
          Kitchen closes 45 minutes before the restaurant. Reservations recommended on weekends.
        </p>
        <a href="<?= url('/reservation.php') ?>" class="btn btn-gold" style="margin-top:1.6rem">Reserve a Table</a>
      </div>
      <div class="reveal" data-delay="1">
        <h3 style="margin-bottom:1rem">Find Us</h3>
        <ul class="contact-list">
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
            <div><strong>Address</strong><span><?= e(SITE_ADDRESS) ?></span></div>
          </li>
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
            <div><strong>Phone</strong><a href="tel:+966500000000"><?= e(SITE_PHONE) ?></a></div>
          </li>
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M22 6l-10 7L2 6"/></svg></span>
            <div><strong>Email</strong><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></div>
          </li>
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></span>
            <div><strong>Delivery Zone</strong><span>Within 8 km of Downtown — free over <?= format_price(setting('free_delivery_min', FREE_DELIVERY_MIN)) ?></span></div>
          </li>
        </ul>
      </div>
    </div>
    <div class="map-frame reveal">
      <iframe
        title="Map — Uma Lafatik, <?= e(SITE_ADDRESS) ?>"
        src="https://www.google.com/maps?q=<?= urlencode(SITE_ADDRESS) ?>&output=embed"
        loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
</section>

<!-- ============================ CTA BAND ============================ -->
<section class="section">
  <div class="container">
    <div class="cta-band reveal">
      <p class="eyebrow">Hungry now?</p>
      <h2>Craving something <em>special</em>?</h2>
      <p>Order online for fast delivery or pickup, track your order in real time, and savour Uma Lafatik wherever you are.</p>
      <div class="hero-btns">
        <a href="<?= url('/customer/menu.php') ?>" class="btn btn-gold">Order Now</a>
        <a href="<?= url('/customer/track.php') ?>" class="btn btn-outline">Track Order</a>
        <a href="<?= url('/reservation.php') ?>" class="btn btn-outline">Reserve a Table</a>
      </div>
    </div>
  </div>
</section>

<?php
$page_scripts = ['cart.js'];
require_once __DIR__ . '/includes/footer.php';
