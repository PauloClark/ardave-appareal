<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$custStmt = $pdo->prepare('SELECT id FROM customers WHERE user_id = ? LIMIT 1');
$custStmt->execute([$user_id]);
$cust = $custStmt->fetch();
$customer_id = $cust['id'] ?? null;

if (!$customer_id) {
    $notifications = [];
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
        if (!verify_csrf_token($_POST['csrf_token'])) {
            header('Location: ' . build_app_url('customer/notifications.php'));
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
        $nid = (int)($_POST['nid'] ?? 0);
        if ($nid > 0) {
            $u = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND customer_id = ?');
            $u->execute([$nid, $customer_id]);
        }
        if (!empty($_POST['mark_all'])) {
            $u2 = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE customer_id = ?');
            $u2->execute([$customer_id]);
        }
        header('Location: ' . build_app_url('customer/notifications.php'));
        exit;
    }

    $stmt = $pdo->prepare('SELECT id,type,message,is_read,created_at FROM notifications WHERE customer_id = ? ORDER BY created_at DESC');
    $stmt->execute([$customer_id]);
    $notifications = $stmt->fetchAll();
}

$csrfToken = get_csrf_token();

function format_date(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('M j, Y g:ia', $ts) : h($date);
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Notifications - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <a href="<?= h(build_app_url('customer/dashboard.php')) ?>" class="text-decoration-none small">&larr; Back to Dashboard</a>
    <h1 class="h3 mb-4">Notifications</h1>

    <?php if (empty($notifications)): ?>
      <div class="card card-elevated p-5 text-center">
        <div class="mb-3" style="font-size:3rem;opacity:0.3">&#128276;</div>
        <h4>No notifications</h4>
        <p class="text-muted mb-0">You're all caught up! Notifications about your orders will appear here.</p>
      </div>
    <?php else: ?>
      <form method="post" class="mb-3">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <button name="mark_all" value="1" class="btn btn-sm btn-outline-dark">Mark all read</button>
      </form>
      <div class="list-group">
        <?php foreach ($notifications as $n): ?>
          <div class="list-group-item d-flex justify-content-between align-items-start <?= $n['is_read'] ? '' : 'list-group-item-light' ?>">
            <div>
              <div class="fw-bold"><?= h($n['type']) ?><?= $n['is_read'] ? '' : ' <span class="badge bg-danger">New</span>' ?></div>
              <div class="small text-muted"><?= h($n['message']) ?></div>
              <div class="small text-muted mt-1"><?= format_date($n['created_at']) ?></div>
            </div>
            <div>
              <?php if (!$n['is_read']): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                  <input type="hidden" name="nid" value="<?= (int)$n['id'] ?>">
                  <button name="mark_read" value="1" class="btn btn-sm btn-primary">Mark read</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
