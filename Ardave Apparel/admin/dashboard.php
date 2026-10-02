<?php
require_once __DIR__ . '/config.php';

$page_title = 'Dashboard';

$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending Payment'")->fetchColumn();
$pendingVerification = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending Verification'")->fetchColumn();
$processingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Processing'")->fetchColumn();
$readyForPickup = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Ready for Pickup'")->fetchColumn();
$completedOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Completed'")->fetchColumn();
$cancelledOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'verified'")->fetchColumn();

$dailySales = $pdo->query(
    "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS total
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
       AND status IN ('Processing','Ready for Pickup','Completed')
     GROUP BY day
     ORDER BY day ASC"
)->fetchAll();

$monthlySales = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COALESCE(SUM(total_amount),0) AS total
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
       AND status IN ('Processing','Ready for Pickup','Completed')
     GROUP BY month
     ORDER BY month ASC"
)->fetchAll();

$bestProducts = $pdo->query(
    "SELECT p.name, SUM(oi.quantity) AS units_sold
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     GROUP BY oi.product_id
     ORDER BY units_sold DESC
     LIMIT 5"
)->fetchAll();

$statusCounts = $pdo->query(
    "SELECT status, COUNT(*) AS count
     FROM orders
     GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">Welcome back, <?= h($_SESSION['user_name'] ?? 'Admin') ?></p>
    <h1>Dashboard</h1>
  </div>
</div>

<div class="stats-grid">
  <?php foreach ([
    ['label' => 'Total Orders', 'value' => $totalOrders],
    ['label' => 'Pending Orders', 'value' => $pendingOrders],
    ['label' => 'Pending Verification', 'value' => $pendingVerification],
    ['label' => 'Processing', 'value' => $processingOrders],
    ['label' => 'Ready for Pickup', 'value' => $readyForPickup],
    ['label' => 'Completed Orders', 'value' => $completedOrders],
    ['label' => 'Cancelled Orders', 'value' => $cancelledOrders],
    ['label' => 'Total Customers', 'value' => $totalCustomers],
    ['label' => 'Total Products', 'value' => $totalProducts],
    ['label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 2)],
  ] as $card): ?>
    <div class="stat-card">
      <span class="stat-label"><?= h($card['label']) ?></span>
      <span class="stat-value"><?= h($card['value']) ?></span>
    </div>
  <?php endforeach; ?>
</div>

<div class="row gx-4 gy-4">
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Daily Sales</h2></div>
      <canvas data-admin-chart data-chart-config='<?= h(json_encode([
        'type' => 'line',
        'data' => [
          'labels' => array_map(fn($row) => $row['day'], $dailySales),
          'datasets' => [[
            'label' => 'Sales',
            'data' => array_map(fn($row) => (float)$row['total'], $dailySales),
            'backgroundColor' => 'rgba(96,165,250,0.18)',
            'borderColor' => 'rgba(96,165,250,1)',
            'fill' => true,
            'tension' => 0.35,
          ]],
        ],
        'options' => [
          'responsive' => true,
          'plugins' => ['legend' => ['display' => false]],
          'scales' => ['y' => ['ticks' => ['color' => '#cbd5e1']], 'x' => ['ticks' => ['color' => '#cbd5e1']]],
        ],
      ]), ENT_QUOTES, 'UTF-8') ?>'></canvas>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Monthly Sales</h2></div>
      <canvas data-admin-chart data-chart-config='<?= h(json_encode([
        'type' => 'bar',
        'data' => [
          'labels' => array_map(fn($row) => $row['month'], $monthlySales),
          'datasets' => [[
            'label' => 'Revenue',
            'data' => array_map(fn($row) => (float)$row['total'], $monthlySales),
            'backgroundColor' => 'rgba(34,197,94,0.85)',
          ]],
        ],
        'options' => ['responsive' => true, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['ticks' => ['color' => '#cbd5e1']], 'x' => ['ticks' => ['color' => '#cbd5e1']]]],
      ]), ENT_QUOTES, 'UTF-8') ?>'></canvas>
    </div>
  </div>
</div>

<div class="row gx-4 gy-4 mt-3">
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Best Selling Products</h2></div>
      <table class="admin-table">
        <thead><tr><th>Product</th><th>Units Sold</th></tr></thead>
        <tbody>
          <?php if ($bestProducts): ?>
            <?php foreach ($bestProducts as $product): ?>
              <tr>
                <td><?= h($product['name']) ?></td>
                <td><?= (int)$product['units_sold'] ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="2">No product sales yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Order Status Overview</h2></div>
      <canvas data-admin-chart data-chart-config='<?= h(json_encode([
        'type' => 'doughnut',
        'data' => [
          'labels' => array_keys($statusCounts),
          'datasets' => [[
            'data' => array_values($statusCounts),
            'backgroundColor' => ['#facc15','#38bdf8','#4ade80','#60a5fa','#a78bfa','#f87171'],
          ]],
        ],
        'options' => ['responsive' => true, 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['color' => '#e2e8f0']]]],
      ]), ENT_QUOTES, 'UTF-8') ?>'></canvas>
    </div>
  </div>
</div>

<div class="row gx-4 gy-4 mt-3">
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Recent Orders</h2></div>
      <table class="admin-table">
        <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php
          $recentOrders = $pdo->query(
              "SELECT o.order_number, c.name AS customer_name, o.total_amount, o.status, o.created_at
               FROM orders o LEFT JOIN customers c ON c.id = o.customer_id
               ORDER BY o.created_at DESC LIMIT 5"
          )->fetchAll();
          foreach ($recentOrders as $ro): ?>
            <tr>
              <td>#<?= h($ro['order_number']) ?></td>
              <td><?= h($ro['customer_name'] ?? 'Guest') ?></td>
              <td>₱<?= number_format($ro['total_amount'], 2) ?></td>
              <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $ro['status'])) ?>"><?= h($ro['status']) ?></span></td>
              <td><?= date('M d, Y', strtotime($ro['created_at'])) ?></td>
            </tr>
          <?php endforeach; if (empty($recentOrders)): ?>
            <tr><td colspan="5">No recent orders.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Low Stock Products</h2></div>
      <table class="admin-table">
        <thead><tr><th>Product</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody>
          <?php
          $lowStock = $pdo->query(
              "SELECT name, stock FROM products WHERE stock <= 20 ORDER BY stock ASC LIMIT 5"
          )->fetchAll();
          foreach ($lowStock as $ls): ?>
            <tr>
              <td><?= h($ls['name']) ?></td>
              <td><?= (int)$ls['stock'] ?></td>
              <td><span class="badge <?= $ls['stock'] <= 5 ? 'badge-cancelled' : 'badge-pending' ?>"><?= $ls['stock'] <= 5 ? 'Critical' : 'Low' ?></span></td>
            </tr>
          <?php endforeach; if (empty($lowStock)): ?>
            <tr><td colspan="3">All products well stocked.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
