<?php
require __DIR__ . '/_inc/bootstrap.php';
require_login();
require_post_csrf();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $error = null;
        $path = handle_image_upload($_FILES['image'] ?? null, $error);
        if ($error) {
            flash('error', $error);
        } elseif (!$path) {
            flash('error', 'Please choose an image to upload.');
        } else {
            $sort = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM gallery')->fetchColumn();
            $pdo->prepare('INSERT INTO gallery (image, caption, alt_text, sort_order, is_active, created_at) VALUES (?,?,?,?,1,?)')
                ->execute([$path, input('caption', 200), input('alt_text', 200), $sort, now()]);
            flash('success', 'Image added to the gallery.');
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT id, image FROM gallery WHERE id = ?');
        $st->execute([$id]);
        $item = $st->fetch();
        if (!$item) {
            flash('error', 'Image not found.');
        } elseif ($action === 'update') {
            $pdo->prepare('UPDATE gallery SET caption = ?, alt_text = ?, sort_order = ?, is_active = ? WHERE id = ?')
                ->execute([input('caption', 200), input('alt_text', 200), (int)($_POST['sort_order'] ?? 0), !empty($_POST['is_active']) ? 1 : 0, $id]);
            flash('success', 'Image details saved.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM gallery WHERE id = ?')->execute([$id]);
            delete_upload($item['image']);
            flash('success', 'Image deleted.');
        }
    }
    redirect('admin/gallery.php');
}

$items = $pdo->query('SELECT * FROM gallery ORDER BY sort_order ASC, id ASC')->fetchAll();

admin_header('Gallery', 'gallery');
?>
<p class="muted">These photos appear on the <a href="<?= e(url('projects.php')) ?>" target="_blank" rel="noopener">Capabilities page</a>. Use real project photos you have permission to publish.</p>

<form method="post" enctype="multipart/form-data" class="card upload-card">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <h2>Add a photo</h2>
  <div class="grid-3">
    <?php image_field('image', null, 'Photo'); ?>
    <label class="field">Caption<input name="caption" maxlength="200" placeholder="e.g. Steel pipeline construction"></label>
    <label class="field">Alt text (describe the photo)<input name="alt_text" maxlength="200"></label>
  </div>
  <button class="btn" type="submit">Upload</button>
</form>

<?php if (!$items): ?>
<div class="card empty"><p>The gallery is empty.</p></div>
<?php else: ?>
<div class="gallery-admin">
<?php foreach ($items as $g): ?>
  <div class="card gallery-item<?= $g['is_active'] ? '' : ' is-hidden' ?>">
    <img src="<?= e(image_url($g['image'])) ?>" alt="" loading="lazy">
    <form method="post" class="stack">
      <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
      <label class="field">Caption<input name="caption" maxlength="200" value="<?= e($g['caption']) ?>"></label>
      <label class="field">Alt text<input name="alt_text" maxlength="200" value="<?= e($g['alt_text']) ?>"></label>
      <div class="grid-2">
        <label class="field">Order<input type="number" name="sort_order" value="<?= (int)$g['sort_order'] ?>"></label>
        <label class="inline"><input type="checkbox" name="is_active" value="1"<?= checked($g['is_active']) ?>> Visible</label>
      </div>
      <button class="btn small" type="submit">Save</button>
    </form>
    <form method="post" data-confirm="Delete this image?">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
      <button class="btn small danger" type="submit">Delete</button>
    </form>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php admin_footer(); ?>
