<?php
if (!defined('APP_ROOT')) { require __DIR__ . '/includes/bootstrap.php'; }
http_response_code(404);

$pageTitle     = 'Page not found';
$noindex       = true;
$activeNav     = '';
unset($canonicalPath, $pageImage, $pageType, $extraHead);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero not-found">
  <div class="container">
    <div class="eyebrow"><span></span> ERROR 404</div>
    <h1>This page could<br> <em>not be found.</em></h1>
    <p>The page may have moved or no longer exists.</p>
    <div class="hero-actions">
      <a class="btn btn-dark" href="<?= e(url('')) ?>">Back to home <span aria-hidden="true">↗</span></a>
      <a class="text-link" href="<?= e(url('contact.php')) ?>">Contact us <span aria-hidden="true">→</span></a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
