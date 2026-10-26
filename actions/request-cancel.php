<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('student/requests.php');
}
requireCsrf('student/requests.php');

$user = getCurrentUser();
$pdo = getDBConnection();
$requestId = (int) ($_POST['request_id'] ?? 0);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, reference_number, status FROM document_requests WHERE id = ? AND student_id = ? FOR UPDATE'
    );
    $stmt->execute([$requestId, (int) $user['id']]);
    $req = $stmt->fetch();

    if (!$req) {
        throw new RuntimeException('That request could not be found.');
    }
    // Students may cancel only before the Registrar starts reviewing the request.
    if ($req['status'] !== 'Submitted') {
        throw new RuntimeException('This request can no longer be cancelled.');
    }

    $pdo->prepare('UPDATE document_requests SET status = ? WHERE id = ?')->execute(['Cancelled', $requestId]);

    $pdo->prepare(
        'INSERT INTO request_status_history (request_id, previous_status, new_status, remarks, changed_by)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$requestId, 'Submitted', 'Cancelled', 'Cancelled by the student.', (int) $user['id']]);

    logAudit((int) $user['id'], 'CANCEL_REQUEST', 'document_requests', $requestId, "Cancelled request {$req['reference_number']}");

    $pdo->commit();
    setFlash('success', 'Your request has been cancelled.');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Request cancellation failed: ' . $e->getMessage());
    setFlash('danger', 'The request could not be cancelled. Please try again.');
}

redirect('student/request-details.php?id=' . $requestId);
