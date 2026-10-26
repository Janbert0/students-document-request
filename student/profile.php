<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['student']);
$user = getCurrentUser();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Profile</h1>
    <p class="page-subtitle">Your academic details are maintained by the Registrar's Office. You can update your contact number and password here.</p>
  </div>
</div>

<div class="grid-even">
  <section class="panel">
    <h2 class="panel-title">Academic record</h2>
    <div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="p-student">Student ID</label>
          <input class="form-control" id="p-student" type="text" value="<?= e($user['student_number'] ?? '') ?>" readonly>
        </div>
        <div class="form-group">
          <label class="form-label" for="p-email">Email address</label>
          <input class="form-control" id="p-email" type="email" value="<?= attr($user, 'email') ?>" readonly>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="p-first">First name</label>
          <input class="form-control" id="p-first" type="text" value="<?= attr($user, 'first_name') ?>" readonly>
        </div>
        <div class="form-group">
          <label class="form-label" for="p-last">Last name</label>
          <input class="form-control" id="p-last" type="text" value="<?= attr($user, 'last_name') ?>" readonly>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="p-dept">College or department</label>
        <input class="form-control" id="p-dept" type="text" value="<?= attr($user, 'department') ?>" readonly>
      </div>
      <div class="form-group">
        <label class="form-label" for="p-course">Course or program</label>
        <input class="form-control" id="p-course" type="text" value="<?= attr($user, 'course') ?>" readonly>
      </div>
    </div>
  </section>

  <div>
    <section class="panel">
      <h2 class="panel-title">Contact number</h2>
      <form method="POST" action="<?= e(BASE_URL) ?>/actions/profile-update.php">
        <?= csrfField() ?>
        <input type="hidden" name="action_type" value="update_contact">
        <div class="form-group">
          <label class="form-label" for="contact_number">Contact number *</label>
          <input class="form-control" id="contact_number" type="tel" name="contact_number" value="<?= attr($user, 'contact_number') ?>" maxlength="20" autocomplete="tel" required>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save contact number</button>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2 class="panel-title">Change password</h2>
      <form method="POST" action="<?= e(BASE_URL) ?>/actions/profile-update.php">
        <?= csrfField() ?>
        <input type="hidden" name="action_type" value="change_password">
        <div class="form-group">
          <label class="form-label" for="current_password">Current password *</label>
          <input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="new_password">New password *</label>
          <input class="form-control" id="new_password" type="password" name="new_password" minlength="8" maxlength="72" autocomplete="new-password" required>
          <p class="form-help">At least 8 characters.</p>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_new_password">Confirm new password *</label>
          <input class="form-control" id="confirm_new_password" type="password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Change password</button>
        </div>
      </form>
    </section>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
