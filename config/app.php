<?php
require_once __DIR__ . '/env.php';

define('APP_NAME', env('APP_NAME', 'Student Document Request System'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', APP_ENV === 'local');

/**
 * On Vercel, APP_URL may be left unset: build the address from the incoming request
 * so redirects and asset links point at the live site instead of localhost.
 */
$configuredUrl = trim(env('APP_URL', ''));
if ($configuredUrl === '' && getenv('VERCEL') !== false && !empty($_SERVER['HTTP_HOST'])) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $configuredUrl = ($isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
}
define('BASE_URL', rtrim($configuredUrl !== '' ? $configuredUrl : 'http://localhost/college-document-request', '/'));

define('APP_PATH', dirname(__DIR__));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', '7200'));
define('MAX_COPIES', 5);

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
