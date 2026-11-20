<?php
/**
 * Admin bootstrap: loads the site core, starts the session, enforces login.
 * Define ADMIN_PUBLIC before including to skip the login requirement (login page).
 */
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

const ADMIN_IDLE_TIMEOUT = 7200; // seconds of inactivity before automatic logout (2 h)
// Password of the starter account created by database.sql. Logging in with it forces a password change.
const DEFAULT_ADMIN_PASSWORD = 'ChangeMe@123';

session_boot();
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Referrer-Policy: same-origin');

function admin_setup_error(string $title, string $message): void
{
    http_response_code(503);
    exit('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . '</title>'
       . '<body style="font-family:system-ui,sans-serif;padding:40px 20px;max-width:640px;margin:auto;line-height:1.6">'
       . '<h1>' . e($title) . '</h1><p>' . $message . '</p><p>See <b>README.txt → Setup on Hostinger</b>.</p></body>');
}

if (!is_installed()) {
    admin_setup_error('Database not configured', 'Fill in the <code>DATABASE</code> section of <code>config.php</code> with the MySQL details from Hostinger hPanel, then import <code>database.sql</code> in phpMyAdmin.');
}

function admin_db(): PDO
{
    static $checked = false;
    try {
        $pdo = db();
    } catch (Throwable $e) {
        error_log('[admin] db: ' . $e->getMessage());
        admin_setup_error('Database unavailable', 'The admin panel could not connect to MySQL. Check DB_HOST, DB_NAME, DB_USER and DB_PASS in <code>config.php</code>.');
    }
    if (!$checked) {
        $checked = true;
        try {
            $pdo->query('SELECT 1 FROM users LIMIT 1');
        } catch (Throwable $e) {
            admin_setup_error('Database tables missing', 'Connected to MySQL, but the tables were not found. Open <b>phpMyAdmin</b>, select your database and <b>Import</b> the file <code>database.sql</code>.');
        }
    }
    return $pdo;
}

/** Logged-in user row (loaded once per request), or null. */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) { return $user; }
    $user = null;

    $id = (int)($_SESSION['admin_id'] ?? 0);
    if (!$id) { return null; }

    if (time() - (int)($_SESSION['admin_seen'] ?? 0) > ADMIN_IDLE_TIMEOUT) {
        admin_logout();
        flash('info', 'You were logged out after a period of inactivity.');
        return null;
    }

    $st = admin_db()->prepare('SELECT id, name, email, password_hash, last_login_at FROM users WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    // A password change elsewhere invalidates every other session.
    if (!$row || !hash_equals((string)($_SESSION['admin_sig'] ?? ''), admin_session_sig($row['password_hash']))) {
        admin_logout();
        return null;
    }
    $_SESSION['admin_seen'] = time();
    return $user = $row;
}

function admin_session_sig(string $hash): string
{
    return substr(hash('sha256', $hash), 0, 24);
}

function admin_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$user['id'];
    $_SESSION['admin_sig'] = admin_session_sig($user['password_hash']);
    $_SESSION['admin_seen'] = time();
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

function admin_logout(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_sig'], $_SESSION['admin_seen']);
    session_regenerate_id(true);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $next = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_SERVER['REQUEST_URI'] ?? '') : '';
        redirect('admin/login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''), 302);
    }
    // Starter password still in use → only the account page is reachable until it is changed.
    if (!empty($_SESSION['must_change_pw']) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'account.php') {
        flash('error', 'For security, please set a new password before using the admin panel.');
        redirect('admin/account.php', 302);
    }
    return $user;
}

/** Reject POSTs without a valid CSRF token. */
function require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }
    // When an upload exceeds post_max_size PHP silently drops the whole form.
    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('error', 'The upload was too large for the server (max ' . upload_limit_label() . '). Please use a smaller image.');
        redirect(admin_self(), 303);
    }
    if (!csrf_verify()) {
        flash('error', 'Your session expired or the form was submitted twice. Please try again.');
        redirect(admin_self(), 303);
    }
}

function admin_self(): string
{
    // Same admin page, same query string.
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    return 'admin/' . $script . ($qs !== '' ? '?' . $qs : '');
}

function unread_enquiries(): int
{
    static $n = null;
    if ($n === null) {
        $n = (int)admin_db()->query('SELECT COUNT(*) FROM enquiries WHERE is_read = 0')->fetchColumn();
    }
    return $n;
}

