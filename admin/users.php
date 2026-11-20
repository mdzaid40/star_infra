<?php
require __DIR__ . '/_inc/bootstrap.php';
$me = require_login();
require_post_csrf();
$pdo = admin_db();

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $old['name'] = input('name', 100);
        $old['email'] = strtolower(input('email', 190));
        $password = (string)($_POST['password'] ?? '');
        if ($old['name'] === '') { $errors[] = 'Enter a name.'; }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email.'; }
        if (strlen($password) < 10) { $errors[] = 'Password must be at least 10 characters.'; }
        if (hash_equals(DEFAULT_ADMIN_PASSWORD, $password)) { $errors[] = 'Please do not use the default password.'; }
        if (!$errors) {
            $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
            $st->execute([$old['email']]);
            if ($st->fetchColumn()) { $errors[] = 'An admin with this email already exists.'; }
        }
        if (!$errors) {
            $pdo->prepare('INSERT INTO users (name, email, password_hash, created_at) VALUES (?,?,?,?)')
                ->execute([$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT), now()]);
            flash('success', 'Admin user ' . $old['email'] . ' created. Share the password with them securely.');
            redirect('admin/users.php');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($id === (int)$me['id']) {
            flash('error', 'You cannot delete your own account.');
        } elseif ($count <= 1) {
            flash('error', 'At least one admin account is required.');
        } else {
            // Posts keep existing; their author becomes empty.
            $pdo->prepare('UPDATE posts SET author_id = NULL WHERE author_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('success', 'Admin user deleted.');
        }
        redirect('admin/users.php');
    } elseif ($action === 'reset') {
        $id = (int)($_POST['id'] ?? 0);
        $password = (string)($_POST['new_password'] ?? '');
        if (strlen($password) < 10 || hash_equals(DEFAULT_ADMIN_PASSWORD, $password)) {
            flash('error', 'New password must be at least 10 characters and not the default password.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            flash('success', 'Password updated. That user has been signed out everywhere.');
            if ($id === (int)$me['id']) {
                // Keep the current admin logged in after changing their own password.
                $st = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([$id]);
                admin_login($st->fetch());
                unset($_SESSION['must_change_pw']);
            }
        }
        redirect('admin/users.php');
    }
}

$users = $pdo->query('SELECT id, name, email, last_login_at, created_at FROM users ORDER BY name')->fetchAll();

admin_header('Admin users', 'users');
?>
<?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>
<div class="two-col wide-left">
  <section class="card">
    <h2>Users who can access this admin panel</h2>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Name</th><th>Last login</th><th class="actions-col">Actions</th></tr></thead>
      <tbody>
<?php foreach ($users as $u): ?>
        <tr>
          <td data-label="Name"><strong><?= e($u['name']) ?></strong><?= (int)$u['id'] === (int)$me['id'] ? ' <span class="pill">you</span>' : '' ?><br><small class="muted"><?= e($u['email']) ?></small></td>
          <td data-label="Last login"><?= $u['last_login_at'] ? e(format_date($u['last_login_at'], 'j M Y, g:i a')) : '<span class="muted">never</span>' ?></td>
          <td class="actions">
            <details class="inline-details">
              <summary class="btn small secondary">Reset password</summary>
              <form method="post" class="stack">
                <?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <input type="password" name="new_password" minlength="10" required placeholder="New password (min 10)" autocomplete="new-password">
                <button class="btn small" type="submit">Set password</button>
              </form>
            </details>
<?php if ((int)$u['id'] !== (int)$me['id']): ?>
            <form method="post" class="inline-form" data-confirm="Remove admin access for <?= e($u['email']) ?>?">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn small danger" type="submit">Delete</button>
            </form>
<?php endif; ?>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </section>
  <aside class="card">
    <h2>Add admin</h2>
    <form method="post" class="stack">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <label class="field">Name<input name="name" required maxlength="100" value="<?= e($old['name']) ?>"></label>
      <label class="field">Email<input name="email" type="email" required maxlength="190" value="<?= e($old['email']) ?>"></label>
      <label class="field">Password<input name="password" type="password" required minlength="10" autocomplete="new-password"></label>
      <button class="btn" type="submit">Create admin</button>
    </form>
  </aside>
</div>
<?php admin_footer(); ?>
