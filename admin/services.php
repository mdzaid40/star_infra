<?php
require __DIR__ . '/_inc/bootstrap.php';
require_login();
require_post_csrf();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'reorder' && is_array($_POST['order'] ?? null)) {
        // Single UPDATE … CASE statement instead of one query per row.
        $cases = []; $params = []; $ids = [];
        foreach ($_POST['order'] as $sid => $pos) {
            $sid = (int)$sid;
            if ($sid > 0) { $cases[] = 'WHEN ? THEN ?'; array_push($params, $sid, (int)$pos); $ids[] = $sid; }
        }
        if ($ids) {
            $sql = 'UPDATE services SET sort_order = CASE id ' . implode(' ', $cases) . ' END WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $pdo->prepare($sql)->execute(array_merge($params, $ids));
            flash('success', 'Order saved.');
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT id, title, image, is_active FROM services WHERE id = ?');
        $st->execute([$id]);
        $svc = $st->fetch();
        if (!$svc) {
            flash('error', 'Service not found.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
            delete_upload($svc['image']);
            flash('success', 'Deleted “' . $svc['title'] . '”.');
        } elseif ($action === 'toggle') {
            $pdo->prepare('UPDATE services SET is_active = 1 - is_active, updated_at = ? WHERE id = ?')->execute([now(), $id]);
            flash('success', '“' . $svc['title'] . '” ' . ($svc['is_active'] ? 'hidden from' : 'shown on') . ' the website.');
        }
    }
    redirect('admin/services.php');
}

$services = $pdo->query('SELECT id, title, slug, category, image, sort_order, is_active FROM services ORDER BY sort_order ASC, id ASC')->fetchAll();

admin_header('Services', 'services');
?>
<div class="toolbar">
  <a class="btn" href="<?= e(url('admin/service-edit.php')) ?>">+ Add service</a>
  <p class="muted small">Services appear on the homepage, the Services page, the footer and the contact form. Lower order numbers appear first.</p>
</div>

<?php if (!$services): ?>
<div class="card empty"><p>No services yet.</p></div>
<?php else: ?>
<form method="post" id="reorder-form"><?= csrf_field() ?><input type="hidden" name="action" value="reorder"></form>
<div class="table-wrap card">
<table class="table">
  <thead><tr><th style="width:90px">Order</th><th>Service</th><th class="hide-sm">Anchor</th><th>Status</th><th class="actions-col">Actions</th></tr></thead>
  <tbody>
<?php foreach ($services as $s): ?>
    <tr>
      <td data-label="Order"><input form="reorder-form" type="number" name="order[<?= (int)$s['id'] ?>]" value="<?= (int)$s['sort_order'] ?>" class="input-sm" aria-label="Order for <?= e($s['title']) ?>"></td>
      <td data-label="Service">
        <div class="title-cell">
<?php if ($s['image']): ?><img src="<?= e(image_url($s['image'])) ?>" alt="" width="56" height="38" loading="lazy"><?php endif; ?>
          <div><a href="<?= e(url('admin/service-edit.php?id=' . $s['id'])) ?>"><strong><?= e($s['title']) ?></strong></a><br><small class="muted"><?= e($s['category']) ?></small></div>
        </div>
      </td>
      <td data-label="Anchor" class="hide-sm"><code>#<?= e($s['slug']) ?></code></td>
      <td data-label="Status"><span class="pill <?= $s['is_active'] ? 'published' : 'draft' ?>"><?= $s['is_active'] ? 'visible' : 'hidden' ?></span></td>
      <td class="actions">
        <a class="btn small secondary" href="<?= e(url('admin/service-edit.php?id=' . $s['id'])) ?>">Edit</a>
        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn small secondary" type="submit"><?= $s['is_active'] ? 'Hide' : 'Show' ?></button></form>
        <form method="post" class="inline-form" data-confirm="Delete this service permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn small danger" type="submit">Delete</button></form>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<p><button class="btn secondary" type="submit" form="reorder-form">Save order</button></p>
<?php endif; ?>
<?php admin_footer(); ?>
