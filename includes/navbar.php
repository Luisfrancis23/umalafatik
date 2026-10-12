<?php
/** Public navigation bar. */
$user = is_logged_in() ? current_customer() : null;
$cartQty = 0;
try {
    $cartQty = cart_count(getDB());
} catch (Throwable $e) {
    $cartQty = 0;
}
$active = $active_page ?? '';
?>
<header class="site-header" id="siteHeader">
  <div class="container nav-wrap">
    <a href="<?= url('/') ?>" class="brand" aria-label="Uma Lafatik — home">
      <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
        <rect x="2" y="2" width="44" height="44" rx="12" fill="none" stroke="currentColor" stroke-width="2.5"/>
        <path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="currentColor" transform="translate(3.5 0) scale(0.86)"/>
        <path d="M30 12v24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <span class="brand-name">Uma <em>Lafatik</em></span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navLinks">
      <span></span><span></span><span></span>
    </button>

    <nav class="nav" id="navLinks" aria-label="Primary">
      <a href="<?= url('/') ?>" class="<?= $active === 'home' ? 'active' : '' ?>">Home</a>
      <a href="<?= url('/menu.php') ?>" class="<?= $active === 'menu' ? 'active' : '' ?>">Menu</a>
      <a href="<?= url('/about.php') ?>" class="<?= $active === 'about' ? 'active' : '' ?>">Our Story</a>
      <a href="<?= url('/gallery.php') ?>" class="<?= $active === 'gallery' ? 'active' : '' ?>">Gallery</a>
      <a href="<?= url('/reviews.php') ?>" class="<?= $active === 'reviews' ? 'active' : '' ?>">Reviews</a>
      <a href="<?= url('/contact.php') ?>" class="<?= $active === 'contact' ? 'active' : '' ?>">Contact</a>

      <div class="nav-actions">
        <?php if ($user): ?>
          <a href="<?= url('/customer/track.php') ?>" class="nav-cta nav-track" title="Track your order">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Track</span>
          </a>
          <a href="<?= url('/customer/cart.php') ?>" class="nav-cta nav-cart" title="View cart">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.7 8.5a2 2 0 0 1-2 1.5H8.6a2 2 0 0 1-2-1.6L4.4 3.8A1 1 0 0 0 3.4 3H1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.6" fill="currentColor"/><circle cx="17" cy="20" r="1.6" fill="currentColor"/></svg>
            <span>Cart</span>
            <?php if ($cartQty > 0): ?><span class="cart-count" aria-label="<?= (int) $cartQty ?> items in cart"><?= (int) $cartQty ?></span><?php endif; ?>
          </a>
          <a href="<?= url('/customer/') ?>" class="nav-cta nav-account" title="My account">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 21c1.5-4 5-5.5 8-5.5s6.5 1.5 8 5.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <span><?= e($user['first_name']) ?></span>
          </a>
          <a href="<?= url('/customer/logout.php') ?>" class="nav-cta nav-logout" title="Sign out">Sign out</a>
        <?php else: ?>
          <a href="<?= url('/customer/login.php') ?>" class="nav-cta nav-login">Sign in</a>
          <a href="<?= url('/customer/register.php') ?>" class="nav-cta nav-register">Register</a>
        <?php endif; ?>
        <a href="<?= url('/reservation.php') ?>" class="btn btn-gold btn-sm nav-reserve">Reserve a Table</a>
      </div>
    </nav>
  </div>
</header>
