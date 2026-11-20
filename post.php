<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? strtolower(trim($_GET['slug'])) : '';
$post = null;

// Admins may preview drafts/scheduled posts (session only started when previewing).
$preview = false;
if (isset($_GET['preview']) && isset($_COOKIE['sie_session'])) {
    session_boot();
    $preview = !empty($_SESSION['admin_id']);
}

$pdo = ($slug !== '' && preg_match('/^[a-z0-9-]{1,190}$/', $slug) && is_installed()) ? db_try() : null;
if ($pdo) {
    try {
        $sql = 'SELECT p.*, u.name AS author_name FROM posts p LEFT JOIN users u ON u.id = p.author_id WHERE p.slug = ?'
             . ($preview ? '' : " AND p.status = 'published' AND p.published_at <= ?");
        $st = $pdo->prepare($sql . ' LIMIT 1');
        $st->execute($preview ? [$slug] : [$slug, now()]);
        $post = $st->fetch() ?: null;
    } catch (Throwable $e) {
        error_log('[post] ' . $e->getMessage());
    }
}

if (!$post) {
    require __DIR__ . '/404.php';
    exit;
}

$isLive          = $post['status'] === 'published' && $post['published_at'] && strtotime($post['published_at']) <= time();
$pageTitle       = $post['meta_title'] ?: $post['title'];
$pageDescription = $post['meta_description'] ?: post_excerpt($post);
$pageImage       = $post['featured_image'] ?: null;
$pageType        = 'article';
$activeNav       = 'blog';
$canonicalPath   = post_url($post['slug']);
$noindex         = !$isLive;
$related         = published_posts(3, 0, (int)$post['id']);
$postUrl         = abs_url(post_url($post['slug']));
$published       = $post['published_at'] ?: $post['created_at'];

$jsonLd = [
    '@context'      => 'https://schema.org',
    '@type'         => 'BlogPosting',
    'headline'      => $post['title'],
    'description'   => $pageDescription,
    'datePublished' => date('c', strtotime($published)),
    'dateModified'  => date('c', strtotime($post['updated_at'])),
    'mainEntityOfPage' => $postUrl,
    'author'        => ['@type' => 'Organization', 'name' => setting('site_name', SITE_NAME)],
    'publisher'     => ['@type' => 'Organization', 'name' => setting('site_name', SITE_NAME),
                        'logo' => ['@type' => 'ImageObject', 'url' => abs_url('assets/images/star-infra-logo.svg')]],
];
if ($pageImage) { $jsonLd['image'] = abs_url($pageImage); }
$extraHead = '<meta property="article:published_time" content="' . e(date('c', strtotime($published))) . '">' . "\n"
           . '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>' . "\n";

require __DIR__ . '/includes/header.php';
?>
<?php if (!$isLive): ?>
<div class="preview-bar">Preview — this post is <strong><?= e($post['status'] === 'published' ? 'scheduled for ' . format_date($post['published_at'], 'j M Y, g:i a') : 'a draft') ?></strong> and is not visible to the public.</div>
<?php endif; ?>
<article class="post">
  <header class="page-hero post-hero">
    <div class="container post-container">
      <a class="back-link" href="<?= e(blog_url()) ?>">← All articles</a>
      <h1><?= e($post['title']) ?></h1>
      <div class="post-meta">
        <time datetime="<?= e(date('c', strtotime($published))) ?>"><?= e(format_date($published, 'j F Y')) ?></time>
        <span aria-hidden="true">·</span>
        <span><?= reading_time((string)$post['content']) ?> min read</span>
<?php if (!empty($post['author_name'])): ?>
        <span aria-hidden="true">·</span><span><?= e($post['author_name']) ?></span>
<?php endif; ?>
      </div>
    </div>
  </header>
<?php if ($post['featured_image']): ?>
  <div class="container post-container post-cover">
    <img src="<?= e(image_url($post['featured_image'])) ?>" alt="<?= e($post['title']) ?>" width="1200" height="640" fetchpriority="high">
  </div>
<?php endif; ?>
  <div class="container post-container section-pad-sm">
    <div class="prose">
      <?= $post['content'] /* sanitised when saved in admin */ ?>
    </div>
    <div class="share">
      <span>Share</span>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode($postUrl) ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
      <a href="https://wa.me/?text=<?= rawurlencode($post['title'] . ' ' . $postUrl) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
      <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($postUrl) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
      <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($postUrl) ?>&amp;text=<?= rawurlencode($post['title']) ?>" target="_blank" rel="noopener noreferrer">X</a>
    </div>
  </div>
</article>

<?php if ($related): ?>
<section class="section-pad related-posts">
  <div class="container">
    <div class="section-heading"><div><div class="eyebrow"><span></span> KEEP READING</div><h2>More <em>articles.</em></h2></div></div>
    <div class="post-grid">
<?php foreach ($related as $post) { require __DIR__ . '/includes/partials/post-card.php'; } ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="mini-cta">
  <div class="container">
    <h2>Have a project you'd like to discuss?</h2>
    <a class="btn btn-accent" href="<?= e(url('contact.php')) ?>">Talk to our team <span aria-hidden="true">↗</span></a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
