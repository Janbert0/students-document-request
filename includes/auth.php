<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

/** Returns the home page for a role. */
function dashboardPathFor(string $role): string
{
    switch ($role) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'registrar':
            return 'registrar/dashboard.php';
        default:
            return 'student/dashboard.php';
    }
}

/**
 * Returns the signed-in user or null. The record is read from the database on each
 * request, so deactivating an account or changing a role takes effect immediately.
 */
function getCurrentUser(): ?array
{
    static $resolved = false;
    static $user = null;

    if ($resolved) {
        return $user;
    }
    $resolved = true;

    $userId = $_SESSION['user_id'] ?? null;
    if (empty($userId)) {
        return null;
    }

    $stmt = getDBConnection()->prepare(
        'SELECT id, student_number, first_name, middle_name, last_name, email, role,
                department, course, contact_number, account_status
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([(int) $userId]);
    $row = $stmt->fetch();

    if (!$row || $row['account_status'] !== 'active') {
        $_SESSION = [];
        return null;
    }

    $user = $row;
    return $user;
}

function isAuthenticated(): bool
{
    return getCurrentUser() !== null;
}

/** Redirects to the sign-in page when no user is signed in. */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        setFlash('danger', 'Please sign in to continue.');
        redirect('login.php');
    }
}

/** Allows access only to the listed roles. Other users go back to their own home page. */
function requireRole(array $roles): void
{
    requireAuth();
    $user = getCurrentUser();

    if (!in_array($user['role'], $roles, true)) {
        setFlash('danger', 'You do not have permission to open that page.');
        redirect(dashboardPathFor($user['role']));
    }
}
