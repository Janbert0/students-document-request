<?php
$pageTitle = 'Request details';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['student']);
$user = getCurrentUser();
$pdo = getDBConnection();
$requestId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT r.*, d.name AS document_name
     FROM document_requests r
     JOIN document_types d ON d.id = r.document_type_id
     WHERE r.id = ? AND r.student_id = ?'
);
$stmt->execute([$requestId, (int) $user['id']]);
$req = $stmt->fetch();

// Checked before any output, so the redirect can still be sent.
if (!$req) {
    setFlash('danger', 'That request could not be found.');
    redirect('student/requests.php');
}

$historyStmt = $pdo->prepare(
    'SELECT h.new_status, h.remarks, h.created_at, u.role, u.first_name, u.last_name
     FROM request_status_history h
     JOIN users u ON u.id = h.changed_by
     WHERE h.request_id = ?
     ORDER BY h.created_at ASC, h.id ASC'
);
$historyStmt->execute([$requestId]);
$history = $historyStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Request <span class="mono"><?= e($req['reference_number']) ?></span></h1>
    <p class="page-subtitle"><?= e($req['document_name']) ?></p>
  </div>
  <div class="page-actions no-print">
    <button type="button" class="btn btn-secondary" data-print>Print record</button>
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/student/requests.php">Back to requests</a>
  </div>
</div>

<p class="print-only">Student request record. Printed <?= date('F j, Y g:i A') ?>.</p>

<div class="grid-main">
  <div>
    <section class="panel">
      <h2 class="panel-title">Request summary</h2>
      <table class="table table-kv">
        <tr><th>Current status</th><td><?= statusBadge($req['status']) ?></td></tr>
        <tr><th>Document</th><td><?= e($req['document_name']) ?></td></tr>
        <tr><th>Copies</th><td><?= (int) $req['copies'] ?></td></tr>
        <tr><th>Purpose</th><td><?= e($req['purpose']) ?></td></tr>
        <tr><th>Your remarks</th><td><?= e($req['remarks'] ?: 'None') ?></td></tr>
        <tr><th>Submitted</th><td><?= formatDate($req['submitted_at'], 'F j, Y g:i A') ?></td></tr>
        <tr><th>Last updated</th><td><?= formatDate($req['updated_at'], 'F j, Y g:i A') ?></td></tr>
        <?php if ($req['released_at']): ?>
          <tr><th>Released</th><td><?= formatDate($req['released_at'], 'F j, Y g:i A') ?></td></tr>
        <?php endif; ?>
      </table>
    </section>

    <?php if ($req['status'] === 'Submitted'): ?>
      <section class="panel no-print">
        <h2 class="panel-title">Cancel this request</h2>
        <p class="muted">You can cancel a request until the Registrar's Office starts reviewing it.</p>
        <form method="POST" action="<?= e(BASE_URL) ?>/actions/request-cancel.php" data-confirm="Cancel this request? This cannot be undone.">
          <?= csrfField() ?>
          <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
          <button type="submit" class="btn btn-danger">Cancel request</button>
        </form>
      </section>
    <?php endif; ?>
  </div>

  <section class="panel">
    <h2 class="panel-title">Timeline</h2>
    <ol class="timeline">
      <?php foreach ($history as $entry): ?>
        <li>
          <div class="timeline-status"><?= statusBadge($entry['new_status']) ?></div>
          <div class="timeline-meta">
            <?= formatDate($entry['created_at'], 'M j, Y g:i A') ?>
            · <?= $entry['role'] === 'student' ? 'By you' : 'By the Registrar\'s Office' ?>
          </div>
          <?php if (!empty($entry['remarks'])): ?>
            <div class="timeline-remark"><?= e($entry['remarks']) ?></div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
