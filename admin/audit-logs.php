<?php
$pageTitle = 'Audit logs';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin']);
$pdo = getDBConnection();

$actionFilter = cleanInput($_GET, 'action');
$search = cleanInput($_GET, 'search');

$sql = 'SELECT a.id, a.action, a.entity_type, a.entity_id, a.details, a.ip_address, a.created_at,
               u.first_name, u.last_name, u.email
        FROM audit_logs a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE 1 = 1';
$params = [];

if ($actionFilter !== '') {
    $sql .= ' AND a.action = ?';
    $params[] = $actionFilter;
}
if ($search !== '') {
    $sql .= ' AND (CONCAT(u.first_name, \' \', u.last_name) LIKE ? OR u.email LIKE ? OR a.details LIKE ? OR a.ip_address LIKE ?)';
    $term = likeTerm($search);
    $params = array_merge($params, [$term, $term, $term, $term]);
}
$sql .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC')->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Audit logs</h1>
    <p class="page-subtitle">Sign-ins, status changes, and administrative actions. The newest 200 matching entries are shown.</p>
  </div>
</div>

<form method="GET" class="filter-bar" action="<?= e(BASE_URL) ?>/admin/audit-logs.php">
  <div class="form-group">
    <label class="form-label" for="search">Search</label>
    <input class="form-control" id="search" type="search" name="search" value="<?= attr($_GET, 'search') ?>" placeholder="Name, email, details or IP address">
  </div>
  <div class="form-group narrow">
    <label class="form-label" for="action">Action</label>
    <select class="form-control" id="action" name="action">
      <option value="">All actions</option>
      <?php foreach ($actions as $act): ?>
        <option value="<?= e($act) ?>"<?= $actionFilter === $act ? ' selected' : '' ?>><?= e($act) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/admin/audit-logs.php">Clear</a>
  </div>
</form>

<section class="panel panel-flush">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>When</th>
          <th>User</th>
          <th>Action</th>
          <th>Record</th>
          <th>IP address</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$logs): ?>
          <tr><td colspan="6" class="empty">No entries match your filters.</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td class="nowrap"><?= formatDate($log['created_at'], 'M j, Y g:i:s A') ?></td>
              <td>
                <?php if ($log['first_name']): ?>
                  <?= e($log['first_name'] . ' ' . $log['last_name']) ?>
                  <div class="muted"><?= e($log['email']) ?></div>
                <?php else: ?>
                  <span class="muted">Guest</span>
                <?php endif; ?>
              </td>
              <td class="mono"><?= e($log['action']) ?></td>
              <td><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : '' ?></td>
              <td class="mono"><?= e($log['ip_address'] ?? '—') ?></td>
              <td class="detail-cell"><?= e($log['details']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
