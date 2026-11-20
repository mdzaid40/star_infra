<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle       = 'Capabilities & Project Delivery';
$pageDescription = setting('projects_subtitle');
$activeNav       = 'projects';
$canonicalPath   = 'projects.php';
$gallery         = gallery_items();

$steps = [
    ['Understand', 'Review the tender, drawings, specifications, site conditions and deliverables.'],
    ['Plan', 'Align resources, method statements, timelines and safety requirements.'],
    ['Execute', 'Coordinate site activities against approved scope and quality requirements.'],
    ['Close out', 'Complete testing, commissioning records and handover documentation as applicable.'],
];

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <div class="eyebrow"><span></span> PROJECT DELIVERY</div>
    <h1><?= rich(setting('projects_title')) ?></h1>
    <p><?= e(setting('projects_subtitle')) ?></p>
  </div>
</section>

<?php if ($gallery): ?>
<section class="project-gallery section-pad">
  <div class="container">
    <div class="eyebrow"><span></span> WORK ENVIRONMENTS</div>
    <h2>Across the worksite, <em>across disciplines.</em></h2>
    <div class="project-image-grid">
<?php foreach ($gallery as $item): ?>
      <figure>
        <img src="<?= e(image_url($item['image'])) ?>" alt="<?= e($item['alt_text'] ?: $item['caption']) ?>" width="600" height="400" loading="lazy" decoding="async">
<?php if (!empty($item['caption'])): ?>
        <figcaption><?= e($item['caption']) ?></figcaption>
<?php endif; ?>
      </figure>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-pad">
  <div class="container split-layout">
    <div><div class="eyebrow"><span></span> CONTRACTING SCOPE</div><h2>From defined packages<br>to <em>integrated works.</em></h2></div>
    <div class="rich-copy">
      <p class="lead">The firm may participate in government, PSU, public-sector, private-sector and corporate tenders, subject to applicable eligibility and qualification requirements.</p>
      <p>We can undertake contracts, subcontracts, work orders, EPC and allied engineering projects within our stated business scope and applicable approvals.</p>
      <p>Each engagement is approached with clear scope understanding, coordination with stakeholders and attention to project specifications and documentation.</p>
    </div>
  </div>
</section>

<section class="delivery-section section-pad">
  <div class="container">
    <div class="eyebrow"><span></span> DELIVERY FRAMEWORK</div>
    <h2>A clear path from scope<br>to <em>handover.</em></h2>
    <div class="steps-grid">
<?php foreach ($steps as $i => [$title, $text]): ?>
      <article><span><?= sprintf('%02d', $i + 1) ?></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
<?php endforeach; ?>
    </div>
<?php if (setting('projects_note') !== ''): ?>
    <p class="disclaimer"><?= e(setting('projects_note')) ?></p>
<?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
