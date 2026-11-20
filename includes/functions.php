<?php
if (!defined('APP_ROOT')) { http_response_code(403); exit; }

/* =================================================================
 * Output helpers
 * ================================================================= */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape text, then turn *word* into <em>word</em> and new lines into <br>. */
function rich($text): string
{
    $html = e(trim((string)$text));
    $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);
    return nl2br($html, false);
}

/** Escape text and wrap each blank-line separated block in <p>. */
function paragraphs($text, string $class = ''): string
{
    $blocks = preg_split('/\R\s*\R/', trim((string)$text)) ?: [];
    $attr = $class !== '' ? ' class="' . e($class) . '"' : '';
    $out = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block !== '') { $out .= '<p' . $attr . '>' . nl2br(e($block), false) . '</p>'; }
    }
    return $out;
}

function str_limit(string $text, int $limit = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if (mb_strlen($text) <= $limit) { return $text; }
    $cut = mb_substr($text, 0, $limit);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $limit * 0.6) { $cut = mb_substr($cut, 0, $space); }
    return rtrim($cut, " ,.;:-") . '…';
}

function format_date(?string $datetime, string $format = 'j M Y'): string
{
    if (!$datetime) { return ''; }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($t !== false) { $text = $t; }
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
    $text = trim($text, '-');
    return substr($text, 0, 180);
}

/* =================================================================
 * URLs
 * ================================================================= */

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') { return true; }
    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') { return true; }
    if (strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') { return true; }
    return (string)($_SERVER['SERVER_PORT'] ?? '') === '443';
}

/** Folder the site lives in, relative to the domain ("" when installed at the root). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) { return $base; }

    if (SITE_URL !== '') {
        return $base = rtrim((string)parse_url(SITE_URL, PHP_URL_PATH), '/');
    }

    $app = str_replace('\\', '/', realpath(APP_ROOT) ?: APP_ROOT);
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    if ($docRoot !== '' && strpos($app . '/', rtrim($docRoot, '/') . '/') === 0) {
        return $base = rtrim(substr($app, strlen(rtrim($docRoot, '/'))), '/');
    }

    // Fallback: derive from the running script.
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $file = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    if ($file !== '' && strpos($file, $app) === 0) {
        $rel = substr($file, strlen($app));
        if ($rel !== '' && substr($script, -strlen($rel)) === $rel) {
            return $base = rtrim(substr($script, 0, -strlen($rel)), '/');
        }
    }
    return $base = '';
}

/** Scheme + host, e.g. https://www.example.com */
function site_origin(): string
{
    if (SITE_URL !== '') {
        $p = parse_url(SITE_URL);
        return ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
    }
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $host); // guard against header injection
    return (is_https() ? 'https' : 'http') . '://' . $host;
}

/** Root-relative URL to a path inside the site. */
function url(string $path = ''): string
{
    if (preg_match('#^(https?:)?//#i', $path) || strpos($path, 'mailto:') === 0 || strpos($path, 'tel:') === 0) {
        return $path;
    }
    return base_path() . '/' . ltrim($path, '/');
}

/**
 * Absolute URL. Accepts a site-relative path ("about.php"), a root-relative
 * URL already produced by url()/post_url() ("/blog/x"), or a full URL.
 */
function abs_url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) { return $path; }
    if (strpos($path, '//') === 0) { return (is_https() ? 'https:' : 'http:') . $path; }
    if (strpos($path, '/') === 0) { return site_origin() . $path; }
    return site_origin() . url($path);
}

/** Static asset URL with cache-busting version. */
function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

/** URL for a stored image path (local path or full URL). */
function image_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') { return ''; }
    return url($path);
}

function post_url(string $slug): string
{
    return PRETTY_URLS ? url('blog/' . rawurlencode($slug)) : url('post.php?slug=' . rawurlencode($slug));
}

function blog_url(int $page = 1): string
{
    $base = PRETTY_URLS ? url('blog') : url('blog.php');
    return $page > 1 ? $base . '?page=' . $page : $base;
}

function redirect(string $path, int $status = 303): void
{
    $location = preg_match('#^https?://#i', $path) ? $path : url($path);
    header('Location: ' . $location, true, $status);
    exit;
}

function phone_link(string $phone): string
{
    $digits = preg_replace('/[^\d+]/', '', $phone);
    return 'tel:' . $digits;
}

function whatsapp_link(string $number, string $text = ''): string
{
    $digits = ltrim(preg_replace('/\D/', '', $number), '0');
    if (strlen($digits) === 10) { $digits = '91' . $digits; } // Indian mobile without country code
    return 'https://wa.me/' . $digits . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/* =================================================================
 * Database
 * ================================================================= */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, (int)DB_PORT, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
    // Keep MySQL's clock in step with PHP's so NOW()-style comparisons agree.
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    return $pdo;
}

