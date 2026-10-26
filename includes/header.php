<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

$pageTitle = $pageTitle ?? 'Portal';
$user = getCurrentUser();
$unreadCount = $user ? getUnreadNotificationCount((int) $user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/print.css" media="print">
</head>
<body>
<?php if ($user): ?>
<div class="app">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="main">
    <header class="topbar">
      <button type="button" class="btn btn-secondary btn-sm menu-toggle" data-menu-toggle aria-controls="sidebar" aria-expanded="false">Menu</button>
      <div class="topbar-user">
        <a class="topbar-link" href="<?= e(BASE_URL) ?>/notifications.php">
          Notifications<?php if ($unreadCount > 0): ?> <span class="count" aria-label="<?= $unreadCount ?> unread"><?= $unreadCount ?></span><?php endif; ?>
        </a>
        <form method="POST" action="<?= e(BASE_URL) ?>/logout.php" class="inline-form">
          <?= csrfField() ?>
          <button type="submit" class="btn btn-ghost btn-sm">Sign out</button>
        </form>
      </div>
    </header>
    <main class="content">
      <?php include __DIR__ . '/flash-messages.php'; ?>
<?php endif; ?>
