<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('student/profile.php');
}
requireCsrf('student/profile.php');

$user = getCurrentUser();
$pdo = getDBConnection();
$action = cleanInput($_POST, 'action_type');

if ($action === 'update_contact') {
    $contact = cleanInput($_POST, 'contact_number');

    if (!preg_match(CONTACT_NUMBER_PATTERN, $contact)) {
        setFlash('danger', 'Enter a valid contact number, such as 09171234567.');
        redirect('student/profile.php');
    }

    $pdo->prepare('UPDATE users SET contact_number = ? WHERE id = ?')->execute([$contact, (int) $user['id']]);
    logAudit((int) $user['id'], 'UPDATE_CONTACT', 'user', (int) $user['id'], 'Contact number updated');
    setFlash('success', 'Your contact number has been updated.');
    redirect('student/profile.php');
}

if ($action === 'change_password') {
    $current = rawInput($_POST, 'current_password');
    $new = rawInput($_POST, 'new_password');
    $confirm = rawInput($_POST, 'confirm_password');

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([(int) $user['id']]);
    $hash = (string) $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        setFlash('danger', 'Your current password is incorrect.');
        redirect('student/profile.php');
    }

    $passwordError = validatePassword($new);
    if ($passwordError !== null) {
        setFlash('danger', $passwordError);
        redirect('student/profile.php');
    }

    if ($new !== $confirm) {
        setFlash('danger', 'The new passwords do not match.');
        redirect('student/profile.php');
    }

    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);

    session_regenerate_id(true);
    logAudit((int) $user['id'], 'CHANGE_PASSWORD', 'user', (int) $user['id'], 'Password changed');
    setFlash('success', 'Your password has been changed.');
    redirect('student/profile.php');
}

setFlash('danger', 'That request was not recognised.');
redirect('student/profile.php');
