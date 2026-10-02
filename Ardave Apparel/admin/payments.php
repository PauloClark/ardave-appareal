<?php
require_once __DIR__ . '/config.php';

$page_title = 'Payments';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $notice = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        if ($paymentId > 0 && in_array($action, ['approve','decline'], true)) {
            $status = $action === 'approve' ? 'verified' : 'failed';
            $orderStatus = $action === 'approve' ? 'Payment Verified' : 'Payment Failed';
            try {
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE payments SET status = ? WHERE id = ?')->execute([$status, $paymentId]);
                $p = $pdo->prepare('SELECT order_id, amount FROM payments WHERE id = ? LIMIT 1');
                $p->execute([$paymentId]);
                $prow = $p->fetch();
                if ($prow) {
                    $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$orderStatus, (int)$prow['order_id']]);
                    $pdo->prepare('INSERT INTO transactions (payment_id,type,amount,notes) VALUES (?,?,?,?)')
                        ->execute([$paymentId, $status, $prow['amount'], 'Payment ' . $status . ' by admin ' . ($_SESSION['user_id'] ?? 'unknown')]);
                }
                $pdo->commit();
                $notice = 'Payment status updated.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $notice = 'Unable to update payment status.';
            }
        }
    }
}

$stmt = $pdo->query('SELECT p.*, o.order_number, c.name AS customer_name, oi.product_name FROM payments p LEFT JOIN orders o ON o.id = p.order_id LEFT JOIN customers c ON c.id = o.customer_id LEFT JOIN order_items oi ON oi.order_id = o.id ORDER BY p.created_at DESC');
$payments = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid py-4">
  <h3>Payment Verification</h3>
  <?php if ($notice): ?>
    <div class="alert alert-info"><?= h($notice) ?></div>
  <?php endif; ?>
  <table class="table table-striped">
    <thead>
      <tr><th>Customer</th><th>Order</th><th>Product</th><th>Amount</th><th>Method</th><th>Status</th><th>Receipt</th><th>Transaction #</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php if ($payments): ?>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= h($p['customer_name'] ?? '') ?></td>
            <td><?= h($p['order_number'] ?? '') ?></td>
            <td><?= h($p['product_name'] ?? '') ?></td>
            <td>&#8369;<?= number_format($p['amount'], 2) ?></td>
            <td><?= h(strtoupper($p['method'])) ?></td>
            <td><?php
              $pm = strtolower($p['status']);
              $badgeClass = match($pm) {
                'pending' => 'bg-warning text-dark',
                'verified' => 'bg-success',
                'paid' => 'bg-success',
                'failed' => 'bg-danger',
                default => 'bg-secondary',
              };
              $icon = in_array($pm, ['verified','paid']) ? '<i class="bi bi-check-circle-fill me-1"></i>' : '';
              echo '<span class="badge ' . h($badgeClass) . '">' . $icon . h(ucfirst($p['status'])) . '</span>';
            ?></td>
            <td><?php if (!empty($p['receipt_image'])): ?><?= h($p['receipt_image']) ?><?php else: ?>&mdash;<?php endif; ?></td>
            <td><?= h($p['transaction_ref'] ?? '') ?></td>
            <td>
              <?php if ($p['status'] === 'pending'): ?>
                <form method="POST" action="payments.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-success">Approve Payment</button>
                </form>
                <form method="POST" action="payments.php" style="display:inline;margin-left:6px;">
                  <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
                  <input type="hidden" name="action" value="decline">
                  <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-danger">Decline Payment</button>
                </form>
              <?php else: ?>
                &mdash;
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="9">No payments found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

