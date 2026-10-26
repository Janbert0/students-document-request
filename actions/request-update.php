<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['registrar', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('registrar/requests.php');
}
requireCsrf('registrar/requests.php');

$user = getCurrentUser();
$pdo = getDBConnection();
$requestId = (int) ($_POST['request_id'] ?? 0);
$newStatus = cleanInput($_POST, 'new_status');
$remarks = cleanInput($_POST, 'remarks');

if ($requestId <= 0) {
    setFlash('danger', 'That request could not be found.');
    redirect('registrar/requests.php');
}

$detailsPath = 'registrar/request-details.php?id=' . $requestId;

if ($remarks === '' || mb_strlen($remarks) > 1000) {
    setFlash('danger', 'Enter remarks for the student (1000 characters or fewer).');
    redirect($detailsPath);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id, reference_number, student_id, status FROM document_requests WHERE id = ? FOR UPDATE');
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();

    if (!$req) {
        throw new RuntimeException('That request could not be found.');
    }
    if (!canTransition($req['status'], $newStatus)) {
        throw new RuntimeException("A request cannot move from {$req['status']} to {$newStatus}.");
    }

    $releasedAt = $newStatus === 'Released' ? date('Y-m-d H:i:s') : null;
    $pdo->prepare(
        'UPDATE document_requests SET status = ?, processed_by = ?, released_at = COALESCE(?, released_at) WHERE id = ?'
    )->execute([$newStatus, (int) $user['id'], $releasedAt, $requestId]);

    $pdo->prepare(
        'INSERT INTO request_status_history (request_id, previous_status, new_status, remarks, changed_by)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$requestId, $req['status'], $newStatus, $remarks, (int) $user['id']]);

    createNotification(
        (int) $req['student_id'],
        $requestId,
        "Request {$req['reference_number']} updated",
        "Status changed to {$newStatus}. Remarks: {$remarks}"
    );

    logAudit(
        (int) $user['id'],
        'UPDATE_REQUEST_STATUS',
        'document_requests',
        $requestId,
        "Changed status of {$req['reference_number']} from {$req['status']} to {$newStatus}"
    );

    $pdo->commit();
    setFlash('success', "Status updated to {$newStatus}.");
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Status update failed: ' . $e->getMessage());
    setFlash('danger', 'The status could not be updated. Please try again.');
}

redirect($detailsPath);
