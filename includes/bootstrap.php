<?php
/**
 * Loaded at the top of every page (public, admin and installer).
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/config.php';

date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Asia/Kolkata');

// Error handling: never leak paths/stack traces on the live site.
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
$__logDir = APP_ROOT . '/storage/logs';
if (is_dir($__logDir) && is_writable($__logDir)) {
    ini_set('error_log', $__logDir . '/php-error.log');
}
unset($__logDir);

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

require_once APP_ROOT . '/includes/content.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/html.php';
require_once APP_ROOT . '/includes/mailer.php';
require_once APP_ROOT . '/includes/uploads.php';
