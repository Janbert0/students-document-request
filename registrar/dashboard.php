<?php
$pageTitle = 'Overview';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['registrar', 'admin']);
$pdo = getDBConnection();

$counts = $pdo->query(
    "SELECT SUM(status = 'Submitted') AS submitted,
            SUM(status = 'Under Review') AS in_review,
            SUM(status = 'Processing') AS processing,
            SUM(status = 'Ready for Release') AS ready
     FROM document_requests"
)->fetch();
$counts = array_map('intval', $counts);

$queue = $pdo->query(
    "SELECT r.id, r.reference_number, r.status, r.submitted_at,
            d.name AS document_name, u.first_name, u.last_name, u.student_number
     FROM document_requests r
     JOIN document_types d ON d.id = r.document_type_id
     JOIN users u ON u.id = r.student_id
     WHERE r.status IN ('Submitted', 'Under Review', 'Approved', 'Processing', 'Ready for Release')
     ORDER BY r.submitted_at ASC, r.id ASC
     LIMIT 10"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Overview</h1>
    <p class="page-subtitle">Open requests, oldest first.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(BASE_URL) ?>/registrar/requests.php">Open request queue</a>
</div>

<div class="summary">
  <div class="summary-item">
    <div class="summary-value"><?= $counts['submitted'] ?></div>
    <div class="summary-label">Newly submitted</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $counts['in_review'] ?></div>
    <div class="summary-label">Under review</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $counts['processing'] ?></div>
    <div class="summary-label">Processing</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $counts['ready'] ?></div>
    <div class="summary-label">Ready for release</div>
  </div>
</div>

<section class="panel panel-flush">
  <div class="panel-head panel-head-pad">
    <h2 class="panel-title">Oldest open requests</h2>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Student</th>
          <th>Document</th>
          <th>Submitted</th>
          <th>Status</th>
          <th><span class="visually-hidden">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$queue): ?>
          <tr><td colspan="6" class="empty">There are no open requests.</td></tr>
        <?php else: ?>
          <?php foreach ($queue as $row): ?>
            <tr>
              <td class="mono"><?= e($row['reference_number']) ?></td>
              <td>
                <?= e($row['first_name'] . ' ' . $row['last_name']) ?>
                <div class="muted mono"><?= e($row['student_number'] ?? '') ?></div>
              </td>
              <td><?= e($row['document_name']) ?></td>
              <td><?= formatDate($row['submitted_at']) ?></td>
              <td><?= statusBadge($row['status']) ?></td>
              <td class="actions-cell"><a href="<?= e(BASE_URL) ?>/registrar/request-details.php?id=<?= (int) $row['id'] ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
