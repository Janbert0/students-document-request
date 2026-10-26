<?php
$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

requireAuth();
$user = getCurrentUser();
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('notifications.php');
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')
        ->execute([(int) $user['id']]);
    setFlash('success', 'All notifications are marked as read.');
    redirect('notifications.php');
}

$stmt = $pdo->prepare(
    'SELECT n.id, n.title, n.message, n.is_read, n.created_at, n.request_id, r.reference_number
     FROM notifications n
     LEFT JOIN document_requests r ON r.id = n.request_id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC, n.id DESC
     LIMIT 100'
);
$stmt->execute([(int) $user['id']]);
$items = $stmt->fetchAll();
$unread = getUnreadNotificationCount((int) $user['id']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Notifications</h1>
    <p class="page-subtitle"><?= $unread ?> unread</p>
  </div>
  <?php if ($unread > 0): ?>
    <form method="POST" action="<?= e(BASE_URL) ?>/notifications.php">
      <?= csrfField() ?>
      <button type="submit" class="btn btn-secondary">Mark all as read</button>
    </form>
  <?php endif; ?>
</div>

<section class="panel">
  <?php if (!$items): ?>
    <p class="empty">No notifications yet.</p>
  <?php else: ?>
    <ul class="notice-list">
      <?php foreach ($items as $item): ?>
        <li class="notice<?= $item['is_read'] ? '' : ' is-unread' ?>">
          <div class="notice-title">
            <?php if (!$item['is_read']): ?><span class="dot" aria-hidden="true"></span><?php endif; ?>
            <?= e($item['title']) ?>
          </div>
          <div class="notice-body"><?= e($item['message']) ?></div>
          <div class="notice-meta">
            <?= formatDate($item['created_at'], 'M j, Y g:i A') ?>
            <?php if ($item['reference_number'] && $user['role'] === 'student'): ?>
              · <a href="<?= e(BASE_URL) ?>/student/request-details.php?id=<?= (int) $item['request_id'] ?>">View request</a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
