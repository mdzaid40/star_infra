<?php
require __DIR__ . '/includes/bootstrap.php';

$pages = [
    ['', '1.0'], ['about.php', '0.8'], ['services.php', '0.9'], ['projects.php', '0.8'],
    [blog_url(), '0.7'], ['contact.php', '0.8'],
];
$posts = [];
$pdo = is_installed() ? db_try() : null;
if ($pdo) {
    try {
        // Only the two columns needed; capped to the sitemap protocol limit.
        $st = $pdo->prepare("SELECT slug, updated_at FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC LIMIT 5000");
        $st->execute([now()]);
        $posts = $st->fetchAll();
    } catch (Throwable $e) { error_log('[sitemap] ' . $e->getMessage()); }
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as [$path, $priority]): ?>
  <url><loc><?= e(abs_url($path)) ?></loc><priority><?= $priority ?></priority></url>
<?php endforeach; ?>
<?php foreach ($posts as $p): ?>
  <url><loc><?= e(abs_url(post_url($p['slug']))) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($p['updated_at']))) ?></lastmod><priority>0.6</priority></url>
<?php endforeach; ?>
</urlset>
