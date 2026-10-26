<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('student/request-create.php');
}
requireCsrf('student/request-create.php');

$user = getCurrentUser();
$pdo = getDBConnection();

$messages = validateRequestInput($_POST);
$documentTypeId = (int) ($_POST['document_type_id'] ?? 0);

if (!$messages) {
    $typeStmt = $pdo->prepare('SELECT id FROM document_types WHERE id = ? AND active = 1');
    $typeStmt->execute([$documentTypeId]);
    if (!$typeStmt->fetch()) {
        $messages[] = 'The selected document is no longer available. Choose another document.';
    }
}

if ($messages) {
    setOldInput($_POST);
    setFlash('danger', implode(' ', $messages));
    redirect('student/request-create.php');
}

$copies = (int) $_POST['copies'];
$purpose = cleanInput($_POST, 'purpose');
$remarks = nullIfBlank(cleanInput($_POST, 'remarks'));

try {
    $pdo->beginTransaction();

    $referenceNumber = generateUniqueReferenceNumber($pdo);

    $insert = $pdo->prepare(
        "INSERT INTO document_requests (reference_number, student_id, document_type_id, copies, purpose, remarks, status)
         VALUES (?, ?, ?, ?, ?, ?, 'Submitted')"
    );
    $insert->execute([$referenceNumber, (int) $user['id'], $documentTypeId, $copies, $purpose, $remarks]);
    $requestId = (int) $pdo->lastInsertId();

    $history = $pdo->prepare(
        "INSERT INTO request_status_history (request_id, previous_status, new_status, remarks, changed_by)
         VALUES (?, NULL, 'Submitted', 'Request submitted.', ?)"
    );
    $history->execute([$requestId, (int) $user['id']]);

    logAudit((int) $user['id'], 'SUBMIT_REQUEST', 'document_requests', $requestId, "Submitted request {$referenceNumber}");

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Request submission failed: ' . $e->getMessage());
    setOldInput($_POST);
    setFlash('danger', 'The request could not be submitted. Please try again.');
    redirect('student/request-create.php');
}

setFlash('success', "Your request was submitted. Reference number: {$referenceNumber}.");
redirect('student/request-details.php?id=' . $requestId);
