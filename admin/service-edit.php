<?php
require __DIR__ . '/_inc/bootstrap.php';
require_login();
require_post_csrf();
$pdo = admin_db();

$id = (int)($_GET['id'] ?? 0);
$svc = ['id' => 0, 'title' => '', 'slug' => '', 'category' => '', 'icon' => '', 'short_description' => '', 'description' => '',
        'features' => '', 'image' => null, 'image_alt' => '', 'sort_order' => 0, 'is_active' => 1];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { flash('error', 'Service not found.'); redirect('admin/services.php'); }
    $svc = $row;
} else {
    $svc['sort_order'] = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM services')->fetchColumn();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $svc['title']             = input('title', 150);
    $svc['slug']              = input('slug', 100);
    $svc['category']          = mb_strtoupper(input('category', 100));
    $svc['icon']              = mb_substr(input('icon', 10), 0, 3);
    $svc['short_description'] = input('short_description', 300);
    $svc['description']       = input('description', 3000);
    $svc['features']          = implode("\n", service_features(input('features', 3000)));
    $svc['image_alt']         = input('image_alt', 200);
    $svc['sort_order']        = (int)($_POST['sort_order'] ?? 0);
    $svc['is_active']         = !empty($_POST['is_active']) ? 1 : 0;

    if ($svc['title'] === '') { $errors[] = 'Please enter a service title.'; }
    if ($svc['short_description'] === '') { $errors[] = 'Please enter a short description (shown on the homepage card).'; }

    if (!$errors) {
        [$image, $imgError] = process_image_field('image', $svc['image']);
        if ($imgError) { $errors[] = $imgError; } else { $svc['image'] = $image; }
    } elseif (!empty($_FILES['image']['name'])) {
        $errors[] = 'Please choose the image again after fixing the errors above.';
    }

    if (!$errors) {
        $svc['slug'] = unique_slug($pdo, 'services', $svc['slug'] !== '' ? $svc['slug'] : $svc['title'], (int)$svc['id']);
        $values = [$svc['title'], $svc['slug'], $svc['category'], $svc['icon'], $svc['short_description'], $svc['description'],
                   $svc['features'], $svc['image'], $svc['image_alt'], $svc['sort_order'], $svc['is_active'], now()];
        if ($svc['id']) {
            $pdo->prepare('UPDATE services SET title=?, slug=?, category=?, icon=?, short_description=?, description=?, features=?, image=?, image_alt=?, sort_order=?, is_active=?, updated_at=? WHERE id=?')
                ->execute(array_merge($values, [$svc['id']]));
        } else {
            $pdo->prepare('INSERT INTO services (title, slug, category, icon, short_description, description, features, image, image_alt, sort_order, is_active, updated_at, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute(array_merge($values, [now()]));
            $svc['id'] = (int)$pdo->lastInsertId();
        }
        flash('success', 'Service saved.');
        redirect('admin/service-edit.php?id=' . $svc['id']);
    }
}

admin_header($svc['id'] ? 'Edit service' : 'Add service', 'services');
?>
<?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>
<p><a href="<?= e(url('admin/services.php')) ?>">← All services</a></p>

<form method="post" enctype="multipart/form-data" class="editor-layout">
  <?= csrf_field() ?>
  <div class="editor-main">
    <div class="card">
      <label class="field">Service title<input name="title" id="title" required maxlength="150" value="<?= e($svc['title']) ?>" class="input-lg"></label>
      <div class="grid-2">
        <label class="field">Category label<input name="category" maxlength="100" value="<?= e($svc['category']) ?>" placeholder="e.g. GAS INFRASTRUCTURE"></label>
        <label class="field">Page anchor (URL #id)<input name="slug" id="slug" maxlength="100" pattern="[a-z0-9-]*" value="<?= e($svc['slug']) ?>" placeholder="auto" data-autoslug="<?= $svc['slug'] === '' ? '1' : '0' ?>"></label>
      </div>
      <label class="field">Short description (homepage card)<textarea name="short_description" rows="2" maxlength="300" required><?= e($svc['short_description']) ?></textarea></label>
      <label class="field">Full description (Services page)<textarea name="description" rows="4" maxlength="3000"><?= e($svc['description']) ?></textarea><small class="help">Leave a blank line between paragraphs.</small></label>
      <label class="field">Key points (one per line)<textarea name="features" rows="5" maxlength="3000"><?= e($svc['features']) ?></textarea></label>
    </div>
  </div>
  <aside class="editor-side">
    <div class="card">
      <label class="inline"><input type="checkbox" name="is_active" value="1"<?= checked($svc['is_active']) ?>> Show on website</label>
      <div class="grid-2">
        <label class="field">Order<input type="number" name="sort_order" value="<?= (int)$svc['sort_order'] ?>"></label>
        <label class="field">Icon<input name="icon" maxlength="3" value="<?= e($svc['icon']) ?>" placeholder="⚙"></label>
      </div>
      <button class="btn" type="submit">Save service</button>
    </div>
    <div class="card">
      <?php image_field('image', $svc['image'], 'Image', 'Landscape, ~1200×800'); ?>
      <label class="field">Image description (alt text)<input name="image_alt" maxlength="200" value="<?= e($svc['image_alt']) ?>"></label>
    </div>
  </aside>
</form>
<?php admin_footer(); ?>
