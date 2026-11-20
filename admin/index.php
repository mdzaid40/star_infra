<?php
require __DIR__ . '/_inc/bootstrap.php';
$user = require_login();
$pdo = admin_db();

// All counters in one round-trip.
$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM posts WHERE status = 'published') AS published,
    (SELECT COUNT(*) FROM posts WHERE status = 'draft') AS drafts,
    (SELECT COUNT(*) FROM services WHERE is_active = 1) AS services,
    (SELECT COUNT(*) FROM gallery WHERE is_active = 1) AS gallery,
    (SELECT COUNT(*) FROM enquiries) AS enquiries,
    (SELECT COUNT(*) FROM enquiries WHERE is_read = 0) AS unread")->fetch();

$recentEnquiries = $pdo->query('SELECT id, name, email, service, is_read, created_at FROM enquiries ORDER BY created_at DESC, id DESC LIMIT 6')->fetchAll();
$recentPosts = $pdo->query('SELECT id, title, slug, status, published_at, updated_at FROM posts ORDER BY updated_at DESC LIMIT 6')->fetchAll();

admin_header('Dashboard', 'dashboard');
?>
<p class="muted">Welcome back, <?= e($user['name']) ?>.<?php if ($user['last_login_at']): ?> Last login: <?= e(format_date($user['last_login_at'], 'j M Y, g:i a')) ?>.<?php endif; ?></p>

<div class="stat-grid">
  <a class="stat" href="<?= e(url('admin/posts.php?status=published')) ?>"><b><?= (int)$stats['published'] ?></b><span>Published posts</span></a>
  <a class="stat" href="<?= e(url('admin/posts.php?status=draft')) ?>"><b><?= (int)$stats['drafts'] ?></b><span>Drafts</span></a>
  <a class="stat" href="<?= e(url('admin/enquiries.php?filter=unread')) ?>"><b><?= (int)$stats['unread'] ?></b><span>Unread enquiries</span></a>
  <a class="stat" href="<?= e(url('admin/services.php')) ?>"><b><?= (int)$stats['services'] ?></b><span>Active services</span></a>
  <a class="stat" href="<?= e(url('admin/gallery.php')) ?>"><b><?= (int)$stats['gallery'] ?></b><span>Gallery images</span></a>
</div>

<div class="quick-actions">
  <a class="btn" href="<?= e(url('admin/post-edit.php')) ?>">+ New blog post</a>
  <a class="btn secondary" href="<?= e(url('admin/settings.php')) ?>">Edit site content</a>
  <a class="btn secondary" href="<?= e(url('admin/gallery.php')) ?>">Upload photos</a>
</div>

<div class="two-col">
  <section class="card">
    <div class="card-head"><h2>Latest enquiries</h2><a href="<?= e(url('admin/enquiries.php')) ?>">View all →</a></div>
<?php if (!$recentEnquiries): ?>
    <p class="muted">No enquiries yet. Contact-form submissions will appear here.</p>
<?php else: ?>
    <ul class="list">
<?php foreach ($recentEnquiries as $q): ?>
      <li class="<?= $q['is_read'] ? '' : 'unread' ?>">
        <a href="<?= e(url('admin/enquiry.php?id=' . $q['id'])) ?>"><strong><?= e($q['name']) ?></strong> <span class="muted"><?= e($q['service'] ?: 'General enquiry') ?></span></a>
        <time><?= e(format_date($q['created_at'], 'j M, g:i a')) ?></time>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h2>Recently edited posts</h2><a href="<?= e(url('admin/posts.php')) ?>">View all →</a></div>
<?php if (!$recentPosts): ?>
    <p class="muted">No posts yet. <a href="<?= e(url('admin/post-edit.php')) ?>">Write your first article</a>.</p>
<?php else: ?>
    <ul class="list">
<?php foreach ($recentPosts as $p): ?>
      <li>
        <a href="<?= e(url('admin/post-edit.php?id=' . $p['id'])) ?>"><strong><?= e($p['title']) ?></strong></a>
        <span class="pill <?= e($p['status']) ?>"><?= e($p['status']) ?></span>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </section>
</div>

<?php if (SMTP_HOST === '' ): ?>
<div class="alert info">Tip: contact-form emails are sent with PHP mail(). For reliable delivery on Hostinger, add your mailbox's SMTP details in <code>config.php</code>. Enquiries are always saved here as well.</div>
<?php endif; ?>
<?php admin_footer(); ?>
