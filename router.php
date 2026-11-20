<?php
/**
 * LOCAL DEVELOPMENT ONLY — emulates the .htaccess rules for PHP's built-in server:
 *     php -S localhost:8000 router.php
 * Not used (and blocked) on Hostinger.
 */
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }

$uri = urldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#^/(includes|storage|admin/_inc)(/|$)#', $uri)
    || preg_match('#(^|/)(config\.php|router\.php|README\.txt|[^/]*\.(sql|lock|log|md))$#', $uri)
    || preg_match('#/\.#', $uri)
    || (strpos($uri, '/uploads/') === 0 && preg_match('#\.(php\d?|phtml|phar|html?|svg|js)$#i', $uri))) {
    http_response_code(403);
    exit('Forbidden');
}

$routes = [
    '#^/blog/?$#'                 => ['blog.php', []],
    '#^/blog/([a-z0-9-]+)/?$#'    => ['post.php', ['slug']],
    '#^/sitemap\.xml$#'           => ['sitemap.php', []],
    '#^/robots\.txt$#'            => ['robots.php', []],
];
foreach ($routes as $pattern => [$script, $params]) {
    if (preg_match($pattern, $uri, $m)) {
        foreach ($params as $i => $name) { $_GET[$name] = $m[$i + 1]; }
        $_SERVER['SCRIPT_NAME'] = '/' . $script;
        require __DIR__ . '/' . $script;
        return true;
    }
}

$path = __DIR__ . $uri;
if (is_file($path) || (is_dir($path) && is_file(rtrim($path, '/') . '/index.php'))) {
    return false; // let the built-in server handle it
}

require __DIR__ . '/404.php';
