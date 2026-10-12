<?php
/**
 * Admin dashboard shell.
 * Variables: $page_title, $nav_active
 */
require_admin();

$admin = current_admin();
$nav_active = $nav_active ?? '';
$page_title = $page_title ?? 'Admin Dashboard — ' . SITE_NAME;
$extra_css  = array_merge($extra_css ?? [], [url('/assets/css/dashboard.css')]);

require_once __DIR__ . '/head.php';

$nav = [
    'dashboard'   => ['label' => 'Overview',     'href' => '/admin/',                'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    'orders'      => ['label' => 'Orders',       'href' => '/admin/orders.php',      'icon' => 'M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6zm3 15a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM3 6h18'],
    'menu'        => ['label' => 'Menu Items',   'href' => '/admin/menu-items.php',  'icon' => 'M11 9H9V2H7v7H5V2H3v7c0 2.1 1.6 3.8 3.7 4L6.5 22h3l-.2-9C11.4 12.8 13 11.1 13 9V2h-2v7zm5-4v9h2.5v9h3V2c-3 0-5.5 2-5.5 5z'],
    'categories'  => ['label' => 'Categories',   'href' => '/admin/categories.php',  'icon' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z'],
    'reservations'=> ['label' => 'Reservations', 'href' => '/admin/reservations.php','icon' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z'],
    'customers'   => ['label' => 'Customers',    'href' => '/admin/customers.php',   'icon' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
    'reviews'     => ['label' => 'Reviews',      'href' => '/admin/reviews.php',     'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2zM9 9l2 2 4-4'],
    'reports'     => ['label' => 'Reports',      'href' => '/admin/reports.php',     'icon' => 'M3 3v18h18M18 17V9M13 17V5M8 17v-3'],
    'settings'    => ['label' => 'Settings',     'href' => '/admin/settings.php',    'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z'],
];
?>
<div class="dash">
  <aside class="dash-side dash-side-admin" id="dashSide" aria-label="Admin navigation">
    <a href="<?= url('/admin/') ?>" class="brand brand-light">
      <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
        <rect x="2" y="2" width="44" height="44" rx="12" fill="none" stroke="currentColor" stroke-width="2.5"/>
        <path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="currentColor" transform="translate(3.5 0) scale(0.86)"/>
        <path d="M30 12v24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <span class="brand-name">Uma <em>Lafatik</em> <small>Admin</small></span>
    </a>

    <nav class="dash-nav">
      <?php foreach ($nav as $key => $item): ?>
      <a href="<?= url($item['href']) ?>" class="dash-link <?= $nav_active === $key ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= $item['icon'] ?>" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span><?= e($item['label']) ?></span>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="dash-side-foot">
      <a href="<?= url('/') ?>" class="dash-link">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>View Website</span>
      </a>
      <a href="<?= url('/admin/logout.php') ?>" class="dash-link dash-logout">
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
        <span class="user-chip">
          <span class="user-avatar user-avatar-admin" aria-hidden="true"><?= e(mb_substr($admin['name'], 0, 1)) ?></span>
          <span class="user-chip-name"><?= e($admin['name']) ?> <small>(<?= e($admin['role']) ?>)</small></span>
        </span>
      </div>
    </div>
    <main class="dash-main" id="main">
      <?= flash() ?>
