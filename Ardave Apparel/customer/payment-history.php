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
    $payments = [];
} else {
    $stmt = $pdo->prepare('SELECT p.*, o.order_number FROM payments p JOIN orders o ON o.id = p.order_id WHERE o.customer_id = ? ORDER BY p.created_at DESC');
    $stmt->execute([$customer_id]);
    $payments = $stmt->fetchAll();
}

function format_date(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : h($date);
}

function payment_status_badge(string $status): string {
    $map = [
        'pending'  => 'bg-warning text-dark',
        'verified' => 'bg-success',
        'rejected' => 'bg-danger',
        'paid'     => 'bg-success',
    ];
    $class = $map[strtolower($status)] ?? 'bg-secondary';
    $icon = '';
    if (in_array(strtolower($status), ['verified', 'paid'])) {
        $icon = '<i class="bi bi-check-circle-fill me-1"></i>';
    }
    return '<span class="badge ' . $class . '">' . $icon . h(ucfirst($status)) . '</span>';
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment History - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
      <div>
        <a href="<?= h(build_app_url('customer/dashboard.php')) ?>" class="text-decoration-none small">&larr; Back to Dashboard</a>
        <h1 class="h3 mb-0">Payment History</h1>
      </div>
      <a href="<?= h(build_app_url('products/products.php')) ?>" class="btn btn-outline-dark btn-sm">Continue Shopping &rarr;</a>
    </div>

    <?php if (empty($payments)): ?>
      <div class="card card-elevated p-5 text-center">
        <div class="mb-3" style="font-size:3rem;opacity:0.3">&#128179;</div>
        <h4>No payments yet</h4>
        <p class="text-muted mb-4">You haven't made any payments. Place an order to get started!</p>
        <a href="<?= h(build_app_url('products/products.php')) ?>" class="btn btn-dark mx-auto" style="max-width:220px">Browse Products</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>Ref</th>
              <th>Order</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Receipt</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td class="text-nowrap"><?= h($p['transaction_ref'] ?? '—') ?></td>
                <td><?= h($p['order_number'] ?? '') ?></td>
                <td>&#8369;<?= number_format($p['amount'], 2) ?></td>
                <td><?= h(ucfirst($p['method'])) ?></td>
                <td><?= payment_status_badge($p['status']) ?></td>
                <td><?php if (!empty($p['receipt_image'])): ?><?= h($p['receipt_image']) ?><?php else: ?>&mdash;<?php endif; ?></td>
                <td><?= format_date($p['created_at']) ?></td>
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
