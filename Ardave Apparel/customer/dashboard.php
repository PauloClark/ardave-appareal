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
    $totalOrders = 0; $pendingOrders = 0; $completedOrders = 0;
} else {
    $countSql = <<<SQL
SELECT
  COUNT(*) AS total_orders,
  SUM(status = 'Pending') AS pending_orders,
  SUM(CASE WHEN status NOT IN ('Pending','Cancelled') THEN 1 ELSE 0 END) AS completed_orders
FROM orders
WHERE customer_id = ?
SQL;
    $cstmt = $pdo->prepare($countSql);
    $cstmt->execute([$customer_id]);
    $counts = $cstmt->fetch();
    $totalOrders = (int)($counts['total_orders'] ?? 0);
    $pendingOrders = (int)($counts['pending_orders'] ?? 0);
    $completedOrders = (int)($counts['completed_orders'] ?? 0);

    $notifStmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM notifications WHERE customer_id = ? AND is_read = 0');
    $notifStmt->execute([$customer_id]);
    $unreadCount = (int)($notifStmt->fetch()['cnt'] ?? 0);
}

$user = current_user($pdo);
$csrfToken = get_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <div class="card card-elevated p-4 mb-4">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <div class="eyebrow">My Account</div>
          <h1 class="h3 mb-2">Welcome, <?= h($user['name'] ?? $user['email'] ?? 'Customer') ?></h1>
          <p class="text-muted mb-0"><?= h($user['email'] ?? '') ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="my-orders.php" class="btn btn-outline-dark btn-sm">My Orders</a>
          <a href="payment-history.php" class="btn btn-outline-dark btn-sm">Payment History</a>
          <a href="edit-profile.php" class="btn btn-outline-dark btn-sm">Edit Profile</a>
          <a href="notifications.php" class="btn btn-outline-dark btn-sm position-relative">
            Notifications
            <?php if (!empty($unreadCount)): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
              </span>
            <?php endif; ?>
          </a>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-4"><div class="stat-box"><div class="text-muted small">Total Orders</div><div class="h4"><?= $totalOrders ?></div></div></div>
      <div class="col-12 col-md-4"><div class="stat-box"><div class="text-muted small">Pending Orders</div><div class="h4 text-warning"><?= $pendingOrders ?></div></div></div>
      <div class="col-12 col-md-4"><div class="stat-box"><div class="text-muted small">Completed Orders</div><div class="h4 text-success"><i class="bi bi-check-circle-fill me-1"></i><?= $completedOrders ?></div></div></div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6"><a href="my-orders.php" class="btn btn-dark w-100">View My Orders</a></div>
      <div class="col-md-6"><a href="../tracking/track-order.php" class="btn btn-outline-primary w-100">Track Order</a></div>
      <div class="col-md-6"><a href="../cart/cart.php" class="btn btn-outline-dark w-100">My Cart</a></div>
      <div class="col-md-6"><a href="../products/products.php" class="btn btn-outline-dark w-100">Continue Shopping</a></div>
      <div class="col-md-6">
        <form method="post" action="<?= h(build_app_url('authentication/logout.php')) ?>" class="d-grid">
          <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
          <button type="submit" class="btn btn-outline-danger w-100">Logout</button>
        </form>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
