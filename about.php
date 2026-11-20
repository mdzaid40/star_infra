<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle       = 'About Us';
$pageDescription = setting('about_subtitle') . ' ' . setting('about_lead');
$activeNav       = 'about';
$canonicalPath   = 'about.php';

$values = [
    ['Safety first', 'Promoting safe work practices, site discipline and risk awareness throughout execution.'],
    ['Quality in detail', 'Following project specifications, approved procedures and applicable quality requirements.'],
    ['Clear coordination', 'Working closely with clients, consultants, vendors and site teams to keep work aligned.'],
    ['Responsible delivery', 'Respecting timelines, resources, documentation and the obligations of every assignment.'],
];

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <div class="eyebrow"><span></span> ABOUT THE FIRM</div>
    <h1><?= rich(setting('about_title')) ?></h1>
    <p><?= e(setting('about_subtitle')) ?></p>
  </div>
</section>

<section class="section-pad">
  <div class="container split-layout">
    <div><div class="eyebrow"><span></span> OUR APPROACH</div><h2>Built around the<br> <em>work that matters.</em></h2></div>
    <div class="rich-copy">
      <p class="lead"><?= e(setting('about_lead')) ?></p>
      <?= paragraphs(setting('about_body')) ?>
    </div>
  </div>
</section>

<section class="values-section section-pad">
  <div class="container">
    <div class="eyebrow"><span></span> OUR WORKING PRINCIPLES</div>
    <h2>What guides <em>our work.</em></h2>
    <div class="value-grid">
<?php foreach ($values as $i => [$title, $text]): ?>
      <article><b><?= sprintf('%02d', $i + 1) ?></b><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="mini-cta">
  <div class="container">
    <h2>Looking for a dependable contracting partner?</h2>
    <a class="btn btn-accent" href="<?= e(url('contact.php')) ?>">Get in touch <span aria-hidden="true">↗</span></a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