/** Returns a connection, or null if the database is unreachable (logged once). */
function db_try(): ?PDO
{
    static $failed = false;
    if ($failed) { return null; }
    try {
        return db();
    } catch (Throwable $e) {
        $failed = true;
        error_log('[db] connection failed: ' . $e->getMessage());
        return null;
    }
}

/** True once the DATABASE section of config.php has been filled in. */
function is_installed(): bool
{
    return DB_NAME !== '' && strpos(DB_NAME, 'u000000000') !== 0 && DB_PASS !== 'change-me';
}

/* =================================================================
 * Content (memoised: each query runs at most once per request)
 * ================================================================= */

function settings(): array
{
    static $settings = null;
    if ($settings !== null) { return $settings; }

    $settings = settings_defaults();
    $pdo = is_installed() ? db_try() : null;
    if ($pdo) {
        try {
            foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
                $settings[$row['setting_key']] = (string)$row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log('[settings] ' . $e->getMessage());
        }
    }
    return $settings;
}

function setting(string $key, string $fallback = ''): string
{
    $s = settings();
    $value = $s[$key] ?? '';
    return $value !== '' ? $value : $fallback;
}

function services(): array
{
    static $services = null;
    if ($services !== null) { return $services; }

    $pdo = is_installed() ? db_try() : null;
    if ($pdo) {
        try {
            $services = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
            return $services;
        } catch (Throwable $e) {
            error_log('[services] ' . $e->getMessage());
        }
    }
    $services = [];
    foreach (default_services() as $i => $s) {
        $services[] = $s + ['id' => $i + 1, 'sort_order' => $i + 1, 'is_active' => 1];
    }
    return $services;
}

function gallery_items(): array
{
    static $items = null;
    if ($items !== null) { return $items; }

    $pdo = is_installed() ? db_try() : null;
    if ($pdo) {
        try {
            $items = $pdo->query('SELECT * FROM gallery WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
            return $items;
        } catch (Throwable $e) {
            error_log('[gallery] ' . $e->getMessage());
        }
    }
    return $items = default_gallery();
}

function service_features(?string $features): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$features) ?: [])));
}

/** Published posts (newest first) with author name joined in — no per-row queries. */
function published_posts(int $limit, int $offset = 0, ?int $excludeId = null): array
{
    $pdo = is_installed() ? db_try() : null;
    if (!$pdo) { return []; }
    try {
        $sql = 'SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.published_at, u.name AS author_name
                FROM posts p LEFT JOIN users u ON u.id = p.author_id
                WHERE p.status = \'published\' AND p.published_at <= :now'
             . ($excludeId ? ' AND p.id <> :exclude' : '')
             . ' ORDER BY p.published_at DESC, p.id DESC LIMIT :limit OFFSET :offset';
        $st = $pdo->prepare($sql);
        $st->bindValue(':now', now());
        if ($excludeId) { $st->bindValue(':exclude', $excludeId, PDO::PARAM_INT); }
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    } catch (Throwable $e) {
        error_log('[posts] ' . $e->getMessage());
        return [];
    }
}

function published_posts_count(): int
{
    $pdo = is_installed() ? db_try() : null;
    if (!$pdo) { return 0; }
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= ?");
        $st->execute([now()]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        error_log('[posts] ' . $e->getMessage());
        return 0;
    }
}

function post_excerpt(array $post, int $limit = 160): string
{
    $excerpt = trim((string)($post['excerpt'] ?? ''));
    if ($excerpt !== '') { return $excerpt; }
    return str_limit(html_entity_decode(strip_tags((string)($post['content'] ?? '')), ENT_QUOTES, 'UTF-8'), $limit);
}

function reading_time(string $html): int
{
    $words = str_word_count(strip_tags($html));
    return max(1, (int)ceil($words / 200));
}

/* =================================================================
 * Sessions, CSRF & flash messages
 * ================================================================= */

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    session_name('sie_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (base_path() ?: '') . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    session_boot();
    $sent = (string)($_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return !empty($_SESSION['_csrf']) && $sent !== '' && hash_equals($_SESSION['_csrf'], $sent);
}

function flash(string $type, string $message): void
{
    session_boot();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) { return []; }
    $out = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $out;
}

function client_ip(): string
{
    // REMOTE_ADDR is the only value that cannot be spoofed by the visitor.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/* =================================================================
 * Input helpers
 * ================================================================= */

function input(string $key, int $maxLength = 0): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) { return ''; }
    // Normalise line endings and strip invisible control characters (keep \n and \t).
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    $v = trim($v);
    if ($maxLength > 0 && mb_strlen($v) > $maxLength) { $v = mb_substr($v, 0, $maxLength); }
    return $v;
}

function is_valid_http_url(string $url): bool
{
    return $url === '' || (filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url));
}

/** Simple paginator: returns [page, totalPages, offset]. */
function paginate(int $total, int $perPage, $requested): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($pages, (int)$requested));
    return [$page, $pages, ($page - 1) * $perPage];
}
