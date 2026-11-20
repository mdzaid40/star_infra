<?php
require __DIR__ . '/_inc/bootstrap.php';

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_MIN   = 15;

if (current_user()) { redirect('admin/index.php', 302); }

$pdo   = admin_db();
$error = null;
$email = '';
$ip    = client_ip();

/** Only allow redirects back into the admin area of this site. */
$next = (string)($_GET['next'] ?? $_POST['next'] ?? '');
$adminPrefix = base_path() . '/admin/';
if ($next === '' || strpos($next, $adminPrefix) !== 0 || preg_match('#[\r\n]|//|\\\\#', substr($next, 1))) {
    $next = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(input('email', 190));
    $password = (string)($_POST['password'] ?? '');

    $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?');
    $st->execute([$ip, date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MIN * 60)]);
    $attempts = (int)$st->fetchColumn();

    if (!csrf_verify()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($attempts >= LOGIN_MAX_ATTEMPTS) {
        $error = 'Too many failed attempts. Please wait ' . LOGIN_WINDOW_MIN . ' minutes and try again.';
    } else {
        $st = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();

        // Always run exactly one password_verify so timing doesn't reveal whether the email exists.
        $hash  = $user['password_hash'] ?? '$2y$12$ANwI3cYPkewEkuhK.KB/D.HEubHcgArMXGxEwcCOppSUcHOgA2zQe';
        $valid = password_verify($password, $hash);
        if ($user && $valid) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$user['password_hash'], $user['id']]);
            }
            $pdo->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([now(), $user['id']]);
            $pdo->prepare('DELETE FROM login_attempts WHERE ip = ? OR attempted_at < ?')->execute([$ip, date('Y-m-d H:i:s', time() - 86400)]);
            admin_login($user);
            if (hash_equals(DEFAULT_ADMIN_PASSWORD, $password)) {
                $_SESSION['must_change_pw'] = 1;
                flash('error', 'You are using the default password. Please change your email and password now.');
                redirect('admin/account.php');
            }
            redirect($next !== '' ? site_origin() . $next : 'admin/index.php');
        }

        $pdo->prepare('INSERT INTO login_attempts (ip, email, attempted_at) VALUES (?,?,?)')->execute([$ip, $email, now()]);
        $left = LOGIN_MAX_ATTEMPTS - $attempts - 1;
        $error = 'Incorrect email or password.' . ($left > 0 && $left <= 2 ? " {$left} attempt(s) left." : '');
    }
}
$messages = flashes();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin login — <?= e(setting('site_name', SITE_NAME)) ?></title>
<link rel="icon" href="<?= e(asset('assets/images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('admin/assets/admin.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-card">
  <img src="<?= e(asset('assets/images/star-infra-logo.svg')) ?>" alt="<?= e(setting('site_name', SITE_NAME)) ?>" class="auth-logo" width="240" height="50">
  <h1>Admin login</h1>
<?php foreach ($messages as $m): ?>
  <div class="alert <?= e($m['type']) ?>"><?= e($m['message']) ?></div>
<?php endforeach; ?>
<?php if ($error): ?>
  <div class="alert error" role="alert"><?= e($error) ?></div>
<?php endif; ?>
  <form method="post" class="stack" action="<?= e(url('admin/login.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Email<input type="email" name="email" required autocomplete="username" autofocus value="<?= e($email) ?>"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p class="muted small">Forgot your password? Ask another admin to reset it, or see README.txt → "Reset admin password".</p>
  <p class="small"><a href="<?= e(url('')) ?>">← Back to website</a></p>
</main>
</body>
</html>
