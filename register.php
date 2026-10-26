<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';

if (isAuthenticated()) {
    redirect(dashboardPathFor(getCurrentUser()['role']));
}

$errors = [];
$general = '';
$values = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('register.php');

    $values = $_POST;
    $errors = validateRegistrationData($_POST);

    if (!$errors) {
        $pdo = getDBConnection();
        $email = cleanInput($_POST, 'email');
        $studentNumber = cleanInput($_POST, 'student_number');

        $check = $pdo->prepare('SELECT 1 FROM users WHERE email = ? OR student_number = ? LIMIT 1');
        $check->execute([$email, $studentNumber]);

        if ($check->fetchColumn()) {
            $general = 'An account already uses this email address or student ID.';
        } else {
            try {
                $insert = $pdo->prepare(
                    "INSERT INTO users (student_number, first_name, middle_name, last_name, email, password_hash,
                                        role, department, course, contact_number, account_status)
                     VALUES (?, ?, ?, ?, ?, ?, 'student', ?, ?, ?, 'active')"
                );
                $insert->execute([
                    $studentNumber,
                    cleanInput($_POST, 'first_name'),
                    nullIfBlank(cleanInput($_POST, 'middle_name')),
                    cleanInput($_POST, 'last_name'),
                    $email,
                    password_hash(rawInput($_POST, 'password'), PASSWORD_DEFAULT),
                    nullIfBlank(cleanInput($_POST, 'department')),
                    nullIfBlank(cleanInput($_POST, 'course')),
                    nullIfBlank(cleanInput($_POST, 'contact_number')),
                ]);

                $newUserId = (int) $pdo->lastInsertId();
                logAudit($newUserId, 'REGISTER_STUDENT', 'user', $newUserId, 'Student account created');

                setFlash('success', 'Your account has been created. You can sign in now.');
                redirect('login.php');
            } catch (PDOException $e) {
                // A concurrent registration can still take the same email or student ID.
                error_log('Registration failed: ' . $e->getMessage());
                $general = 'An account already uses this email address or student ID.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create account · <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body class="auth-body">
  <main class="auth-panel auth-panel-wide">
    <p class="auth-app"><?= e(APP_NAME) ?></p>
    <h1 class="auth-title">Create a student account</h1>
    <p class="auth-lead">An account lets you request official academic documents and track them.</p>

    <?php if ($general !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= e($general) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e(BASE_URL) ?>/register.php" novalidate>
      <?= csrfField() ?>
      <p class="form-note">Fields marked with * are required.</p>

      <div class="form-group">
        <label class="form-label" for="student_number">Student ID number *</label>
        <input class="form-control" id="student_number" type="text" name="student_number" value="<?= attr($values, 'student_number') ?>" placeholder="For example 2023-00101" maxlength="30" required>
        <?= formError($errors, 'student_number') ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="first_name">First name *</label>
          <input class="form-control" id="first_name" type="text" name="first_name" value="<?= attr($values, 'first_name') ?>" maxlength="50" autocomplete="given-name" required>
          <?= formError($errors, 'first_name') ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="last_name">Last name *</label>
          <input class="form-control" id="last_name" type="text" name="last_name" value="<?= attr($values, 'last_name') ?>" maxlength="50" autocomplete="family-name" required>
          <?= formError($errors, 'last_name') ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="middle_name">Middle name</label>
          <input class="form-control" id="middle_name" type="text" name="middle_name" value="<?= attr($values, 'middle_name') ?>" maxlength="50" autocomplete="additional-name">
          <?= formError($errors, 'middle_name') ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="contact_number">Contact number</label>
          <input class="form-control" id="contact_number" type="tel" name="contact_number" value="<?= attr($values, 'contact_number') ?>" maxlength="20" autocomplete="tel">
          <?= formError($errors, 'contact_number') ?>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email address *</label>
        <input class="form-control" id="email" type="email" name="email" value="<?= attr($values, 'email') ?>" maxlength="100" autocomplete="email" required>
        <?= formError($errors, 'email') ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="department">College or department</label>
          <input class="form-control" id="department" type="text" name="department" value="<?= attr($values, 'department') ?>" maxlength="100" placeholder="For example College of Computer Studies">
          <?= formError($errors, 'department') ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="course">Course or program</label>
          <input class="form-control" id="course" type="text" name="course" value="<?= attr($values, 'course') ?>" maxlength="100" placeholder="For example BS Information Technology">
          <?= formError($errors, 'course') ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Password *</label>
          <input class="form-control" id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
          <p class="form-help">At least 8 characters.</p>
          <?= formError($errors, 'password') ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm password *</label>
          <input class="form-control" id="confirm_password" type="password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
          <?= formError($errors, 'confirm_password') ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>

    <p class="auth-footer">Already have an account? <a href="<?= e(BASE_URL) ?>/login.php">Sign in</a></p>
  </main>
</body>
</html>
