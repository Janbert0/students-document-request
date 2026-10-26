<?php
$pageTitle = 'My requests';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['student']);
$user = getCurrentUser();
$pdo = getDBConnection();

$statusFilter = cleanInput($_GET, 'status');
if (!in_array($statusFilter, REQUEST_STATUSES, true)) {
    $statusFilter = '';
}
$search = cleanInput($_GET, 'search');

$sql = 'SELECT r.id, r.reference_number, r.copies, r.status, r.submitted_at, d.name AS document_name
        FROM document_requests r
        JOIN document_types d ON d.id = r.document_type_id
        WHERE r.student_id = ?';
$params = [(int) $user['id']];

if ($statusFilter !== '') {
    $sql .= ' AND r.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $sql .= ' AND (r.reference_number LIKE ? OR d.name LIKE ?)';
    $term = likeTerm($search);
    $params[] = $term;
    $params[] = $term;
}
$sql .= ' ORDER BY r.submitted_at DESC, r.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">My requests</h1>
    <p class="page-subtitle">Every document you have requested, newest first.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/student/request-create.php">New request</a>
</div>

<form method="GET" class="filter-bar" action="<?= e(BASE_URL) ?>/student/requests.php">
  <div class="form-group">
    <label class="form-label" for="search">Search</label>
    <input class="form-control" id="search" type="search" name="search" value="<?= attr($_GET, 'search') ?>" placeholder="Reference number or document">
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
  <div class="filter-actions">
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/student/requests.php">Clear</a>
  </div>
</form>

<section class="panel panel-flush">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Document</th>
          <th class="num">Copies</th>
          <th>Submitted</th>
          <th>Status</th>
          <th><span class="visually-hidden">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$requests): ?>
          <tr><td colspan="6" class="empty">No requests match your filters.</td></tr>
        <?php else: ?>
          <?php foreach ($requests as $row): ?>
            <tr>
              <td class="mono"><?= e($row['reference_number']) ?></td>
              <td><?= e($row['document_name']) ?></td>
              <td class="num"><?= (int) $row['copies'] ?></td>
              <td><?= formatDate($row['submitted_at']) ?></td>
              <td><?= statusBadge($row['status']) ?></td>
              <td class="actions-cell"><a href="<?= e(BASE_URL) ?>/student/request-details.php?id=<?= (int) $row['id'] ?>">Details</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
