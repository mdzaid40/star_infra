<?php
require __DIR__ . '/_inc/bootstrap.php';
require_login();
$pdo = admin_db();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM enquiries WHERE id = ?');
$st->execute([$id]);
$q = $st->fetch();
if (!$q) { flash('error', 'Enquiry not found.'); redirect('admin/enquiries.php'); }

if (!$q['is_read']) {
    $pdo->prepare('UPDATE enquiries SET is_read = 1 WHERE id = ?')->execute([$id]);
}

$replySubject = 'Re: Your enquiry' . ($q['service'] ? ' – ' . $q['service'] : '');

admin_header('Enquiry from ' . $q['name'], 'enquiries');
?>
<p><a href="<?= e(url('admin/enquiries.php')) ?>">← All enquiries</a></p>
<div class="two-col wide-left">
  <section class="card">
    <h2>Message</h2>
    <div class="message-body"><?= nl2br(e($q['message']), false) ?></div>
  </section>
  <aside class="card">
    <dl class="details">
      <dt>Name</dt><dd><?= e($q['name']) ?></dd>
      <dt>Email</dt><dd><a href="mailto:<?= e($q['email']) ?>"><?= e($q['email']) ?></a></dd>
      <dt>Phone</dt><dd><?= $q['phone'] ? '<a href="' . e(phone_link($q['phone'])) . '">' . e($q['phone']) . '</a>' : '—' ?></dd>
      <dt>Service</dt><dd><?= e($q['service'] ?: '—') ?></dd>
      <dt>Received</dt><dd><?= e(format_date($q['created_at'], 'j M Y, g:i a')) ?></dd>
      <dt>Email notification</dt><dd><?= $q['mail_sent'] ? 'Sent' : '<span class="warn-text">Not sent</span>' ?></dd>
      <dt>IP address</dt><dd><?= e($q['ip'] ?: '—') ?></dd>
    </dl>
    <div class="side-actions">
      <a class="btn" href="mailto:<?= e($q['email']) ?>?subject=<?= rawurlencode($replySubject) ?>">Reply by email</a>
<?php if ($q['phone']): ?>
      <a class="btn secondary" href="<?= e(whatsapp_link($q['phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a>
<?php endif; ?>
    </div>
    <form method="post" action="<?= e(url('admin/enquiries.php')) ?>" class="side-actions">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
      <button class="btn small secondary" name="action" value="unread">Mark unread</button>
      <button class="btn small danger" name="action" value="delete" data-confirm-click="Delete this enquiry?">Delete</button>
    </form>
  </aside>
</div>
<?php admin_footer(); ?>
