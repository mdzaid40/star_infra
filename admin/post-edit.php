<?php
require __DIR__ . '/_inc/bootstrap.php';
$user = require_login();
require_post_csrf();
$pdo = admin_db();

$id = (int)($_GET['id'] ?? 0);
$post = [
    'id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'featured_image' => null,
    'status' => 'draft', 'published_at' => null, 'meta_title' => '', 'meta_description' => '',
];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { flash('error', 'Post not found.'); redirect('admin/posts.php'); }
    $post = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post['title']            = input('title', 200);
    $post['slug']             = input('slug', 190);
    $post['excerpt']          = input('excerpt', 500);
    $post['content']          = sanitize_html((string)($_POST['content'] ?? ''));
    $post['meta_title']       = input('meta_title', 200);
    $post['meta_description'] = input('meta_description', 300);
    $post['status']           = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    $publishedInput           = input('published_at', 20);

    if ($post['title'] === '') { $errors[] = 'Please enter a title.'; }
    if (trim(strip_tags($post['content'], '<img>')) === '') { $errors[] = 'Please write some content.'; }

    if ($publishedInput !== '') {
        $ts = strtotime($publishedInput);
        if ($ts === false) { $errors[] = 'Publish date is not valid.'; } else { $post['published_at'] = date('Y-m-d H:i:s', $ts); }
    } elseif ($post['status'] === 'published') {
        $post['published_at'] = $post['published_at'] ?: now();
    } else {
        $post['published_at'] = null;
    }

    // Handle the image only once the rest is valid, so a failed save never orphans/deletes files.
    if (!$errors) {
        [$image, $imgError] = process_image_field('featured_image', $post['featured_image']);
        if ($imgError) { $errors[] = $imgError; } else { $post['featured_image'] = $image; }
    } elseif (!empty($_FILES['featured_image']['name'])) {
        $errors[] = 'Please choose the featured image again after fixing the errors above.';
    }

    if (!$errors) {
        $post['slug'] = unique_slug($pdo, 'posts', $post['slug'] !== '' ? $post['slug'] : $post['title'], (int)$post['id']);
        if ($post['excerpt'] === '') {
            $post['excerpt'] = str_limit(html_entity_decode(strip_tags(str_replace('</p>', '</p> ', $post['content'])), ENT_QUOTES, 'UTF-8'), 200);
        }
        $values = [$post['title'], $post['slug'], $post['excerpt'], $post['content'], $post['featured_image'], $post['status'],
                   $post['published_at'], $post['meta_title'], $post['meta_description'], now()];
        if ($post['id']) {
            $st = $pdo->prepare('UPDATE posts SET title=?, slug=?, excerpt=?, content=?, featured_image=?, status=?, published_at=?, meta_title=?, meta_description=?, updated_at=? WHERE id=?');
            $st->execute(array_merge($values, [$post['id']]));
            flash('success', 'Post saved.');
        } else {
            $st = $pdo->prepare('INSERT INTO posts (title, slug, excerpt, content, featured_image, status, published_at, meta_title, meta_description, updated_at, author_id, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute(array_merge($values, [$user['id'], now()]));
            $post['id'] = (int)$pdo->lastInsertId();
            flash('success', $post['status'] === 'published' ? 'Post published.' : 'Draft created.');
        }
        redirect('admin/post-edit.php?id=' . $post['id']);
    }
}

$isNew = !$post['id'];
$scheduled = $post['status'] === 'published' && $post['published_at'] && strtotime($post['published_at']) > time();
$viewUrl = $post['id'] && $post['slug'] ? post_url($post['slug']) . ($post['status'] === 'published' && !$scheduled ? '' : (PRETTY_URLS ? '?' : '&') . 'preview=1') : '';

