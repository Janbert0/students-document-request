<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/workflow.php';

/** Escapes a value for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Returns a value from an input array, escaped for use inside an HTML attribute. */
function attr(array $source, string $key): string
{
    return e(rawInput($source, $key));
}

/** Returns an input value as a string, or an empty string if it is missing or not scalar. */
function rawInput(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? $value : '';
}

/** Returns an input value as a trimmed string. */
function cleanInput(array $source, string $key): string
{
    return trim(rawInput($source, $key));
}

/** Converts an empty string to null, for optional database columns. */
function nullIfBlank(string $value): ?string
{
    return $value === '' ? null : $value;
}

/** Sends the browser to a path inside the application and stops the script. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/** Stores a one-time message to show on the next page. */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Keeps submitted form values so a failed form can be refilled after redirecting.
 * Passwords and tokens are never stored.
 */
function setOldInput(array $data): void
{
    $excluded = ['csrf_token', 'password', 'confirm_password', 'current_password', 'new_password'];
    $_SESSION['old_input'] = array_filter(array_diff_key($data, array_flip($excluded)), 'is_string');
}

/** Returns a value kept by setOldInput(), consumed on first read. */
function old(string $key, string $default = ''): string
{
    static $input = null;

    if ($input === null) {
        $input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
    }

    return isset($input[$key]) && is_string($input[$key]) ? $input[$key] : $default;
}

/** Renders a field-level validation message, or nothing when the field is valid. */
function formError(array $errors, string $field): string
{
    if (empty($errors[$field])) {
        return '';
    }
    return '<p class="form-error">' . e($errors[$field]) . '</p>';
}

/** Builds a LIKE pattern for a search term, escaping wildcard characters. */
function likeTerm(string $term): string
{
    return '%' . addcslashes($term, '%_\\') . '%';
}

/** Creates a reference number such as REQ-20261012-3FA9C2 (19 characters). */
function generateReferenceNumber(): string
{
    return 'REQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/** Creates a reference number that does not already exist in the database. */
function generateUniqueReferenceNumber(PDO $pdo): string
{
    $check = $pdo->prepare('SELECT 1 FROM document_requests WHERE reference_number = ? LIMIT 1');
    do {
        $reference = generateReferenceNumber();
        $check->execute([$reference]);
        $exists = (bool) $check->fetchColumn();
    } while ($exists);

    return $reference;
}

/** Records an entry in the audit log. */
function logAudit(?int $userId, string $action, string $entityType, ?int $entityId, string $details): void
{
    $stmt = getDBConnection()->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $entityType, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

/** Adds a notification for a user. */
function createNotification(int $userId, ?int $requestId, string $title, string $message): void
{
    $stmt = getDBConnection()->prepare(
        'INSERT INTO notifications (user_id, request_id, title, message) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $requestId, $title, $message]);
}

/** Counts a user's unread notifications. */
function getUnreadNotificationCount(int $userId): int
{
    $stmt = getDBConnection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/** Returns an escaped status label wrapped in a badge element. */
function statusBadge(string $status): string
{
    $slug = strtolower(str_replace(' ', '-', $status));
    return '<span class="badge badge-' . e($slug) . '">' . e($status) . '</span>';
}

/** Formats a database date for display, or an em dash when there is no value. */
function formatDate(?string $value, string $format = 'M j, Y'): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $time = strtotime($value);
    return $time === false ? '—' : date($format, $time);
}

/** Returns the display name for a role. */
function roleLabel(string $role): string
{
    $labels = [
        'student' => 'Student',
        'registrar' => 'Registrar\'s Office',
        'admin' => 'Administrator',
    ];
    return $labels[$role] ?? ucfirst($role);
}
