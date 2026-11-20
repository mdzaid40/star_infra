<?php
require __DIR__ . '/includes/bootstrap.php';

$perPage = 9;
$total   = published_posts_count();
[$page, $pages, $offset] = paginate($total, $perPage, $_GET['page'] ?? 1);
$posts   = $total ? published_posts($perPage, $offset) : [];

$pageTitle       = 'Blog' . ($page > 1 ? ' – Page ' . $page : '');
$pageDescription = 'News, project updates and insights on pipeline, mechanical, civil and industrial maintenance works from ' . setting('site_name', SITE_NAME) . '.';
$activeNav       = 'blog';
$canonicalPath   = blog_url($page);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <div class="eyebrow"><span></span> BLOG</div>
    <h1>Insights from<br> <em>the worksite.</em></h1>
    <p>Project updates, industry notes and practical perspectives on engineering and infrastructure delivery.</p>
  </div>
</section>

<section class="section-pad">
  <div class="container">
<?php if (!$posts): ?>
    <div class="empty-state">
      <h2>No articles yet.</h2>
      <p>We're preparing our first posts. In the meantime, <a href="<?= e(url('contact.php')) ?>">get in touch</a> to discuss your project.</p>
    </div>
<?php else: ?>
    <div class="post-grid">
<?php foreach ($posts as $post) { require __DIR__ . '/includes/partials/post-card.php'; } ?>
    </div>
<?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Blog pages">
<?php if ($page > 1): ?>
      <a href="<?= e(blog_url($page - 1)) ?>" rel="prev">← Newer</a>
<?php endif; ?>
<?php $gap = false; for ($i = 1; $i <= $pages; $i++):
        if ($i !== 1 && $i !== $pages && abs($i - $page) > 2) { if (!$gap) { echo '      <span class="pagination-gap">…</span>' . "\n"; $gap = true; } continue; }
        $gap = false; ?>
      <a href="<?= e(blog_url($i)) ?>"<?= $i === $page ? ' class="active" aria-current="page"' : '' ?>><?= $i ?></a>
<?php endfor; ?>
<?php if ($page < $pages): ?>
      <a href="<?= e(blog_url($page + 1)) ?>" rel="next">Older →</a>
<?php endif; ?>
    </nav>
<?php endif; ?>
<?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