admin_header($isNew ? 'New blog post' : 'Edit post', 'posts');
?>
<?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="editor-layout" id="post-form" data-upload-url="<?= e(url('admin/upload.php')) ?>">
  <?= csrf_field() ?>
  <div class="editor-main">
    <div class="card">
      <label class="field">Title<input name="title" id="title" required maxlength="200" value="<?= e($post['title']) ?>" placeholder="Post title" class="input-lg"></label>
      <label class="field">URL slug
        <div class="input-prefix"><span><?= e(PRETTY_URLS ? '/blog/' : 'post.php?slug=') ?></span><input name="slug" id="slug" maxlength="190" value="<?= e($post['slug']) ?>" placeholder="auto-generated-from-title" pattern="[a-z0-9-]*" data-autoslug="<?= $post['slug'] === '' ? '1' : '0' ?>"></div>
        <small class="help">Lowercase letters, numbers and hyphens. Changing it later breaks existing links.</small>
      </label>
      <div class="field">
        <span class="label">Content</span>
        <div id="editor" class="editor-surface" hidden><?= $post['content'] ?></div>
        <textarea name="content" id="content" rows="18" class="editor-fallback"><?= e($post['content']) ?></textarea>
        <small class="help">Use the toolbar for headings, lists, links and images. Images are uploaded to your server.</small>
      </div>
      <label class="field">Excerpt (summary shown on the blog list)
        <textarea name="excerpt" rows="3" maxlength="500" placeholder="Leave empty to generate from the content"><?= e($post['excerpt']) ?></textarea>
      </label>
    </div>

    <details class="card"<?= ($post['meta_title'] || $post['meta_description']) ? ' open' : '' ?>>
      <summary>SEO (optional)</summary>
      <label class="field">Meta title<input name="meta_title" maxlength="200" value="<?= e($post['meta_title']) ?>" placeholder="Defaults to the post title"></label>
      <label class="field">Meta description<textarea name="meta_description" rows="2" maxlength="300" placeholder="Defaults to the excerpt"><?= e($post['meta_description']) ?></textarea></label>
    </details>
  </div>

  <aside class="editor-side">
    <div class="card">
      <label class="field">Status
        <select name="status">
          <option value="draft"<?= selected($post['status'] === 'draft') ?>>Draft (hidden)</option>
          <option value="published"<?= selected($post['status'] === 'published') ?>>Published</option>
        </select>
      </label>
      <label class="field">Publish date
        <input type="datetime-local" name="published_at" value="<?= $post['published_at'] ? e(date('Y-m-d\TH:i', strtotime($post['published_at']))) : '' ?>">
        <small class="help">Leave empty to use “now”. A future date schedules the post.</small>
      </label>
<?php if ($scheduled): ?><p class="pill scheduled">Scheduled for <?= e(format_date($post['published_at'], 'j M Y, g:i a')) ?></p><?php endif; ?>
      <div class="side-actions">
        <button class="btn" type="submit">Save</button>
<?php if ($viewUrl): ?><a class="btn secondary" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener"><?= $post['status'] === 'published' && !$scheduled ? 'View' : 'Preview' ?> ↗</a><?php endif; ?>
      </div>
    </div>
    <div class="card">
      <?php image_field('featured_image', $post['featured_image'], 'Featured image', 'Recommended 1600×900'); ?>
    </div>
<?php if (!$isNew): ?>
    <p class="muted small">Last updated <?= e(format_date($post['updated_at'], 'j M Y, g:i a')) ?></p>
<?php endif; ?>
  </aside>
</form>

<?php if (!$isNew): ?>
<form method="post" action="<?= e(url('admin/posts.php')) ?>" data-confirm="Delete this post permanently?" class="danger-zone">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$post['id'] ?>"><input type="hidden" name="action" value="delete">
  <button class="btn danger small" type="submit">Delete post</button>
</form>
<?php endif; ?>

<?php
// Quill rich-text editor (falls back to the plain textarea if the CDN is unreachable).
admin_footer(
    '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">' . "\n"
  . '<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js" defer></script>' . "\n"
  . '<script src="' . e(asset('admin/assets/editor.js')) . '" defer></script>'
);
