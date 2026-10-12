<?php
/**
 * Public footer + shared scripts.
 * Variable: $page_scripts (array of asset paths relative to /assets/)
 */
$page_scripts = $page_scripts ?? [];
$hours = [
    ['Days' => 'Monday – Thursday', 'Time' => '12:00 – 23:00'],
    ['Days' => 'Friday – Saturday', 'Time' => '12:00 – 00:00'],
    ['Days' => 'Sunday', 'Time' => '13:00 – 22:00'],
];
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-brand">
      <a href="<?= url('/') ?>" class="brand brand-light">
        <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
          <rect x="2" y="2" width="44" height="44" rx="12" fill="none" stroke="currentColor" stroke-width="2.5"/>
          <path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="currentColor" transform="translate(3.5 0) scale(0.86)"/>
          <path d="M30 12v24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        <span class="brand-name">Uma <em>Lafatik</em></span>
      </a>
      <p><?= e(SITE_TAGLINE) ?>. Exceptional food, warm hospitality, and moments worth remembering — crafted with love since day one.</p>
      <div class="social-links">
        <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.2" cy="6.8" r="1.3" fill="currentColor"/></svg></a>
        <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M14 8h3V4.5h-3c-2.5 0-4 1.8-4 4.3V11H7v3.5h3V21h3.5v-6.5h3l.5-3.5h-3.5V8.7c0-.5.3-.7 1-.7z" fill="currentColor"/></svg></a>
        <a href="#" aria-label="X (Twitter)"><svg viewBox="0 0 24 24"><path d="M4 4l7.2 9.3L4.4 20h2.5l5.3-5.4L16.8 20H20l-7.5-9.7L18.9 4h-2.5l-4.7 4.9L8.2 4H4z" fill="currentColor"/></svg></a>
        <a href="#" aria-label="TikTok"><svg viewBox="0 0 24 24"><path d="M16.5 3c.4 2.2 1.8 3.7 4.1 3.9v3c-1.6 0-3-.5-4.1-1.3v6.6c0 3.4-2.5 5.8-5.7 5.8A5.4 5.4 0 0 1 5.4 15c0-3.2 2.7-5.5 6-5.3v3.1c-1.6-.3-3 .7-3 2.2 0 1.5 1.2 2.7 2.8 2.7 1.8 0 3.2-1.4 3.2-3.4V3h3.1z" fill="currentColor"/></svg></a>
      </div>
    </div>

    <div class="footer-col">
      <h4>Explore</h4>
      <ul class="footer-links">
        <li><a href="<?= url('/menu.php') ?>">View Menu</a></li>
        <li><a href="<?= url('/reservation.php') ?>">Reserve a Table</a></li>
        <li><a href="<?= url('/about.php') ?>">Our Story</a></li>
        <li><a href="<?= url('/gallery.php') ?>">Gallery</a></li>
        <li><a href="<?= url('/reviews.php') ?>">Guest Reviews</a></li>
        <li><a href="<?= url('/customer/track.php') ?>">Track an Order</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Contact</h4>
      <address>
        <p><?= e(SITE_ADDRESS) ?></p>
        <p><a href="tel:+966500000000"><?= e(SITE_PHONE) ?></a></p>
        <p><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></p>
      </address>
      <a href="<?= url('/contact.php') ?>" class="btn btn-outline btn-sm">Contact Us</a>
    </div>

    <div class="footer-col">
      <h4>Opening Hours</h4>
      <table class="footer-hours">
        <?php foreach ($hours as $row): ?>
        <tr><td><?= e($row['Days']) ?></td><td><?= e($row['Time']) ?></td></tr>
        <?php endforeach; ?>
      </table>
      <a href="<?= url('/reservation.php') ?>" class="btn btn-gold btn-sm">Book Now</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
      <p class="footer-legal"><a href="<?= url('/contact.php') ?>">Privacy</a> &middot; <a href="<?= url('/contact.php') ?>">Terms</a></p>
    </div>
  </div>
</footer>

<button class="to-top" id="toTop" aria-label="Back to top" title="Back to top">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5m0 0l-6 6m6-6l6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>

<script src="<?= asset('js/main.js') . '?v=1' ?>"></script>
<?php foreach ($page_scripts as $script): ?>
<script src="<?= e(asset('js/' . ltrim($script, '/'))) . '?v=1' ?>"></script>
<?php endforeach; ?>
</body>
</html>
