<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_for_flow(basename(__FILE__));

$q_order = trim($_GET['order'] ?? '');
$q_mobile = trim($_GET['mobile'] ?? '');

$orders = [];

if ($q_order !== '') {
    $stmt = $pdo->prepare('SELECT o.*, c.name AS customer_name, c.phone AS customer_phone FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.order_number = ? LIMIT 1');
    $stmt->execute([$q_order]);
    $row = $stmt->fetch();
    if ($row) $orders[] = $row;
} elseif ($q_mobile !== '') {
    // normalize mobile: remove non-digits
    $mobileNorm = preg_replace('/\D+/', '', $q_mobile);
    $stmt = $pdo->prepare('SELECT o.*, c.name AS customer_name, c.phone AS customer_phone FROM orders o JOIN customers c ON c.id = o.customer_id WHERE REPLACE(c.phone, " ", "") LIKE ? ORDER BY o.created_at DESC');
    $stmt->execute(["%" . $mobileNorm . "%"]);
    $orders = $stmt->fetchAll();
}

function fetchTracking(PDO $pdo, $orderId) {
    $tstmt = $pdo->prepare('SELECT status,note,created_at FROM tracking WHERE order_id = ? ORDER BY created_at ASC');
    $tstmt->execute([$orderId]);
    return $tstmt->fetchAll();
}

$allStatuses = [
    'Pending',
    'Waiting Payment',
    'Pending Verification',
    'Payment Verified',
    'Processing',
    'Printing',
    'Ready For Pickup',
    'Completed',
    'Cancelled'
];

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Track Order - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel track-page">
    <section class="track-hero mb-5">
      <span class="eyebrow">Order Tracking</span>
      <h1>Track your Ardave Apparel order with confidence.</h1>
      <p>Enter your order number or mobile number to see status updates, payment verification, and fulfillment progress.</p>
      <form class="row g-3 track-form mt-4" method="get">
        <div class="col-md-5">
          <input name="order" class="form-control" placeholder="Order number" value="<?= h($q_order) ?>">
        </div>
        <div class="col-md-5">
          <input name="mobile" class="form-control" placeholder="Mobile number" value="<?= h($q_mobile) ?>">
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-dark">Search</button>
        </div>
      </form>
    </section>

    <?php if ($q_order === '' && $q_mobile === ''): ?>
      <div class="alert alert-info">Search using order number or mobile number.</div>
    <?php endif; ?>

    <?php if (!empty($orders)): ?>
      <?php foreach ($orders as $order): ?>
        <div class="card mb-4 p-3">
          <div class="d-flex justify-content-between">
            <div>
              <div><strong>Order:</strong> <?= h($order['order_number']) ?></div>
              <div><strong>Customer:</strong> <?= h($order['customer_name'] ?? '') ?> <?= $order['customer_phone'] ? '('.h($order['customer_phone']).')' : '' ?></div>
              <div><strong>Placed:</strong> <?= h($order['created_at']) ?></div>
            </div>
            <div class="text-end">
              <div><strong>Total</strong></div>
              <div class="h5">â‚±<?= number_format($order['total_amount'],2) ?></div>
              <div class="mt-2"><span class="badge bg-secondary">Current: <?= h($order['status']) ?></span></div>
            </div>
          </div>

          <?php $tracking = fetchTracking($pdo, $order['id']); ?>

          <hr>
          <h6>Tracking</h6>
          <div class="row">
            <div class="col-md-4">
              <ul class="list-group">
                <?php foreach ($allStatuses as $s):
                  $reached = false;
                  $statusText = strtolower($order['status'] ?? '');
                  foreach ($tracking as $t) {
                    if (stripos($t['status'], $s) !== false || stripos($order['status'], $s) !== false) { $reached = true; break; }
                  }
                  if (!$reached && $statusText !== '' && strpos($statusText, strtolower($s)) !== false) {
                    $reached = true;
                  }
                ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><?= h($s) ?></div>
                    <div>
                      <?php if ($reached): ?>
                        <span class="badge bg-success">âœ“</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">â€¢</span>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="col-md-8">
              <h6>Timeline</h6>
              <?php if (empty($tracking)): ?>
                <div class="alert alert-info">No tracking updates yet.</div>
              <?php else: ?>
                <ul class="list-group">
                  <?php foreach ($tracking as $t): ?>
                    <li class="list-group-item">
                      <div class="d-flex justify-content-between">
                        <div><strong><?= h($t['status']) ?></strong><?php if (!empty($t['note'])): ?> â€” <?= h($t['note']) ?><?php endif; ?></div>
                        <div class="small text-muted"><?= h($t['created_at']) ?></div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php elseif ($q_order !== '' || $q_mobile !== ''): ?>
      <div class="alert alert-warning">No matching orders found.</div>
    <?php endif; ?>

  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

