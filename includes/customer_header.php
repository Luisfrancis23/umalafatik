<?php
/**
 * Customer dashboard shell — sidebar + topbar + content.
 * Variables: $page_title, $nav_active
 */
require_login();

$user = current_customer();
$unread = 0;
try {
    $unread = unread_notifications(getDB(), (int) $user['id']);
} catch (Throwable $e) {
    $unread = 0;
}

$nav_active = $nav_active ?? '';
$page_title = $page_title ?? 'My Account — ' . SITE_NAME;
$extra_css  = array_merge($extra_css ?? [], [url('/assets/css/dashboard.css')]);

require_once __DIR__ . '/head.php';

$nav = [
    'dashboard'     => ['label' => 'Dashboard',     'href' => '/customer/',                'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    'orders'        => ['label' => 'My Orders',     'href' => '/customer/orders.php',      'icon' => 'M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6zm3 15a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM3 6h18'],
    'track'         => ['label' => 'Track Order',   'href' => '/customer/track.php',       'icon' => 'M3 12h4l3-8 4 16 3-8h4'],
    'cart'          => ['label' => 'Cart',          'href' => '/customer/cart.php',        'icon' => 'M6 6h15l-1.7 8.5a2 2 0 0 1-2 1.5H8.6a2 2 0 0 1-2-1.6L4.4 3.8A1 1 0 0 0 3.4 3H1'],
    'reservations'  => ['label' => 'Reservations',  'href' => '/customer/reservations.php',  'icon' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z'],
    'notifications' => ['label' => 'Notifications', 'href' => '/customer/notifications.php', 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0'],
    'menu'          => ['label' => 'Order Food',    'href' => '/customer/menu.php',        'icon' => 'M11 9H9V2H7v7H5V2H3v7c0 2.1 1.6 3.8 3.7 4L6.5 22h3l-.2-9C11.4 12.8 13 11.1 13 9V2h-2v7zm5-4v9h2.5v9h3V2c-3 0-5.5 2-5.5 5z'],
    'profile'       => ['label' => 'Profile',       'href' => '/customer/profile.php',       'icon' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
];
?>
<div class="dash">
  <aside class="dash-side" id="dashSide" aria-label="Account navigation">
    <a href="<?= url('/') ?>" class="brand brand-light">
      <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
        <rect x="2" y="2" width="44" height="44" rx="12" fill="none" stroke="currentColor" stroke-width="2.5"/>
        <path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="currentColor" transform="translate(3.5 0) scale(0.86)"/>
        <path d="M30 12v24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <span class="brand-name">Uma <em>Lafatik</em></span>
    </a>

    <nav class="dash-nav">
      <?php foreach ($nav as $key => $item): ?>
      <a href="<?= url($item['href']) ?>" class="dash-link <?= $nav_active === $key ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= $item['icon'] ?>" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span><?= e($item['label']) ?></span>
        <?php if ($key === 'notifications' && $unread > 0): ?>
          <span class="nav-badge" aria-label="<?= (int) $unread ?> unread notifications"><?= (int) $unread > 9 ? '9+' : (int) $unread ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="dash-side-foot">
      <a href="<?= url('/') ?>" class="dash-link">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Back to Website</span>
      </a>
      <a href="<?= url('/customer/logout.php') ?>" class="dash-link dash-logout">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Sign Out</span>
      </a>
    </div>
  </aside>

  <div class="dash-body">
    <div class="dash-topbar">
      <button class="nav-toggle dash-toggle" id="dashToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="dashSide">
        <span></span><span></span><span></span>
      </button>
      <h1 class="dash-title"><?= e($page_title) ?></h1>
      <div class="dash-top-actions">
        <a href="<?= url('/customer/notifications.php') ?>" class="icon-btn" aria-label="Notifications<?= $unread ? " ($unread unread)" : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?php if ($unread > 0): ?><span class="nav-badge" aria-hidden="true"><?= (int) $unread > 9 ? '9+' : (int) $unread ?></span><?php endif; ?>
        </a>
        <a href="<?= url('/customer/profile.php') ?>" class="user-chip">
          <span class="user-avatar" aria-hidden="true"><?= e(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1)) ?></span>
          <span class="user-chip-name"><?= e($user['first_name']) ?> <?= e($user['last_name']) ?></span>
        </a>
      </div>
    </div>
    <main class="dash-main" id="main">
      <?= flash() ?>
