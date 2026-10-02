<?php
require_once __DIR__ . '/config.php';

$page_title = 'Customers';

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_account' && !empty($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?');
        $stmt->execute([$userId]);
        $notice = 'Customer account status updated.';
    }
}

$searchTerm = trim($_GET['q'] ?? '');

$sql =
    'SELECT u.id AS user_id, u.email, u.name AS user_name, u.phone AS user_phone, u.role, u.created_at, u.is_active, COUNT(o.id) AS order_count
     FROM users u
     LEFT JOIN customers c ON c.user_id = u.id
     LEFT JOIN orders o ON o.customer_id = c.id
     WHERE u.role = ?';
$params = ['customer'];
if ($searchTerm !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $like = "%$searchTerm%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' GROUP BY u.id ORDER BY u.created_at DESC';

$statement = $pdo->prepare($sql);
$statement->execute($params);
$customers = $statement->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<?php if (!empty($notice)): ?><div class="alert alert-info"><?= h($notice) ?></div><?php endif; ?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">Review registered customers and manage accounts.</p>
    <h1>Customers</h1>
  </div>
</div>

<form method="get" action="customers.php" class="mb-3">
  <div style="display:flex; gap:1rem; align-items:flex-end; max-width:480px;">
    <div style="flex:1;">
      <label class="form-label" for="q">Search Customers</label>
      <input type="text" class="form-control" name="q" id="q" value="<?= h($searchTerm) ?>" placeholder="Name, email, or phone">
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($searchTerm !== ''): ?>
      <a href="customers.php" class="btn btn-outline-light">Clear</a>
    <?php endif; ?>
  </div>
</form>

<div class="panel-card">
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Member Since</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if ($customers): ?>
          <?php foreach ($customers as $customer): ?>
            <tr>
              <td><?= h($customer['user_name'] ?: 'N/A') ?></td>
              <td><?= h($customer['email']) ?></td>
              <td><?= h($customer['user_phone'] ?: 'N/A') ?></td>
              <td><?= (int)$customer['order_count'] ?></td>
              <td><?= date('M d, Y', strtotime($customer['created_at'])) ?></td>
              <td><span class="badge <?= $customer['is_active'] ? 'badge-processing' : 'badge-cancelled' ?>"><?= $customer['is_active'] ? 'Active' : 'Disabled' ?></span></td>
              <td>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                  <a href="orders.php?q=<?= urlencode($customer['user_name'] ?: $customer['email']) ?>" class="btn btn-sm btn-outline-light">View Orders</a>
                  <form method="post" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="toggle_account">
                    <input type="hidden" name="user_id" value="<?= (int)$customer['user_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-light"><?= $customer['is_active'] ? 'Disable' : 'Enable' ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7">No customers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
