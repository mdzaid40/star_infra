<?php
require __DIR__ . '/_inc/bootstrap.php';
require_login();
require_post_csrf();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = $pdo->prepare('SELECT id, title, featured_image, status FROM posts WHERE id = ?');
    $st->execute([$id]);
    $post = $st->fetch();
    if (!$post) {
        flash('error', 'Post not found.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        delete_upload($post['featured_image']);
        flash('success', 'Deleted “' . $post['title'] . '”.');
    } elseif ($action === 'toggle') {
        if ($post['status'] === 'published') {
            $pdo->prepare("UPDATE posts SET status = 'draft', updated_at = ? WHERE id = ?")->execute([now(), $id]);
            flash('success', '“' . $post['title'] . '” moved to drafts.');
        } else {
            $pdo->prepare("UPDATE posts SET status = 'published', published_at = COALESCE(published_at, ?), updated_at = ? WHERE id = ?")->execute([now(), now(), $id]);
            flash('success', '“' . $post['title'] . '” is now published.');
        }
    }
    redirect(admin_self());
}

$status = in_array($_GET['status'] ?? '', ['published', 'draft'], true) ? $_GET['status'] : '';
$q = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];
if ($status !== '') { $where[] = 'p.status = ?'; $params[] = $status; }
if ($q !== '') { $where[] = 'p.title LIKE ?'; $params[] = '%' . addcslashes($q, '%_\\') . '%'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM posts p $whereSql");
$st->execute($params);
$total = (int)$st->fetchColumn();
[$page, $pages, $offset] = paginate($total, 20, $_GET['page'] ?? 1);

$st = $pdo->prepare("SELECT p.id, p.title, p.slug, p.status, p.published_at, p.updated_at, p.featured_image, u.name AS author
                     FROM posts p LEFT JOIN users u ON u.id = p.author_id
                     $whereSql ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT 20 OFFSET $offset");
$st->execute($params);
$posts = $st->fetchAll();

admin_header('Blog posts', 'posts');
?>
<div class="toolbar">
  <a class="btn" href="<?= e(url('admin/post-edit.php')) ?>">+ New post</a>
  <form method="get" class="filters">
    <select name="status" onchange="this.form.submit()">
      <option value="">All posts</option>
      <option value="published"<?= selected($status === 'published') ?>>Published</option>
      <option value="draft"<?= selected($status === 'draft') ?>>Drafts</option>
    </select>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search titles…">
    <button class="btn secondary" type="submit">Filter</button>
  </form>
</div>

<?php if (!$posts): ?>
<div class="card empty"><p>No posts found.</p><a class="btn" href="<?= e(url('admin/post-edit.php')) ?>">Write a new post</a></div>
<?php else: ?>
<div class="table-wrap card">
<table class="table">
  <thead><tr><th>Title</th><th>Status</th><th>Date</th><th class="hide-sm">Author</th><th class="actions-col">Actions</th></tr></thead>
  <tbody>
<?php foreach ($posts as $p):
    $scheduled = $p['status'] === 'published' && $p['published_at'] && strtotime($p['published_at']) > time(); ?>
    <tr>
      <td data-label="Title">
        <div class="title-cell">
<?php if ($p['featured_image']): ?><img src="<?= e(image_url($p['featured_image'])) ?>" alt="" width="56" height="38" loading="lazy"><?php endif; ?>
          <a href="<?= e(url('admin/post-edit.php?id=' . $p['id'])) ?>"><strong><?= e($p['title']) ?></strong></a>
        </div>
      </td>
      <td data-label="Status"><span class="pill <?= $scheduled ? 'scheduled' : e($p['status']) ?>"><?= $scheduled ? 'scheduled' : e($p['status']) ?></span></td>
      <td data-label="Date"><?= e(format_date($p['published_at'] ?: $p['updated_at'], 'j M Y')) ?></td>
      <td data-label="Author" class="hide-sm"><?= e($p['author'] ?: '—') ?></td>
      <td class="actions">
        <a class="btn small secondary" href="<?= e(url('admin/post-edit.php?id=' . $p['id'])) ?>">Edit</a>
        <a class="btn small secondary" href="<?= e(post_url($p['slug']) . ($p['status'] === 'published' && !$scheduled ? '' : (PRETTY_URLS ? '?' : '&') . 'preview=1')) ?>" target="_blank" rel="noopener">View</a>
        <form method="post" class="inline-form">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="toggle">
          <button class="btn small secondary" type="submit"><?= $p['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
        </form>
        <form method="post" class="inline-form" data-confirm="Delete this post permanently?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete">
          <button class="btn small danger" type="submit">Delete</button>
        </form>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php admin_pagination($page, $pages, array_filter(['status' => $status, 'q' => $q])); ?>
<?php endif; ?>
<?php admin_footer(); ?>
