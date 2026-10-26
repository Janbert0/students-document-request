<?php
$pageTitle = 'New request';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['student']);
$user = getCurrentUser();

$documentTypes = getDBConnection()
    ->query('SELECT id, name, requirements, estimated_processing_days FROM document_types WHERE active = 1 ORDER BY name ASC')
    ->fetchAll();

$pageScripts = ['request.js'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">New document request</h1>
    <p class="page-subtitle">Choose a document and tell the Registrar what it is for.</p>
  </div>
</div>

<div class="grid-main">
  <section class="panel">
    <form method="POST" action="<?= e(BASE_URL) ?>/actions/request-submit.php">
      <?= csrfField() ?>
      <p class="form-note">Fields marked with * are required.</p>

      <div class="form-group">
        <label class="form-label" for="document_type_id">Document *</label>
        <select class="form-control" id="document_type_id" name="document_type_id" required>
          <option value="">Choose a document</option>
          <?php foreach ($documentTypes as $type): ?>
            <option value="<?= (int) $type['id'] ?>"
                    data-days="<?= (int) $type['estimated_processing_days'] ?>"
                    data-requirements="<?= attr($type, 'requirements') ?>"
                    <?= old('document_type_id') === (string) $type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="copies">Number of copies *</label>
        <input class="form-control" id="copies" type="number" name="copies" min="1" max="<?= MAX_COPIES ?>" value="<?= e(old('copies', '1')) ?>" required>
        <p class="form-help">Up to <?= MAX_COPIES ?> copies per request.</p>
      </div>

      <div class="form-group">
        <label class="form-label" for="purpose">Purpose *</label>
        <textarea class="form-control" id="purpose" name="purpose" rows="3" maxlength="255" required placeholder="For example: scholarship renewal, employment application"><?= e(old('purpose')) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="remarks">Additional remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="2" maxlength="1000" placeholder="Anything the Registrar should know"><?= e(old('remarks')) ?></textarea>
      </div>

      <div class="form-actions">
        <a class="btn btn-secondary" href="<?= e(BASE_URL) ?>/student/requests.php">Cancel</a>
        <button type="submit" class="btn btn-primary">Submit request</button>
      </div>
    </form>
  </section>

  <aside>
    <section class="panel" id="doc-summary" hidden>
      <h2 class="panel-title">About this document</h2>
      <dl class="detail-list">
        <dt>Processing time</dt>
        <dd data-field="days"></dd>
        <dt>Clearance needed</dt>
        <dd data-field="requirements"></dd>
      </dl>
    </section>

    <section class="panel">
      <h2 class="panel-title">Your details</h2>
      <dl class="detail-list">
        <dt>Name</dt>
        <dd><?= e($user['first_name'] . ' ' . $user['last_name']) ?></dd>
        <dt>Student ID</dt>
        <dd class="mono"><?= e($user['student_number'] ?? '—') ?></dd>
        <dt>Department</dt>
        <dd><?= e($user['department'] ?: '—') ?></dd>
        <dt>Course</dt>
        <dd><?= e($user['course'] ?: '—') ?></dd>
      </dl>
    </section>
  </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
