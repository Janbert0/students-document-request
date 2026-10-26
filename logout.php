<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Sign-out is a POST so that it cannot be triggered by a link on another site.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    redirect(isAuthenticated() ? dashboardPathFor(getCurrentUser()['role']) : 'login.php');
}

$user = getCurrentUser();
if ($user) {
    logAudit((int) $user['id'], 'LOGOUT', 'user', (int) $user['id'], 'Signed out');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

redirect('login.php');
