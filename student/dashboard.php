<?php
$pageTitle = 'Overview';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['student']);
$user = getCurrentUser();
$pdo = getDBConnection();

$summaryStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(status IN ('Submitted', 'Under Review', 'Approved')) AS in_review,
            SUM(status = 'Processing') AS processing,
            SUM(status = 'Ready for Release') AS ready
     FROM document_requests
     WHERE student_id = ?"
);
$summaryStmt->execute([(int) $user['id']]);
$summary = array_map('intval', $summaryStmt->fetch());

$recentStmt = $pdo->prepare(
    'SELECT r.id, r.reference_number, r.copies, r.status, r.submitted_at, d.name AS document_name
     FROM document_requests r
     JOIN document_types d ON d.id = r.document_type_id
     WHERE r.student_id = ?
     ORDER BY r.submitted_at DESC, r.id DESC
     LIMIT 5'
);
$recentStmt->execute([(int) $user['id']]);
$recent = $recentStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Overview</h1>
    <p class="page-subtitle">Track your document requests and their status.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/student/request-create.php">New request</a>
</div>

<div class="summary">
  <div class="summary-item">
    <div class="summary-value"><?= $summary['total'] ?></div>
    <div class="summary-label">Total requests</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $summary['in_review'] ?></div>
    <div class="summary-label">Waiting for review</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $summary['processing'] ?></div>
    <div class="summary-label">Being processed</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $summary['ready'] ?></div>
    <div class="summary-label">Ready for release</div>
  </div>
</div>

<section class="panel">
  <div class="panel-head">
    <h2 class="panel-title">Recent requests</h2>
    <a href="<?= e(BASE_URL) ?>/student/requests.php">View all</a>
  </div>

  <?php if (!$recent): ?>
    <p class="empty">
      You have not submitted any requests yet.<br>
      <a href="<?= e(BASE_URL) ?>/student/request-create.php">Submit your first request</a>
    </p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Document</th>
            <th>Submitted</th>
            <th class="num">Copies</th>
            <th>Status</th>
            <th><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $row): ?>
            <tr>
              <td class="mono"><?= e($row['reference_number']) ?></td>
              <td><?= e($row['document_name']) ?></td>
              <td><?= formatDate($row['submitted_at']) ?></td>
              <td class="num"><?= (int) $row['copies'] ?></td>
              <td><?= statusBadge($row['status']) ?></td>
              <td class="actions-cell"><a href="<?= e(BASE_URL) ?>/student/request-details.php?id=<?= (int) $row['id'] ?>">Details</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
