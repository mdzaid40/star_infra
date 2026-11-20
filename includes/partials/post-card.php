<?php
/** Expects $post (row from published_posts()). */
if (!defined('APP_ROOT')) { http_response_code(403); exit; }
$__href = post_url($post['slug']);
?>
      <article class="post-card">
        <a class="post-card-media" href="<?= e($__href) ?>" tabindex="-1" aria-hidden="true">
<?php if (!empty($post['featured_image'])): ?>
          <img src="<?= e(image_url($post['featured_image'])) ?>" alt="" width="640" height="360" loading="lazy" decoding="async">
<?php else: ?>
          <span class="post-card-placeholder">★</span>
<?php endif; ?>
        </a>
        <div class="post-card-body">
          <time datetime="<?= e(date('c', strtotime($post['published_at']))) ?>"><?= e(format_date($post['published_at'])) ?></time>
          <h3><a href="<?= e($__href) ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e(str_limit(post_excerpt($post), 150)) ?></p>
          <a class="text-link" href="<?= e($__href) ?>">Read article <span aria-hidden="true">→</span></a>
        </div>
      </article>
