<?php
/** Navigation for the signed-in role. Included by header.php, where $user is set. */
switch ($user['role']) {
    case 'admin':
        $navItems = [
            ['admin/dashboard.php', 'Overview'],
            ['registrar/requests.php', 'Request queue'],
            ['admin/document-types.php', 'Document types'],
            ['admin/users.php', 'Users'],
            ['admin/audit-logs.php', 'Audit logs'],
        ];
        break;
    case 'registrar':
        $navItems = [
            ['registrar/dashboard.php', 'Overview'],
            ['registrar/requests.php', 'Request queue'],
        ];
        break;
    default:
        $navItems = [
            ['student/dashboard.php', 'Overview'],
            ['student/requests.php', 'My requests'],
            ['student/request-create.php', 'New request'],
            ['student/profile.php', 'Profile'],
        ];
}

$currentPage = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) . '/' . basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= e(BASE_URL) ?>/<?= e(dashboardPathFor($user['role'])) ?>"><?= e(APP_NAME) ?></a>
  <nav aria-label="Main">
    <ul class="nav-list">
      <?php foreach ($navItems as [$path, $label]): ?>
        <?php $isActive = $currentPage === $path; ?>
        <li>
          <a href="<?= e(BASE_URL) ?>/<?= e($path) ?>"<?= $isActive ? ' class="is-active" aria-current="page"' : '' ?>><?= e($label) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="sidebar-foot">
    <div><?= e($user['first_name'] . ' ' . $user['last_name']) ?></div>
    <div class="muted"><?= e(roleLabel($user['role'])) ?></div>
  </div>
</aside>
