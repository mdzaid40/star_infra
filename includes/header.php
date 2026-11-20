<?php
if (!defined('APP_ROOT')) { http_response_code(403); exit; }

/**
 * Page variables (set before including this file):
 *   $pageTitle, $pageDescription, $pageImage, $pageType, $activeNav, $canonicalPath, $extraHead, $noindex
 */
$siteName        = setting('site_name', SITE_NAME);
$pageTitle       = $pageTitle ?? 'Engineering & Infrastructure Solutions';
$pageDescription = str_limit($pageDescription ?? setting('meta_description'), 300);
$pageImage       = $pageImage ?? setting('og_image');
$pageType        = $pageType ?? 'website';
$activeNav       = $activeNav ?? basename($_SERVER['SCRIPT_NAME'] ?? 'index.php', '.php');
$fullTitle       = $pageTitle . ' | ' . $siteName;
$canonical       = isset($canonicalPath) ? abs_url($canonicalPath) : null;
$email           = setting('contact_email', SITE_EMAIL);
$phone           = setting('contact_phone', SITE_PHONE);

$navItems = [
    'index'    => ['Home', url('')],
    'about'    => ['About us', url('about.php')],
    'services' => ['Services', url('services.php')],
    'projects' => ['Capabilities', url('projects.php')],
    'blog'     => ['Blog', blog_url()],
    'contact'  => ['Contact', url('contact.php')],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
<?php if (!empty($noindex)): ?>
  <meta name="robots" content="noindex, follow">
<?php endif; ?>
<?php if ($canonical): ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
  <meta name="theme-color" content="#172522">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:type" content="<?= e($pageType) ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
<?php if ($canonical): ?>
  <meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
<?php if ($pageImage): ?>
  <meta property="og:image" content="<?= e(abs_url($pageImage)) ?>">
  <meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
  <link rel="icon" href="<?= e(asset('assets/images/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
  <link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?> blog" href="<?= e(url('feed.php')) ?>">
<?= $extraHead ?? '' ?>
<?= setting('head_scripts') ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="topbar">
  <div class="container topbar-inner">
    <span class="topbar-tagline"><?= e(setting('site_tagline', SITE_TAGLINE)) ?></span>
    <div class="topbar-contact">
      <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><span class="top-sep" aria-hidden="true">/</span><a href="<?= e(phone_link($phone)) ?>"><?= e($phone) ?></a>
    </div>
  </div>
</div>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="brand" href="<?= e(url('')) ?>" aria-label="<?= e($siteName) ?> home">
      <img class="brand-logo" src="<?= e(asset('assets/images/star-infra-logo.svg')) ?>" alt="<?= e($siteName) ?>" width="300" height="63">
    </a>
    <button class="menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav">
      <span></span><span></span><span></span>
    </button>
    <nav class="main-nav" id="main-nav" aria-label="Main">
<?php foreach ($navItems as $key => [$label, $href]): ?>
      <a class="<?= $activeNav === $key ? 'active' : '' ?>" href="<?= e($href) ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
      <a class="nav-cta" href="<?= e(url('contact.php')) ?>">Discuss a project <span aria-hidden="true">↗</span></a>
    </nav>
  </div>
</header>
<main id="main">
