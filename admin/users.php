<?php
$pageTitle = 'Users';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

requireRole(['admin']);
$pdo = getDBConnection();
$me = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/users.php');
    $action = cleanInput($_POST, 'action');

    if ($action === 'set_status') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        $newStatus = cleanInput($_POST, 'new_status') === 'active' ? 'active' : 'deactivated';

        $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target || $target['role'] === 'admin') {
            setFlash('danger', 'That account cannot be changed here.');
        } else {
            $pdo->prepare('UPDATE users SET account_status = ? WHERE id = ?')->execute([$newStatus, $targetId]);
            logAudit((int) $me['id'], 'USER_STATUS_CHANGED', 'user', $targetId, "Account set to {$newStatus}");
            setFlash('success', $newStatus === 'active' ? 'Account activated.' : 'Account deactivated.');
        }
        redirect('admin/users.php');
    }

    if ($action === 'create_registrar') {
        $firstName = cleanInput($_POST, 'first_name');
        $lastName = cleanInput($_POST, 'last_name');
        $email = cleanInput($_POST, 'email');
        $password = rawInput($_POST, 'password');

        $error = null;
        if ($firstName === '' || $lastName === '') {
            $error = 'Enter the first and last name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $error = 'Enter a valid email address.';
        } else {
            $error = validatePassword($password);
        }

        if ($error === null) {
            $check = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetchColumn()) {
                $error = 'An account already uses this email address.';
            }
        }

        if ($error !== null) {
            setOldInput($_POST);
            setFlash('danger', $error);
            redirect('admin/users.php');
        }

        $pdo->prepare(
            "INSERT INTO users (first_name, last_name, email, password_hash, role, department, account_status)
             VALUES (?, ?, ?, ?, 'registrar', ?, 'active')"
        )->execute([
            $firstName,
            $lastName,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            "Registrar's Office",
        ]);

        $newId = (int) $pdo->lastInsertId();
        logAudit((int) $me['id'], 'CREATE_REGISTRAR', 'user', $newId, "Created registrar account for {$email}");
        setFlash('success', 'Registrar account created.');
        redirect('admin/users.php');
    }

    redirect('admin/users.php');
}

$users = $pdo->query(
    'SELECT id, student_number, first_name, last_name, email, role, account_status, created_at
     FROM users
     ORDER BY FIELD(role, \'admin\', \'registrar\', \'student\'), last_name ASC, first_name ASC'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Users</h1>
    <p class="page-subtitle">Activate or deactivate accounts, and add Registrar staff.</p>
  </div>
</div>

<div class="grid-main">
  <section class="panel panel-flush">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $row): ?>
            <tr>
              <td>
                <?= e($row['first_name'] . ' ' . $row['last_name']) ?>
                <?php if ($row['student_number']): ?>
                  <div class="muted mono"><?= e($row['student_number']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= e($row['email']) ?></td>
              <td><?= e(roleLabel($row['role'])) ?></td>
              <td><span class="badge badge-<?= e($row['account_status']) ?>"><?= e(ucfirst($row['account_status'])) ?></span></td>
              <td class="actions-cell">
                <?php if ($row['role'] !== 'admin'): ?>
                  <?php if ($row['account_status'] === 'active'): ?>
                    <form method="POST" action="<?= e(BASE_URL) ?>/admin/users.php" class="inline-form" data-confirm="Deactivate this account? The user will be signed out.">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="set_status">
                      <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                      <input type="hidden" name="new_status" value="deactivated">
                      <button type="submit" class="btn btn-ghost btn-sm">Deactivate</button>
                    </form>
                  <?php else: ?>
                    <form method="POST" action="<?= e(BASE_URL) ?>/admin/users.php" class="inline-form">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="set_status">
                      <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                      <input type="hidden" name="new_status" value="active">
                      <button type="submit" class="btn btn-ghost btn-sm">Activate</button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <h2 class="panel-title">Add Registrar staff</h2>
    <form method="POST" action="<?= e(BASE_URL) ?>/admin/users.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create_registrar">

      <div class="form-group">
        <label class="form-label" for="reg_first">First name *</label>
        <input class="form-control" id="reg_first" type="text" name="first_name" value="<?= e(old('first_name')) ?>" maxlength="50" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="reg_last">Last name *</label>
        <input class="form-control" id="reg_last" type="text" name="last_name" value="<?= e(old('last_name')) ?>" maxlength="50" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="reg_email">Email address *</label>
        <input class="form-control" id="reg_email" type="email" name="email" value="<?= e(old('email')) ?>" maxlength="100" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="reg_password">Initial password *</label>
        <input class="form-control" id="reg_password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <p class="form-help">At least 8 characters. Ask the staff member to change it after signing in.</p>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create account</button>
      </div>
    </form>
  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
