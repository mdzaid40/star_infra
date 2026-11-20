<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle       = 'Our Services';
$pageDescription = 'Plant & mechanical works, steel and MDPE gas pipelines, GI pipelines, civil construction and industrial maintenance services.';
$activeNav       = 'services';
$canonicalPath   = 'services.php';
$services        = services();

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <div class="eyebrow"><span></span> OUR CAPABILITIES</div>
    <h1>One partner. Multiple<br> <em>engineering disciplines.</em></h1>
    <p>Integrated contracting capabilities for industrial facilities and infrastructure networks.</p>
  </div>
</section>

<section class="section-pad service-detail-wrap">
  <div class="container service-detail-list">
<?php if (!$services): ?>
    <p class="empty-state">Service details will be published soon. Please <a href="<?= e(url('contact.php')) ?>">contact us</a> for information.</p>
<?php endif; ?>
<?php foreach ($services as $i => $svc): $features = service_features($svc['features']); ?>
    <article id="<?= e($svc['slug']) ?>" class="service-detail<?= !empty($svc['image']) ? ' has-image' : '' ?>">
      <span class="detail-no"><?= sprintf('%02d', $i + 1) ?></span>
      <div class="service-detail-body">
<?php if (!empty($svc['category'])): ?>
        <div class="eyebrow"><span></span> <?= e($svc['category']) ?></div>
<?php endif; ?>
        <h2><?= e($svc['title']) ?></h2>
        <?= paragraphs($svc['description'] ?: $svc['short_description']) ?>
<?php if ($features): ?>
        <ul>
<?php foreach ($features as $f): ?>
          <li><?= e($f) ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>
<?php if (!empty($svc['image'])): ?>
      <img class="service-detail-img" src="<?= e(image_url($svc['image'])) ?>" alt="<?= e($svc['image_alt'] ?: $svc['title']) ?>" width="480" height="320" loading="lazy" decoding="async">
<?php endif; ?>
    </article>
<?php endforeach; ?>
  </div>
</section>

<section class="mini-cta">
  <div class="container">
    <h2>Need a scope-specific proposal?</h2>
    <a class="btn btn-accent" href="<?= e(url('contact.php')) ?>">Share your requirement <span aria-hidden="true">↗</span></a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
