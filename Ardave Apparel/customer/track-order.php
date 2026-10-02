<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$order_number = trim($_GET['order'] ?? '');
$order = null;
$tracking = [];

if ($order_number !== '') {
    $s = $pdo->prepare('SELECT o.* FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.order_number = ? AND c.user_id = ? LIMIT 1');
    $s->execute([$order_number, $_SESSION['user_id']]);
    $order = $s->fetch();
    if ($order) {
        $t = $pdo->prepare('SELECT status,note,created_at FROM tracking WHERE order_id = ? ORDER BY created_at ASC');
        $t->execute([$order['id']]);
        $tracking = $t->fetchAll();
    }
}

// If order exists, infer steps
$steps = ['Pending','Payment Verified','Printing','Processing','Completed'];

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Track Order - Ardave Apparel</title>
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <h1 class="h3 mb-4">Track Order</h1>

    <form class="row g-2 mb-4">
      <div class="col-md-6">
        <input name="order" value="<?= h($order_number) ?>" class="form-control" placeholder="Enter order number">
      </div>
      <div class="col-md-2">
        <button class="btn btn-dark">Search</button>
      </div>
    </form>

    <?php if ($order === null && $order_number !== ''): ?>
      <div class="alert alert-warning">Order not found or not accessible.</div>
    <?php endif; ?>

    <?php if ($order): ?>
      <h5>Order <?= h($order['order_number']) ?> â€” Status: <?= h($order['status']) ?></h5>
      <div class="mt-3">
        <?php foreach ($steps as $step): ?>
          <?php
            $done = false;
            foreach ($tracking as $t) { if (stripos($t['status'], $step) !== false) { $done = true; break; } }
          ?>
          <div class="d-flex align-items-center mb-2">
            <div class="me-3">
              <?php if ($done): ?>
                <span class="badge bg-success">âœ“</span>
              <?php else: ?>
                <span class="badge bg-secondary">â€¢</span>
              <?php endif; ?>
            </div>
            <div>
              <div><?= h($step) ?></div>
              <?php if ($done): ?>
                <div class="small text-muted"><?php
                  foreach ($tracking as $t) { if (stripos($t['status'], $step) !== false) { echo h($t['created_at']) . ' â€” ' . h($t['note']); break; } }
                ?></div>
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
