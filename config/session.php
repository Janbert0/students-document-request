<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/db-session.php';

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_set_save_handler(new DatabaseSessionHandler(getDBConnection()), true);
    session_start();
}

// Expire the session after a period of inactivity.
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
    $_SESSION = [];
}
$_SESSION['last_activity'] = time();

// Rotate the session ID regularly to limit session fixation exposure.
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
