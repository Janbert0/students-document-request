<?php
$pageTitle = 'Document types';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

requireRole(['admin']);
$me = getCurrentUser();
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/document-types.php');
    $action = cleanInput($_POST, 'action');

    if ($action === 'toggle') {
        $docId = (int) ($_POST['doc_id'] ?? 0);
        $active = ($_POST['active'] ?? '') === '1' ? 1 : 0;

        $stmt = $pdo->prepare('SELECT id, name FROM document_types WHERE id = ?');
        $stmt->execute([$docId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            setFlash('danger', 'That document type could not be found.');
        } else {
            $pdo->prepare('UPDATE document_types SET active = ? WHERE id = ?')->execute([$active, $docId]);
            logAudit((int) $me['id'], 'TOGGLE_DOC_TYPE', 'document_types', $docId, ($active ? 'Activated' : 'Deactivated') . " {$doc['name']}");
            setFlash('success', $active ? 'Document type activated.' : 'Document type deactivated.');
        }
        redirect('admin/document-types.php');
    }

    if ($action === 'create' || $action === 'update') {
        $docId = (int) ($_POST['doc_id'] ?? 0);
        [$clean, $messages] = validateDocumentType($_POST);

        if (!$messages) {
            $dupe = $pdo->prepare('SELECT id FROM document_types WHERE name = ? AND id <> ? LIMIT 1');
            $dupe->execute([$clean['name'], $docId]);
            if ($dupe->fetch()) {
                $messages[] = 'A document type with this title already exists.';
            }
        }

        if ($action === 'update' && !$messages) {
            $exists = $pdo->prepare('SELECT 1 FROM document_types WHERE id = ?');
            $exists->execute([$docId]);
            if (!$exists->fetchColumn()) {
                $messages[] = 'That document type could not be found.';
            }
        }

        if ($messages) {
            setOldInput($_POST);
            setFlash('danger', implode(' ', $messages));
            redirect($action === 'update' && $docId > 0 ? 'admin/document-types.php?edit=' . $docId : 'admin/document-types.php');
        }

        $requirements = nullIfBlank($clean['requirements']);

        if ($action === 'create') {
            $pdo->prepare(
                'INSERT INTO document_types (name, description, requirements, estimated_processing_days, active)
                 VALUES (?, ?, ?, ?, 1)'
            )->execute([$clean['name'], $clean['description'], $requirements, $clean['estimated_processing_days']]);

            $newId = (int) $pdo->lastInsertId();
            logAudit((int) $me['id'], 'CREATE_DOC_TYPE', 'document_types', $newId, "Created document type {$clean['name']}");
            setFlash('success', 'Document type added.');
        } else {
            $pdo->prepare(
                'UPDATE document_types SET name = ?, description = ?, requirements = ?, estimated_processing_days = ?
                 WHERE id = ?'
            )->execute([$clean['name'], $clean['description'], $requirements, $clean['estimated_processing_days'], $docId]);

            logAudit((int) $me['id'], 'UPDATE_DOC_TYPE', 'document_types', $docId, "Updated document type {$clean['name']}");
            setFlash('success', 'Document type saved.');
        }
        redirect('admin/document-types.php');
    }

    redirect('admin/document-types.php');
}

$editDoc = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM document_types WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editDoc = $stmt->fetch();

    if (!$editDoc) {
        setFlash('danger', 'That document type could not be found.');
        redirect('admin/document-types.php');
    }
}

$documentTypes = $pdo->query('SELECT * FROM document_types ORDER BY name ASC')->fetchAll();

$formValues = [
    'name' => old('name', (string) ($editDoc['name'] ?? '')),
    'description' => old('description', (string) ($editDoc['description'] ?? '')),
    'requirements' => old('requirements', (string) ($editDoc['requirements'] ?? '')),
    'estimated_processing_days' => old('estimated_processing_days', (string) ($editDoc['estimated_processing_days'] ?? '3')),
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Document types</h1>
    <p class="page-subtitle">Documents students can request, with the clearance each one needs.</p>
  </div>
</div>

<div class="grid-main">
  <section class="panel panel-flush">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Document</th>
            <th class="num">Processing</th>
            <th>Clearance needed</th>
            <th>Status</th>
            <th><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($documentTypes as $doc): ?>
            <tr>
              <td>
                <strong><?= e($doc['name']) ?></strong>
                <div class="muted"><?= e($doc['description']) ?></div>
              </td>
              <td class="num"><?= (int) $doc['estimated_processing_days'] ?> days</td>
              <td><?= e($doc['requirements'] ?: 'None') ?></td>
              <td><span class="badge <?= $doc['active'] ? 'badge-active' : 'badge-deactivated' ?>"><?= $doc['active'] ? 'Active' : 'Inactive' ?></span></td>
              <td class="actions-cell">
                <a href="<?= e(BASE_URL) ?>/admin/document-types.php?edit=<?= (int) $doc['id'] ?>">Edit</a>
                <form method="POST" action="<?= e(BASE_URL) ?>/admin/document-types.php" class="inline-form">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="doc_id" value="<?= (int) $doc['id'] ?>">
                  <input type="hidden" name="active" value="<?= $doc['active'] ? '0' : '1' ?>">
                  <button type="submit" class="btn btn-ghost btn-sm"><?= $doc['active'] ? 'Deactivate' : 'Activate' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <h2 class="panel-title"><?= $editDoc ? 'Edit document type' : 'Add document type' ?></h2>
    <form method="POST" action="<?= e(BASE_URL) ?>/admin/document-types.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $editDoc ? 'update' : 'create' ?>">
      <?php if ($editDoc): ?>
        <input type="hidden" name="doc_id" value="<?= (int) $editDoc['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="dt_name">Title *</label>
        <input class="form-control" id="dt_name" type="text" name="name" value="<?= e($formValues['name']) ?>" maxlength="100" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="dt_description">Description *</label>
        <textarea class="form-control" id="dt_description" name="description" rows="2" maxlength="500" required><?= e($formValues['description']) ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label" for="dt_requirements">Clearance needed</label>
        <textarea class="form-control" id="dt_requirements" name="requirements" rows="2" maxlength="500" placeholder="For example: Library clearance, Finance clearance"><?= e($formValues['requirements']) ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label" for="dt_days">Processing time (working days) *</label>
        <input class="form-control" id="dt_days" type="number" name="estimated_processing_days" value="<?= e($formValues['estimated_processing_days']) ?>" min="1" max="30" required>
      </div>

      <div class="form-actions">
        <?php if ($editDoc): ?>
          <a class="btn btn-secondary" href="<?= e(BASE_URL) ?>/admin/document-types.php">Cancel</a>
          <button type="submit" class="btn btn-primary">Save changes</button>
        <?php else: ?>
          <button type="submit" class="btn btn-primary">Add document type</button>
        <?php endif; ?>
      </div>
    </form>
  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
