<?php
require __DIR__ . '/_inc/bootstrap.php';
require_login();
require_post_csrf();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [$_POST['id'] ?? 0]))));
    $ids = array_slice($ids, 0, 200); // sanity cap per request
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM enquiries WHERE id IN ($in)")->execute($ids);
            flash('success', count($ids) . ' enquiry(ies) deleted.');
        } elseif ($action === 'read' || $action === 'unread') {
            $pdo->prepare("UPDATE enquiries SET is_read = ? WHERE id IN ($in)")->execute(array_merge([$action === 'read' ? 1 : 0], $ids));
            flash('success', 'Updated.');
        }
    }
    redirect(admin_self());
}

$filter = ($_GET['filter'] ?? '') === 'unread' ? 'unread' : '';
$q = trim((string)($_GET['q'] ?? ''));
$where = []; $params = [];
if ($filter === 'unread') { $where[] = 'is_read = 0'; }
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    array_push($params, $like, $like, $like);
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM enquiries $whereSql");
$st->execute($params);
$total = (int)$st->fetchColumn();
[$page, $pages, $offset] = paginate($total, 25, $_GET['page'] ?? 1);

$st = $pdo->prepare("SELECT id, name, email, phone, service, LEFT(message, 140) AS preview, is_read, mail_sent, created_at
                     FROM enquiries $whereSql ORDER BY created_at DESC, id DESC LIMIT 25 OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();

admin_header('Enquiries', 'enquiries');
?>
<div class="toolbar">
  <form method="get" class="filters">
    <select name="filter" onchange="this.form.submit()">
      <option value="">All enquiries</option>
      <option value="unread"<?= selected($filter === 'unread') ?>>Unread only</option>
    </select>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone…">
    <button class="btn secondary" type="submit">Filter</button>
  </form>
  <span class="muted small"><?= $total ?> total</span>
</div>

<?php if (!$rows): ?>
<div class="card empty"><p>No enquiries found.</p></div>
<?php else: ?>
<form method="post" id="bulk-form">
  <?= csrf_field() ?>
  <div class="bulk-bar">
    <label class="inline"><input type="checkbox" data-check-all> Select all</label>
    <button class="btn small secondary" name="action" value="read">Mark read</button>
    <button class="btn small secondary" name="action" value="unread">Mark unread</button>
    <button class="btn small danger" name="action" value="delete" data-confirm-click="Delete the selected enquiries?">Delete</button>
  </div>
  <div class="table-wrap card">
  <table class="table">
    <thead><tr><th style="width:36px"></th><th>From</th><th>Message</th><th>Received</th></tr></thead>
    <tbody>
<?php foreach ($rows as $r): ?>
      <tr class="<?= $r['is_read'] ? '' : 'unread' ?>">
        <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" aria-label="Select"></td>
        <td data-label="From">
          <a href="<?= e(url('admin/enquiry.php?id=' . $r['id'])) ?>"><strong><?= e($r['name']) ?></strong></a><br>
          <small class="muted"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></small>
        </td>
        <td data-label="Message">
<?php if ($r['service']): ?><span class="pill"><?= e($r['service']) ?></span> <?php endif; ?>
          <a href="<?= e(url('admin/enquiry.php?id=' . $r['id'])) ?>" class="muted"><?= e(str_limit($r['preview'], 110)) ?></a>
        </td>
        <td data-label="Received"><?= e(format_date($r['created_at'], 'j M Y, g:i a')) ?><?php if (!$r['mail_sent']): ?><br><small class="warn-text" title="The notification email could not be sent">email not sent</small><?php endif; ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
</form>
<?php admin_pagination($page, $pages, array_filter(['filter' => $filter, 'q' => $q])); ?>
<?php endif; ?>
<?php admin_footer(); ?>
