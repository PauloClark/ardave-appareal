<?php
if (!defined('ADMIN_INIT')) {
    die('Unauthorized access.');
}
$activePage = basename($_SERVER['SCRIPT_NAME']);
$navItems = [
    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => 'dashboard.php', 'page' => 'dashboard.php'],
    ['label' => 'Orders', 'icon' => 'bi-bag-check', 'url' => 'orders.php', 'page' => 'orders.php'],
    ['label' => 'Products', 'icon' => 'bi-box-seam', 'url' => 'products.php', 'page' => 'products.php'],
    ['label' => 'Categories', 'icon' => 'bi-tags', 'url' => 'categories.php', 'page' => 'categories.php'],
    ['label' => 'Customers', 'icon' => 'bi-people', 'url' => 'customers.php', 'page' => 'customers.php'],
    ['label' => 'Inventory', 'icon' => 'bi-box-seam-fill', 'url' => 'inventory.php', 'page' => 'inventory.php'],
    ['label' => 'Notifications', 'icon' => 'bi-bell', 'url' => 'notifications.php', 'page' => 'notifications.php'],
    ['label' => 'Reports', 'icon' => 'bi-graph-up', 'url' => 'reports.php', 'page' => 'reports.php'],
    ['label' => 'Settings', 'icon' => 'bi-gear', 'url' => 'settings.php', 'page' => 'settings.php'],
];
?>
  <aside class="admin-sidebar">
    <div class="sidebar-scroll">
      <div class="sidebar-section">
        <h6 class="sidebar-title">Admin Menu</h6>
        <?php foreach ($navItems as $item): ?>
          <a href="<?= h(build_app_url('admin/' . $item['url'])) ?>" class="sidebar-link<?= $activePage === $item['page'] ? ' active' : '' ?>">
            <i class="bi <?= h($item['icon']) ?>"></i>
            <?= h($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
  <main class="admin-content">
