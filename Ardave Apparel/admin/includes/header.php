<?php
if (!defined('ADMIN_INIT')) {
    die('Unauthorized access.');
}
$user = current_user($pdo) ?: ['name' => 'Admin'];
$activePage = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel — <?= h($page_title ?? 'Dashboard') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= h(build_app_url('admin/assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="admin-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <div class="admin-topbar">
    <div class="admin-brand">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
      </button>
      <a href="<?= h(build_app_url('admin/dashboard.php')) ?>">ARDAVE APPAREL</a>
      <span class="admin-badge">Admin Center</span>
    </div>
    <div class="admin-topbar-actions">
      <div class="admin-user">
        <span class="admin-avatar"><i class="bi bi-person-circle"></i></span>
        <div>
          <strong><?= h($user['name'] ?? $user['email'] ?? 'Admin') ?></strong>
          <small><?= h($_SESSION['user_email'] ?? '') ?></small>
        </div>
      </div>
      <div class="admin-action-buttons">
        <a class="btn btn-sm btn-outline-light" href="<?= h(build_app_url('index.php')) ?>" target="_blank">View Store</a>
        <form method="post" action="<?= h(build_app_url('authentication/logout.php')) ?>" class="m-0">
          <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
          <button type="submit" class="btn btn-sm btn-danger">Logout</button>
        </form>
      </div>
    </div>
  </div>
  <div class="admin-layout">
