<?php
require_once __DIR__ . '/config.php';

$page_title = 'Reports';

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
    'SELECT p.name, SUM(oi.quantity) AS units_sold, SUM(oi.quantity * oi.unit_price) AS revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     GROUP BY oi.product_id
     ORDER BY units_sold DESC
     LIMIT 10'
)->fetchAll();

$stmt = $pdo->prepare(
    'SELECT u.name AS customer, u.email, COUNT(o.id) AS orders, COALESCE(SUM(o.total_amount),0) AS revenue
     FROM users u
     JOIN customers c ON c.user_id = u.id
     LEFT JOIN orders o ON o.customer_id = c.id
     WHERE u.role = ?
     GROUP BY u.id
     ORDER BY revenue DESC
     LIMIT 10'
);
$stmt->execute(['customer']);
$topCustomers = $stmt->fetchAll();

$stats = $pdo->query(
    "SELECT COALESCE(SUM(o.total_amount),0) AS total_revenue,
            COUNT(*) AS total_orders,
            COALESCE(SUM(oi_sub.units),0) AS total_products_sold
     FROM orders o
     LEFT JOIN (SELECT order_id, SUM(quantity) AS units FROM order_items GROUP BY order_id) oi_sub ON oi_sub.order_id = o.id
     WHERE o.status IN ('Processing','Ready for Pickup','Completed')"
)->fetch();
$avgOrderValue = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">Reports let you analyze sales performance and customer trends.</p>
    <h1>Reports</h1>
  </div>
  <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
    <button type="button" class="btn btn-outline-light" onclick="window.print()">Print Report</button>
    <button type="button" class="btn btn-primary" onclick="exportCSV()">Export as CSV</button>
  </div>
</div>

<div class="row gx-4 gy-4">
  <div class="col-12">
    <div class="panel-card">
      <div class="panel-header"><h2>Summary</h2></div>
      <div class="stats-grid">
        <div class="stat-card stat-green">
          <span class="stat-label">Total Revenue</span>
          <span class="stat-value">₱<?= number_format((float)$stats['total_revenue'], 2) ?></span>
        </div>
        <div class="stat-card stat-blue">
          <span class="stat-label">Average Order Value</span>
          <span class="stat-value">₱<?= number_format((float)$avgOrderValue, 2) ?></span>
        </div>
        <div class="stat-card stat-amber">
          <span class="stat-label">Total Orders</span>
          <span class="stat-value"><?= (int)$stats['total_orders'] ?></span>
        </div>
        <div class="stat-card stat-purple">
          <span class="stat-label">Total Products Sold</span>
          <span class="stat-value"><?= (int)$stats['total_products_sold'] ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row gx-4 gy-4 mt-3">
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Daily Sales</h2></div>
      <canvas data-admin-chart data-chart-config='<?= h(json_encode([
        'type' => 'line',
        'data' => [
          'labels' => array_map(fn($row) => $row['day'], $dailySales),
          'datasets' => [[
            'label' => 'Daily Revenue',
            'data' => array_map(fn($row) => (float)$row['total'], $dailySales),
            'backgroundColor' => 'rgba(59,130,246,0.2)',
            'borderColor' => 'rgba(59,130,246,1)',
            'fill' => true,
          ]],
        ],
        'options' => ['responsive' => true, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['ticks' => ['color' => '#cbd5e1']], 'x' => ['ticks' => ['color' => '#cbd5e1']]]],
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
            'label' => 'Monthly Revenue',
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
      <div class="table-responsive">
        <table class="admin-table" id="best-products-table">
          <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
          <tbody>
            <?php if ($bestProducts): ?>
              <?php foreach ($bestProducts as $product): ?>
                <tr>
                  <td><?= h($product['name']) ?></td>
                  <td><?= (int)$product['units_sold'] ?></td>
                  <td>₱<?= number_format((float)$product['revenue'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="3">No sales data available.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="panel-card">
      <div class="panel-header"><h2>Top Customers</h2></div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Customer</th><th>Orders</th><th>Revenue</th></tr></thead>
          <tbody>
            <?php if ($topCustomers): ?>
              <?php foreach ($topCustomers as $customer): ?>
                <tr>
                  <td><?= h($customer['customer']) ?></td>
                  <td><?= (int)$customer['orders'] ?></td>
                  <td>₱<?= number_format((float)$customer['revenue'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="3">No top customer data yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function exportCSV() {
  const rows = [['Product', 'Units Sold', 'Revenue']];
  document.querySelectorAll('#best-products-table tbody tr').forEach(function (tr) {
    const cells = tr.querySelectorAll('td');
    if (cells.length < 3) return;
    rows.push([
      cells[0].textContent.trim(),
      cells[1].textContent.trim(),
      cells[2].textContent.replace(/[₱,]/g, '').trim()
    ]);
  });
  const csv = rows.map(function (row) {
    return row.map(function (cell) { return '"' + String(cell).replace(/"/g, '""') + '"'; }).join(',');
  }).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'best-selling-products.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(a.href);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>