function admin_header(string $title, string $active = ''): void
{
    $user = current_user();
    $nav = [
        'dashboard' => ['Dashboard', 'admin/index.php', '▦'],
        'posts'     => ['Blog posts', 'admin/posts.php', '✎'],
        'services'  => ['Services', 'admin/services.php', '⚙'],
        'gallery'   => ['Gallery', 'admin/gallery.php', '▣'],
        'enquiries' => ['Enquiries', 'admin/enquiries.php', '✉'],
        'settings'  => ['Site settings', 'admin/settings.php', '☰'],
        'users'     => ['Admin users', 'admin/users.php', '☺'],
    ];
    $unread = $user ? unread_enquiries() : 0;
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title) ?> — Admin · <?= e(setting('site_name', SITE_NAME)) ?></title>
<link rel="icon" href="<?= e(asset('assets/images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('admin/assets/admin.css')) ?>">
</head>
<body class="admin">
<header class="admin-topbar">
  <button class="sidebar-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="admin-sidebar">☰</button>
  <a class="admin-brand" href="<?= e(url('admin/index.php')) ?>"><img src="<?= e(asset('assets/images/star-infra-logo.svg')) ?>" alt="" width="150" height="31"><span>Admin</span></a>
  <div class="admin-topbar-right">
    <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">View site ↗</a>
    <a href="<?= e(url('admin/account.php')) ?>" class="hide-sm"><?= e($user['name'] ?? '') ?></a>
  </div>
</header>
<div class="admin-shell">
  <nav class="admin-sidebar" id="admin-sidebar" aria-label="Admin">
<?php foreach ($nav as $key => [$label, $href, $icon]): ?>
    <a href="<?= e(url($href)) ?>" class="<?= $active === $key ? 'active' : '' ?>"><span class="ico" aria-hidden="true"><?= $icon ?></span><?= e($label) ?><?php if ($key === 'enquiries' && $unread): ?><b class="badge"><?= $unread ?></b><?php endif; ?></a>
<?php endforeach; ?>
    <hr>
    <a href="<?= e(url('admin/account.php')) ?>" class="<?= $active === 'account' ? 'active' : '' ?>"><span class="ico" aria-hidden="true">⚿</span>My account</a>
    <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button type="submit"><span class="ico" aria-hidden="true">⏻</span>Log out</button></form>
  </nav>
  <main class="admin-main">
    <h1 class="page-title"><?= e($title) ?></h1>
<?php foreach (flashes() as $f): ?>
    <div class="alert <?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
<?php
}

function admin_footer(string $extra = ''): void
{
    ?>
  </main>
</div>
<script src="<?= e(asset('admin/assets/admin.js')) ?>" defer></script>
<?= $extra ?>
</body>
</html>
<?php
}

/** Small helpers for forms */
function checked($cond): string { return $cond ? ' checked' : ''; }
function selected($cond): string { return $cond ? ' selected' : ''; }

/** Render the image field (preview + file input + remove checkbox). */
function image_field(string $name, ?string $current, string $label = 'Image', string $help = ''): void
{
    ?>
    <div class="field image-field">
      <span class="label"><?= e($label) ?></span>
      <div class="image-preview"<?= $current ? '' : ' hidden' ?>><img src="<?= $current ? e(image_url($current)) : '' ?>" alt=""></div>
      <input type="file" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp,image/gif" data-preview>
<?php if ($current): ?>
      <label class="inline"><input type="checkbox" name="<?= e($name) ?>_remove" value="1"> Remove current image</label>
<?php endif; ?>
      <small class="help">JPG, PNG, WEBP or GIF · max <?= e(upload_limit_label()) ?><?= $help !== '' ? ' · ' . e($help) : '' ?></small>
    </div>
<?php
}

/**
 * Process an image_field() submission.
 * Returns [newPath, error]. newPath is the value to store (may be the unchanged current path).
 */
function process_image_field(string $name, ?string $current): array
{
    $error = null;
    $uploaded = handle_image_upload($_FILES[$name] ?? null, $error);
    if ($error) { return [$current, $error]; }
    if ($uploaded) {
        if ($current) { delete_upload($current); }
        return [$uploaded, null];
    }
    if (!empty($_POST[$name . '_remove'])) {
        if ($current) { delete_upload($current); }
        return [null, null];
    }
    return [$current, null];
}

/** Generate a slug that is unique in $table (one query, no loops against the DB). */
function unique_slug(PDO $pdo, string $table, string $wanted, int $excludeId = 0): string
{
    if (!in_array($table, ['posts', 'services'], true)) { throw new InvalidArgumentException('Bad table'); }
    $base = slugify($wanted);
    if ($base === '') { $base = $table === 'posts' ? 'post' : 'service'; }
    $base = substr($base, 0, 170);
    $st = $pdo->prepare("SELECT slug FROM {$table} WHERE (slug = ? OR slug LIKE ?) AND id <> ?");
    $st->execute([$base, $base . '-%', $excludeId]);
    $taken = array_flip($st->fetchAll(PDO::FETCH_COLUMN));
    if (!isset($taken[$base])) { return $base; }
    for ($i = 2; isset($taken[$base . '-' . $i]); $i++) { /* find first free suffix */ }
    return $base . '-' . $i;
}

/** Admin pagination links. */
function admin_pagination(int $page, int $pages, array $query = []): void
{
    if ($pages <= 1) { return; }
    echo '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($i !== 1 && $i !== $pages && abs($i - $page) > 2) {
            if ($i === 2 || $i === $pages - 1) { echo '<span>…</span>'; }
            continue;
        }
        $q = http_build_query(array_merge($query, ['page' => $i]));
        echo '<a href="?' . e($q) . '"' . ($i === $page ? ' class="active"' : '') . '>' . $i . '</a>';
    }
    echo '</nav>';
}
