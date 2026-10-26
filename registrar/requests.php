<?php
$pageTitle = 'Request queue';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['registrar', 'admin']);
$pdo = getDBConnection();

$search = cleanInput($_GET, 'search');
$statusFilter = cleanInput($_GET, 'status');
if (!in_array($statusFilter, REQUEST_STATUSES, true)) {
    $statusFilter = '';
}
$typeFilter = (int) ($_GET['doc_type'] ?? 0);

$sql = "SELECT r.id, r.reference_number, r.status, r.copies, r.submitted_at,
               d.name AS document_name, u.first_name, u.last_name, u.student_number
        FROM document_requests r
        JOIN document_types d ON d.id = r.document_type_id
        JOIN users u ON u.id = r.student_id
        WHERE 1 = 1";
$params = [];

if ($statusFilter !== '') {
    $sql .= ' AND r.status = ?';
    $params[] = $statusFilter;
}
if ($typeFilter > 0) {
    $sql .= ' AND r.document_type_id = ?';
    $params[] = $typeFilter;
}
if ($search !== '') {
    $sql .= ' AND (r.reference_number LIKE ? OR u.student_number LIKE ? OR CONCAT(u.first_name, \' \', u.last_name) LIKE ?)';
    $term = likeTerm($search);
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}
$sql .= ' ORDER BY r.submitted_at DESC, r.id DESC LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$documentTypes = $pdo->query('SELECT id, name FROM document_types ORDER BY name ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Request queue</h1>
    <p class="page-subtitle">All document requests. The newest 300 that match your filters are shown.</p>
  </div>
</div>

<form method="GET" class="filter-bar" action="<?= e(BASE_URL) ?>/registrar/requests.php">
  <div class="form-group">
    <label class="form-label" for="search">Search</label>
    <input class="form-control" id="search" type="search" name="search" value="<?= attr($_GET, 'search') ?>" placeholder="Reference, student ID or name">
  </div>
  <div class="form-group narrow">
    <label class="form-label" for="status">Status</label>
    <select class="form-control" id="status" name="status">
      <option value="">All statuses</option>
      <?php foreach (REQUEST_STATUSES as $option): ?>
        <option value="<?= e($option) ?>"<?= $statusFilter === $option ? ' selected' : '' ?>><?= e($option) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group narrow">
    <label class="form-label" for="doc_type">Document</label>
    <select class="form-control" id="doc_type" name="doc_type">
      <option value="0">All documents</option>
      <?php foreach ($documentTypes as $type): ?>
        <option value="<?= (int) $type['id'] ?>"<?= $typeFilter === (int) $type['id'] ? ' selected' : '' ?>><?= e($type['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/registrar/requests.php">Clear</a>
  </div>
</form>

<section class="panel panel-flush">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Student</th>
          <th>Document</th>
          <th class="num">Copies</th>
          <th>Submitted</th>
          <th>Status</th>
          <th><span class="visually-hidden">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$requests): ?>
          <tr><td colspan="7" class="empty">No requests match your filters.</td></tr>
        <?php else: ?>
          <?php foreach ($requests as $row): ?>
            <tr>
              <td class="mono"><?= e($row['reference_number']) ?></td>
              <td>
                <?= e($row['first_name'] . ' ' . $row['last_name']) ?>
                <div class="muted mono"><?= e($row['student_number'] ?? '') ?></div>
              </td>
              <td><?= e($row['document_name']) ?></td>
              <td class="num"><?= (int) $row['copies'] ?></td>
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
