<?php
require __DIR__ . '/includes/bootstrap.php';

$posts = published_posts(20);
$siteName = setting('site_name', SITE_NAME);

header('Content-Type: application/rss+xml; charset=UTF-8');
header('Cache-Control: public, max-age=900');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title><?= e($siteName) ?> — Blog</title>
  <link><?= e(abs_url(blog_url())) ?></link>
  <atom:link href="<?= e(abs_url('feed.php')) ?>" rel="self" type="application/rss+xml"/>
  <description><?= e(setting('meta_description')) ?></description>
  <language>en-in</language>
<?php foreach ($posts as $p): ?>
  <item>
    <title><?= e($p['title']) ?></title>
    <link><?= e(abs_url(post_url($p['slug']))) ?></link>
    <guid isPermaLink="true"><?= e(abs_url(post_url($p['slug']))) ?></guid>
    <pubDate><?= e(date(DATE_RSS, strtotime($p['published_at']))) ?></pubDate>
    <description><?= e(post_excerpt($p)) ?></description>
  </item>
<?php endforeach; ?>
</channel>
</rss>
