<?php
/**
 * Uma Lafatik — Our Story & Philosophy
 */
require_once __DIR__ . '/config/config.php';

$page_title = 'Our Story — ' . SITE_NAME;
$page_desc = 'The story and philosophy of Uma Lafatik: honest ingredients, time-honoured recipes and genuine warmth since day one.';
$canonical = BASE_URL . '/about.php';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
$active_page = 'about';
?>

<section class="section" style="padding-top:9rem">
  <div class="container narrow" style="text-align:center">
    <p class="eyebrow reveal">Our Story</p>
    <h1 class="reveal" data-delay="1">From our family <em style="color:var(--gold);font-style:italic">to your table</em></h1>
    <p class="reveal" data-delay="2" style="color:var(--muted); font-size:1.15rem; margin-top:1.4rem; max-width:44rem; margin-inline:auto">
      Uma Lafatik began as a family dream: to share the flavours of home with the
      elegance of a fine dining table.
    </p>
  </div>
</section>

<section class="section alt">
  <div class="container two-col">
    <div class="reveal">
      <img src="<?= asset('images/gallery/interior.svg') ?>" alt="The Uma Lafatik dining room" loading="lazy" style="border-radius:22px; box-shadow:var(--shadow-lg)">
    </div>
    <div class="reveal" data-delay="1">
      <p class="eyebrow">The Beginning</p>
      <h2>A dream passed down</h2>
      <p style="color:var(--muted); margin:1.2rem 0">
        Three generations ago, our grandmother began cooking for anyone who wandered
        past her door — travellers, neighbours, strangers. Her kitchen had one rule:
        nobody leaves hungry, and nobody leaves unchanged.
      </p>
      <p style="color:var(--muted); margin-bottom:1.6rem">
        Uma Lafatik carries that same spirit. We named the restaurant for her —
        <em>Uma</em>, the evening breeze, and <em>Lafatik</em>, the gentle art of
        gathering. Every recipe on our menu was cooked first in her kitchen,
        measured by memory and perfected with patience.
      </p>
      <blockquote style="border-left:3px solid var(--gold); padding-left:1.2rem; font-family:var(--font-display); font-size:1.35rem; font-style:italic; color:var(--char-700)">
        “Great food is love you can taste.”
      </blockquote>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Philosophy</p>
      <h2>What we believe</h2>
    </div>
    <div class="story-grid">
      <div class="value-card reveal">
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8 6 6 8.5 6 12a6 6 0 0 0 12 0c0-3.5-2-6-6-10z"/><path d="M12 22c-2 0-3.5-1.5-3.5-3.5S10 15 12 12c2 3 3.5 4.5 3.5 6.5S14 22 12 22z"/></svg>
        </div>
        <h3>Honest Ingredients</h3>
        <p>Saffron threads, smoked cardamom, cold-pressed olive oil and produce sourced at dawn from farms we know by name. If it isn't good enough for our table, it isn't good enough for yours.</p>
      </div>
      <div class="value-card reveal" data-delay="1">
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
        </div>
        <h3>Time-Honoured Recipes</h3>
        <p>Slow rice simmered for hours, dough rested overnight, spice blends ground fresh each morning. Patience is our secret ingredient — we never rush a dish.</p>
      </div>
      <div class="value-card reveal" data-delay="2">
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
        </div>
        <h3>Genuine Warmth</h3>
        <p>Fine dining is not about stiffness — it is about making you feel known. From your first greeting to your last bite, we treat every guest like family.</p>
      </div>
    </div>
  </div>
</section>

<section class="section alt">
  <div class="container two-col reverse">
    <div class="reveal">
      <p class="eyebrow">The Craft</p>
      <h2>In the kitchen</h2>
      <p style="color:var(--muted); margin:1.2rem 0">
        Our chefs train for years before they touch a guest plate. They learn the
        rhythm of the tandoor, the patience of the biryani, and the quiet confidence
        of a perfect char.
      </p>
      <p style="color:var(--muted); margin-bottom:2rem">
        We cook in small batches, season by season. When the saffron runs out, we
        wait for the next harvest rather than settle for less. That is the Lafatik way.
      </p>
      <div class="section-actions">
        <a href="<?= url('/menu.php') ?>" class="btn btn-terra">Taste the Craft</a>
        <a href="<?= url('/gallery.php') ?>" class="btn btn-outline dark">See the Kitchen</a>
      </div>
    </div>
    <div class="reveal" data-delay="1">
      <img src="<?= asset('images/gallery/spices.svg') ?>" alt="House spices — saffron, cardamom and zaatar" loading="lazy" style="border-radius:22px; box-shadow:var(--shadow-lg)">
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band reveal">
      <p class="eyebrow">Come say hello</p>
      <h2>The table is <em>waiting</em></h2>
      <p>Reserve a table for tonight, or bring Uma Lafatik home with online ordering and delivery.</p>
      <div class="hero-btns">
        <a href="<?= url('/reservation.php') ?>" class="btn btn-gold">Reserve a Table</a>
        <a href="<?= url('/customer/menu.php') ?>" class="btn btn-outline">Order Now</a>
      </div>
    </div>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
