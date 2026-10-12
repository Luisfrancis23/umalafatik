<?php
/**
 * Uma Lafatik — Photo gallery
 */
require_once __DIR__ . '/config/config.php';

$gallery = [
    ['file' => 'assets/images/gallery/interior.svg',       'caption' => 'The Dining Room',   'alt' => 'Warm arches and candlelight in the dining room'],
    ['file' => 'assets/images/gallery/table-setting.svg',  'caption' => 'Table Setting',     'alt' => 'Elegant table setting with a lit candle'],
    ['file' => 'assets/images/gallery/plating.svg',        'caption' => 'Chef’s Plating',    'alt' => 'A chef plating a dish with herbs and drizzle'],
    ['file' => 'assets/images/gallery/lounge.svg',         'caption' => 'The Lounge',        'alt' => 'Cozy lounge with pendant lamps and warm bokeh'],
    ['file' => 'assets/images/gallery/dessert-counter.svg','caption' => 'Dessert Counter',   'alt' => 'Dessert display with macarons and cake'],
    ['file' => 'assets/images/gallery/spices.svg',         'caption' => 'House Spices',      'alt' => 'Bowls of house spices — sumac, saffron, zaatar'],
];

$page_title = 'Gallery — ' . SITE_NAME;
$page_desc = 'A glimpse inside Uma Lafatik — the dining room, kitchen craft, house spices and table settings.';
$canonical = BASE_URL . '/gallery.php';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
$active_page = 'gallery';
?>

<section class="section" style="padding-top:9rem">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Gallery</p>
      <h2>A glimpse inside Uma Lafatik</h2>
      <p>Click any photograph to view it in full. The warmth is best experienced in person.</p>
    </div>

    <div class="gallery-grid">
      <?php foreach ($gallery as $i => $g): ?>
      <div class="gallery-item reveal" data-delay="<?= min($i % 3, 2) ?>" data-caption="<?= e($g['caption']) ?>">
        <img src="<?= e(BASE_PATH . '/' . $g['file']) ?>" alt="<?= e($g['alt']) ?>" loading="lazy">
      </div>
      <?php endforeach; ?>
    </div>

    <div class="cta-band reveal" style="margin-top:4.5rem">
      <p class="eyebrow">Experience it live</p>
      <h2>Photos can't capture the aroma</h2>
      <p>Join us for dinner — or order in and taste the difference for yourself.</p>
      <div class="hero-btns">
        <a href="<?= url('/reservation.php') ?>" class="btn btn-gold">Reserve a Table</a>
        <a href="<?= url('/customer/menu.php') ?>" class="btn btn-outline">Order Now</a>
      </div>
    </div>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
