<?php
$pageTitle = 'Overview';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin']);
$pdo = getDBConnection();

$counts = $pdo->query(
    "SELECT COUNT(*) AS accounts,
            SUM(role = 'student') AS students,
            SUM(role = 'registrar') AS staff
     FROM users"
)->fetch();
$counts = array_map('intval', $counts);

$requestCounts = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(status IN ('Submitted', 'Under Review', 'Approved', 'Processing', 'Ready for Release')) AS open_requests
     FROM document_requests"
)->fetch();
$requestCounts = array_map('intval', $requestCounts);

$recentLogs = $pdo->query(
    'SELECT a.action, a.entity_type, a.entity_id, a.created_at, u.first_name, u.last_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT 8'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Overview</h1>
    <p class="page-subtitle">Accounts, requests, and recent activity.</p>
  </div>
</div>

<div class="summary">
  <div class="summary-item">
    <div class="summary-value"><?= $counts['accounts'] ?></div>
    <div class="summary-label">User accounts</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $counts['students'] ?></div>
    <div class="summary-label">Students</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $counts['staff'] ?></div>
    <div class="summary-label">Registrar staff</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $requestCounts['open_requests'] ?></div>
    <div class="summary-label">Open requests</div>
  </div>
  <div class="summary-item">
    <div class="summary-value"><?= $requestCounts['total'] ?></div>
    <div class="summary-label">All requests</div>
  </div>
</div>

<section class="panel panel-flush">
  <div class="panel-head panel-head-pad">
    <h2 class="panel-title">Recent activity</h2>
    <a href="<?= e(BASE_URL) ?>/admin/audit-logs.php">Full audit log</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>When</th>
          <th>User</th>
          <th>Action</th>
          <th>Record</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recentLogs): ?>
          <tr><td colspan="4" class="empty">No activity recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentLogs as $log): ?>
            <tr>
              <td><?= formatDate($log['created_at'], 'M j, Y g:i A') ?></td>
              <td><?= $log['first_name'] ? e($log['first_name'] . ' ' . $log['last_name']) : '<span class="muted">Guest</span>' ?></td>
              <td class="mono"><?= e($log['action']) ?></td>
              <td><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : '' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
