<?php
require_once __DIR__ . '/config.php';

$page_title = 'Notifications';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $notifId = (int)($_POST['notification_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all_read') {
        $pdo->exec('UPDATE notifications SET is_read = 1');
        $notice = 'All notifications marked as read.';
    } elseif ($notifId > 0 && in_array($action, ['mark_read', 'mark_unread', 'delete'], true)) {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM notifications WHERE id = ?')->execute([$notifId]);
            $notice = 'Notification deleted.';
        } else {
            $isRead = $action === 'mark_read' ? 1 : 0;
            $pdo->prepare('UPDATE notifications SET is_read = ? WHERE id = ?')->execute([$isRead, $notifId]);
            $notice = $isRead ? 'Notification marked read.' : 'Notification marked unread.';
        }
    }
}

$notifications = $pdo->query(
    'SELECT n.*, c.name AS customer_name
     FROM notifications n
     LEFT JOIN customers c ON c.id = n.customer_id
     ORDER BY n.created_at DESC'
)->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<?php if (!empty($notice)): ?><div class="alert alert-info"><?= h($notice) ?></div><?php endif; ?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">View customer notifications and admin alerts.</p>
    <h1>Notifications</h1>
  </div>
  <?php if ($notifications): ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="action" value="mark_all_read">
      <button type="submit" class="btn btn-primary">Mark All as Read</button>
    </form>
  <?php endif; ?>
</div>

<div class="panel-card">
  <div class="panel-header"><h2>Notifications</h2></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Customer</th><th>Type</th><th>Message</th><th>Status</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php if ($notifications): ?>
          <?php foreach ($notifications as $notification): ?>
            <tr>
              <td><?= h($notification['customer_name'] ?? 'System') ?></td>
              <td><?= h($notification['type'] ?? 'General') ?></td>
              <td><?= h($notification['message']) ?></td>
              <td><span class="badge <?= $notification['is_read'] ? 'badge-completed' : 'badge-pending' ?>"><?= $notification['is_read'] ? 'Read' : 'Unread' ?></span></td>
              <td><?= date('M d, Y H:i', strtotime($notification['created_at'])) ?></td>
              <td>
                <form method="post" style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;">
                  <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                  <button type="submit" name="action" value="<?= $notification['is_read'] ? 'mark_unread' : 'mark_read' ?>" class="btn btn-sm btn-outline-light"><?= $notification['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
                  <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete notification?');">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6">No notifications available.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
 