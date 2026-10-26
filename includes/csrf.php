<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

/** Returns the session's CSRF token, creating it if needed. */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Checks a submitted token against the session token in constant time. */
function verifyCsrfToken($token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Returns a hidden form field carrying the CSRF token. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

/** Ends the request with a redirect when the POST token is missing or wrong. */
function requireCsrf(string $redirectPath): void
{
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your form expired or the security check failed. Please try again.');
        redirect($redirectPath);
    }
}
