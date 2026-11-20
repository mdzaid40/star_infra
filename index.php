<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle     = 'Engineering & Infrastructure';
$activeNav     = 'index';
$canonicalPath = '';
$services      = services();
$latestPosts   = setting('home_show_blog') === '1' ? published_posts(3) : [];
$heroImage     = setting('hero_image', 'assets/images/plant-team.webp');
$extraHead     = '<link rel="preload" as="image" href="' . e(image_url($heroImage)) . '" fetchpriority="high">';
$total         = count($services);

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-image" style="background-image:url('<?= e(image_url($heroImage)) ?>')"></div><div class="hero-shade"></div>
  <div class="container hero-content">
    <div class="eyebrow light-eyebrow"><span></span> <?= e(setting('hero_eyebrow')) ?></div>
    <h1><?= rich(setting('hero_title')) ?></h1>
    <p><?= e(setting('hero_text')) ?></p>
    <div class="hero-actions">
      <a class="btn btn-accent" href="<?= e(url('services.php')) ?>">Explore our capabilities <span aria-hidden="true">↗</span></a>
      <a class="text-link light-link" href="<?= e(url('contact.php')) ?>">Start a conversation <span aria-hidden="true">→</span></a>
    </div>
  </div>
  <div class="hero-index" aria-hidden="true"><span>01</span><i></i><span>ENGINEERING / DELIVERY</span></div>
</section>

<section class="intro-section section-pad">
  <div class="container intro-grid">
    <div><div class="eyebrow"><span></span> WHO WE ARE</div><h2><?= rich(setting('intro_title')) ?></h2></div>
    <div class="intro-copy">
      <p class="lead"><?= e(setting('intro_lead')) ?></p>
      <?= paragraphs(setting('intro_text')) ?>
      <a class="text-link" href="<?= e(url('about.php')) ?>">More about our approach <span aria-hidden="true">→</span></a>
    </div>
  </div>
</section>

<?php if ($services): ?>
<section class="services-section section-pad">
  <div class="container">
    <div class="section-heading">
      <div><div class="eyebrow"><span></span> WHAT WE DO</div><h2>Capabilities for complex<br> <em>working environments.</em></h2></div>
      <a class="text-link" href="<?= e(url('services.php')) ?>">View all services <span aria-hidden="true">→</span></a>
    </div>
    <div class="service-grid">
<?php foreach ($services as $i => $svc): ?>
      <a class="service-card" href="<?= e(url('services.php#' . $svc['slug'])) ?>">
<?php if (!empty($svc['image'])): ?>
        <img class="service-photo" src="<?= e(image_url($svc['image'])) ?>" alt="<?= e($svc['image_alt'] ?: $svc['title']) ?>" width="600" height="320" loading="lazy" decoding="async">
<?php endif; ?>
        <span class="service-no"><?= sprintf('%02d / %02d', $i + 1, $total) ?></span>
        <div class="line-icon" aria-hidden="true"><?= e($svc['icon'] ?: '◆') ?></div>
        <h3><?= e($svc['title']) ?></h3>
        <p><?= e($svc['short_description']) ?></p>
        <span class="card-arrow" aria-hidden="true">↗</span>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="statement-section">
  <div class="container statement-inner">
    <div class="eyebrow light-eyebrow"><span></span> HOW WE WORK</div>
    <h2><?= rich(setting('statement_title')) ?></h2>
    <a class="btn btn-outline" href="<?= e(url('about.php')) ?>">Our working principles <span aria-hidden="true">↗</span></a>
  </div>
</section>

<?php if ($latestPosts): ?>
<section class="section-pad blog-home">
  <div class="container">
    <div class="section-heading">
      <div><div class="eyebrow"><span></span> INSIGHTS &amp; UPDATES</div><h2>From the <em>blog.</em></h2></div>
      <a class="text-link" href="<?= e(blog_url()) ?>">All articles <span aria-hidden="true">→</span></a>
    </div>
    <div class="post-grid">
<?php foreach ($latestPosts as $post) { require __DIR__ . '/includes/partials/post-card.php'; } ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="cta-section section-pad">
  <div class="container cta-inner">
    <div><div class="eyebrow"><span></span> HAVE A PROJECT IN MIND?</div><h2><?= rich(setting('cta_title')) ?></h2></div>
    <a class="btn btn-dark" href="<?= e(url('contact.php')) ?>">Talk to our team <span aria-hidden="true">↗</span></a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
