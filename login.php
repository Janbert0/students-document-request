<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isAuthenticated()) {
    redirect(dashboardPathFor(getCurrentUser()['role']));
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('login.php');

    $email = cleanInput($_POST, 'email');
    $password = rawInput($_POST, 'password');

    if ($email === '' || $password === '') {
        $error = 'Enter your email address and password.';
    } else {
        $stmt = getDBConnection()->prepare('SELECT id, role, password_hash, account_status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            logAudit(null, 'LOGIN_FAILED', 'user', null, 'Failed sign-in attempt for ' . $email);
            $error = 'The email address or password is incorrect.';
        } elseif ($account['account_status'] !== 'active') {
            $error = 'This account is not active. Please contact the Registrar\'s Office.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $account['id'];
            logAudit((int) $account['id'], 'LOGIN_SUCCESS', 'user', (int) $account['id'], 'Signed in');
            redirect(dashboardPathFor($account['role']));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body class="auth-body">
  <main class="auth-panel">
    <p class="auth-app"><?= e(APP_NAME) ?></p>
    <h1 class="auth-title">Sign in</h1>
    <p class="auth-lead">Use your university email address and password.</p>

    <?php include __DIR__ . '/includes/flash-messages.php'; ?>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e(BASE_URL) ?>/login.php">
      <?= csrfField() ?>

      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <input class="form-control" id="email" type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" type="password" name="password" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>

    <p class="auth-footer">New student? <a href="<?= e(BASE_URL) ?>/register.php">Create an account</a></p>
  </main>
</body>
</html>
