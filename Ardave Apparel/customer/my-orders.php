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
    $orders = [];
} else {
    $stmt = $pdo->prepare('SELECT id,order_number,status,total_amount,created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll();
}

function order_status_badge(string $status): string {
    $map = [
        'Pending'                       => 'bg-warning text-dark',
        'Waiting For Payment Verification' => 'bg-info text-dark',
        'Payment Verified'              => 'bg-primary',
        'Processing'                    => 'bg-primary',
        'Shipped'                       => 'bg-info',
        'Delivered'                     => 'bg-success',
        'Cancelled'                     => 'bg-danger',
        'Completed'                     => 'bg-success',
    ];
    $class = $map[$status] ?? 'bg-secondary';
    $icon = '';
    if (in_array($status, ['Completed', 'Delivered'])) {
        $icon = '<i class="bi bi-check-circle-fill me-1"></i>';
    }
    return '<span class="badge ' . $class . '">' . $icon . h($status) . '</span>';
}

function format_date(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : h($date);
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Orders - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
      <div>
        <a href="<?= h(build_app_url('customer/dashboard.php')) ?>" class="text-decoration-none small">&larr; Back to Dashboard</a>
        <h1 class="h3 mb-0">My Orders</h1>
      </div>
      <a href="<?= h(build_app_url('products/products.php')) ?>" class="btn btn-outline-dark btn-sm">Continue Shopping &rarr;</a>
    </div>

    <?php if (empty($orders)): ?>
      <div class="card card-elevated p-5 text-center">
        <div class="mb-3" style="font-size:3rem;opacity:0.3">&#128722;</div>
        <h4>No orders yet</h4>
        <p class="text-muted mb-4">You haven't placed any orders. Start browsing our products to find something you love!</p>
        <a href="<?= h(build_app_url('products/products.php')) ?>" class="btn btn-dark mx-auto" style="max-width:220px">Browse Products</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>Order #</th>
              <th>Date</th>
              <th>Total</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td class="fw-semibold"><?= h($o['order_number']) ?></td>
                <td><?= format_date($o['created_at']) ?></td>
                <td>&#8369;<?= number_format($o['total_amount'], 2) ?></td>
                <td><?= order_status_badge($o['status']) ?></td>
                <td class="text-end text-nowrap">
                  <?php if (!in_array($o['status'], ['Cancelled'])): ?>
                    <a href="<?= h(build_app_url('tracking/track-order.php?order_number=' . urlencode($o['order_number']))) ?>" class="btn btn-sm btn-outline-primary">Track Order</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
