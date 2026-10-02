<?php
require_once __DIR__ . '/config.php';

$page_title = 'Orders';

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $notice = 'Invalid request token.';
    } else {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $allowed = ['Pending Payment','Pending Verification','Payment Verified','Processing','Ready for Pickup','Completed','Cancelled','Payment Failed','Refunded'];
        if ($action === 'update_status' && $orderId > 0) {
            $status = trim($_POST['status'] ?? '');
            if (in_array($status, $allowed, true)) {
                $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
                if ($status === 'Payment Verified') {
                    $pdo->prepare("UPDATE payments SET status = 'verified' WHERE order_id = ? AND status = 'pending'")->execute([$orderId]);
                }
                if ($status === 'Cancelled') {
                    $pdo->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ? AND status = 'pending'")->execute([$orderId]);
                }
                $notice = 'Order status updated.';
            }
        }
        if ($action === 'cancel_order' && $orderId > 0) {
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute(['Cancelled', $orderId]);
            $pdo->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ? AND status = 'pending'")->execute([$orderId]);
            $notice = 'Order cancelled.';
        }
    }
}

$searchTerm = trim($_GET['q'] ?? '');

$ordersSql =
    'SELECT o.*, c.name AS customer_name, c.email AS customer_email,
            (SELECT p.status FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_status,
            (SELECT p.receipt_image FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS receipt_image
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id';
$params = [];
if ($searchTerm !== '') {
    $like = "%$searchTerm%";
    $ordersSql .= ' WHERE o.id LIKE ? OR c.name LIKE ? OR c.email LIKE ?';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$ordersSql .= ' ORDER BY o.created_at DESC';
$ordersStmt = $pdo->prepare($ordersSql);
$ordersStmt->execute($params);
$orders = $ordersStmt->fetchAll();

$statuses = ['Pending Payment','Pending Verification','Payment Verified','Processing','Ready for Pickup','Completed','Cancelled'];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<?php if (!empty($notice)): ?><div class="alert alert-info"><?= h($notice) ?></div><?php endif; ?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">Manage payment and fulfillment stages.</p>
    <h1>Orders</h1>
  </div>
</div>

<form method="get" action="orders.php" class="mb-3">
  <div style="display:flex; gap:1rem; align-items:flex-end; max-width:480px;">
    <div style="flex:1;">
      <label class="form-label" for="q">Search Orders</label>
      <input type="text" class="form-control" name="q" id="q" value="<?= h($searchTerm) ?>" placeholder="Order # or customer name">
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($searchTerm !== ''): ?>
      <a href="orders.php" class="btn btn-outline-light">Clear</a>
    <?php endif; ?>
  </div>
</form>

<div class="panel-card">
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Payment</th><th>Receipt</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if ($orders): ?>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>#<?= (int)$order['id'] ?></td>
              <td><?= h($order['customer_name'] ?: $order['customer_email'] ?: 'Guest') ?></td>
              <td>₱<?= number_format($order['total_amount'], 2) ?></td>
              <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>"><?= h($order['status']) ?></span></td>
              <td><?= h($order['payment_status'] ?? 'pending') ?></td>
              <td>
                <?php if (!empty($order['receipt_image'])): ?>
                  <a href="<?= h(build_app_url($order['receipt_image'])) ?>" target="_blank">View</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
              <td>
                <form method="post" style="display:grid; gap:0.5rem;">
                  <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                  <input type="hidden" name="action" value="update_status">
                  <select name="status" onchange="this.form.submit()">
                    <?php foreach ($statuses as $status): ?>
                      <option value="<?= h($status) ?>" <?= $status === $order['status'] ? 'selected' : '' ?>><?= h($status) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <?php if (!empty($order['customer_email'])): ?>
                  <a href="customers.php" class="btn btn-sm btn-outline-light" style="margin-top:0.5rem;">View Customer</a>
                <?php endif; ?>
                <?php if ($order['status'] !== 'Cancelled' && $order['status'] !== 'Completed'): ?>
                  <form method="post" onsubmit="return confirm('Cancel this order?');" style="margin-top:0.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                    <input type="hidden" name="action" value="cancel_order">
                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
