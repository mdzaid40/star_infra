<?php
require __DIR__ . '/_inc/bootstrap.php';
$me = require_login();
require_post_csrf();
$pdo = admin_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $currentPassword = (string)($_POST['current_password'] ?? '');

    if (!password_verify($currentPassword, $me['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    } elseif ($action === 'profile') {
        $name = input('name', 100);
        $email = strtolower(input('email', 190));
        if ($name === '') { $errors[] = 'Name cannot be empty.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email.'; }
        if (!$errors) {
            $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
            $st->execute([$email, $me['id']]);
            if ($st->fetchColumn()) { $errors[] = 'Another admin already uses this email.'; }
        }
        if (!$errors) {
            $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')->execute([$name, $email, $me['id']]);
            flash('success', 'Profile updated.');
            redirect('admin/account.php');
        }
    } elseif ($action === 'password') {
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if (strlen($new) < 10) { $errors[] = 'New password must be at least 10 characters.'; }
        if ($new !== $confirm) { $errors[] = 'New passwords do not match.'; }
        if (hash_equals(DEFAULT_ADMIN_PASSWORD, $new)) { $errors[] = 'Please choose a password different from the default one.'; }
        if (!$errors) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $me['id']]);
            $me['password_hash'] = $hash;
            admin_login($me); // new session id + signature; other sessions are signed out
            unset($_SESSION['must_change_pw']);
            flash('success', 'Password changed. Any other logged-in sessions have been signed out.');
            redirect('admin/account.php');
        }
    }
}

admin_header('My account', 'account');
?>
<?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>
<div class="two-col">
  <form method="post" class="card stack">
    <h2>Profile</h2>
    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <label class="field">Name<input name="name" required maxlength="100" value="<?= e($me['name']) ?>"></label>
    <label class="field">Email (login)<input name="email" type="email" required maxlength="190" value="<?= e($me['email']) ?>"></label>
    <label class="field">Current password<input name="current_password" type="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Save profile</button>
  </form>
  <form method="post" class="card stack">
    <h2>Change password</h2>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <label class="field">Current password<input name="current_password" type="password" required autocomplete="current-password"></label>
    <label class="field">New password (min 10 characters)<input name="new_password" type="password" required minlength="10" autocomplete="new-password"></label>
    <label class="field">Confirm new password<input name="confirm_password" type="password" required minlength="10" autocomplete="new-password"></label>
    <button class="btn" type="submit">Change password</button>
  </form>
</div>
<?php admin_footer(); ?>
