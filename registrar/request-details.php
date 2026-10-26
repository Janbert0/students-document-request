<?php
$pageTitle = 'Process request';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['registrar', 'admin']);
$pdo = getDBConnection();
$requestId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT r.*, d.name AS document_name, d.requirements, d.estimated_processing_days,
            u.first_name, u.last_name, u.email, u.student_number, u.department, u.course, u.contact_number
     FROM document_requests r
     JOIN document_types d ON d.id = r.document_type_id
     JOIN users u ON u.id = r.student_id
     WHERE r.id = ?'
);
$stmt->execute([$requestId]);
$req = $stmt->fetch();

// Checked before any output, so the redirect can still be sent.
if (!$req) {
    setFlash('danger', 'That request could not be found.');
    redirect('registrar/requests.php');
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

$nextStatuses = nextStatusesFor($req['status']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Request <span class="mono"><?= e($req['reference_number']) ?></span></h1>
    <p class="page-subtitle"><?= e($req['document_name']) ?> for <?= e($req['first_name'] . ' ' . $req['last_name']) ?></p>
  </div>
  <div class="page-actions">
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/registrar/requests.php">Back to queue</a>
  </div>
</div>

<div class="grid-main">
  <div>
    <section class="panel">
      <h2 class="panel-title">Update status</h2>
      <?php if ($nextStatuses): ?>
        <form method="POST" action="<?= e(BASE_URL) ?>/actions/request-update.php" data-confirm="Save this status change? The student will be notified.">
          <?= csrfField() ?>
          <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">

          <div class="form-group">
            <label class="form-label" for="new_status">Move to *</label>
            <select class="form-control" id="new_status" name="new_status" required>
              <option value="">Choose a status</option>
              <?php foreach ($nextStatuses as $status): ?>
                <option value="<?= e($status) ?>"><?= e($status) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="remarks">Remarks *</label>
            <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="1000" required placeholder="Explain the change. The student sees this in their timeline."></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save status</button>
          </div>
        </form>
      <?php else: ?>
        <p class="muted">This request is closed. No further status changes are possible.</p>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2 class="panel-title">Request</h2>
      <table class="table table-kv">
        <tr><th>Current status</th><td><?= statusBadge($req['status']) ?></td></tr>
        <tr><th>Document</th><td><?= e($req['document_name']) ?></td></tr>
        <tr><th>Copies</th><td><?= (int) $req['copies'] ?></td></tr>
        <tr><th>Purpose</th><td><?= e($req['purpose']) ?></td></tr>
        <tr><th>Student remarks</th><td><?= e($req['remarks'] ?: 'None') ?></td></tr>
        <tr><th>Clearance needed</th><td><?= e($req['requirements'] ?: 'None') ?></td></tr>
        <tr><th>Typical processing</th><td><?= (int) $req['estimated_processing_days'] ?> working days</td></tr>
        <tr><th>Submitted</th><td><?= formatDate($req['submitted_at'], 'F j, Y g:i A') ?></td></tr>
        <tr><th>Last updated</th><td><?= formatDate($req['updated_at'], 'F j, Y g:i A') ?></td></tr>
      </table>
    </section>

    <section class="panel">
      <h2 class="panel-title">Student</h2>
      <table class="table table-kv">
        <tr><th>Name</th><td><?= e($req['first_name'] . ' ' . $req['last_name']) ?></td></tr>
        <tr><th>Student ID</th><td class="mono"><?= e($req['student_number'] ?? '—') ?></td></tr>
        <tr><th>Email</th><td><?= e($req['email']) ?></td></tr>
        <tr><th>Department</th><td><?= e($req['department'] ?: '—') ?></td></tr>
        <tr><th>Course</th><td><?= e($req['course'] ?: '—') ?></td></tr>
        <tr><th>Contact</th><td><?= e($req['contact_number'] ?: '—') ?></td></tr>
      </table>
    </section>
  </div>

  <section class="panel">
    <h2 class="panel-title">History</h2>
    <ol class="timeline">
      <?php foreach ($history as $entry): ?>
        <li>
          <div class="timeline-status"><?= statusBadge($entry['new_status']) ?></div>
          <div class="timeline-meta">
            <?= formatDate($entry['created_at'], 'M j, Y g:i A') ?>
            · <?= e($entry['first_name'] . ' ' . $entry['last_name']) ?>
            (<?= e(roleLabel($entry['role'])) ?>)
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
