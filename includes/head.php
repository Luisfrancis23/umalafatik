<?php
/**
 * Shared HTML head + opening body tag.
 * Variables: $page_title, $page_desc, $extra_css (array), $page_schema (string JSON-LD)
 */
$page_title = $page_title ?? SITE_NAME . ' — ' . SITE_TAGLINE;
$page_desc  = $page_desc  ?? 'Uma Lafatik — exceptional food, warm hospitality and a memorable dining experience. Reserve a table or order online.';
$extra_css  = $extra_css  ?? [];
$page_schema = $page_schema ?? '';
$canonical  = $canonical ?? current_url();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_desc) ?>">
  <meta name="theme-color" content="#241e1a">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
  <meta property="og:title" content="<?= e($page_title) ?>">
  <meta property="og:description" content="<?= e($page_desc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e(BASE_URL . '/assets/images/og-cover.svg') ?>">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23241e1a'/%3E%3Cpath d='M16 6c-3 0-5 2.2-5 5v3.5c0 1.4 1 2.5 2.4 2.7L13 25h2l-.4-7.8c1.4-.2 2.4-1.3 2.4-2.7V11c0-2.8-2-5-5-5z' fill='%23d4a24e' transform='translate(4 1) scale(0.9)'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') . '?v=1' ?>">
  <?php foreach ($extra_css as $css): ?>
  <link rel="stylesheet" href="<?= e($css) . '?v=1' ?>">
  <?php endforeach; ?>
  <?php if ($page_schema): ?>
  <script type="application/ld+json"><?= $page_schema ?></script>
  <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>
