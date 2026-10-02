<?php
require_once __DIR__ . '/config.php';

$page_title = 'Settings';

$siteName = get_config('SITE_NAME') ?: 'Ardave Apparel';
$adminEmail = get_config('ADMIN_EMAIL') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $stmt = $pdo->prepare("REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())");
    $stmt->execute(['SITE_NAME', trim($_POST['site_name'] ?? $siteName)]);
    $stmt->execute(['ADMIN_EMAIL', trim($_POST['admin_email'] ?? $adminEmail)]);
    header('Location: settings.php?updated=1');
    exit;
}

$stmt = $pdo->prepare('SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN (?, ?)');
$stmt->execute(['SITE_NAME', 'ADMIN_EMAIL']);
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['SITE_NAME'] ?? $siteName;
$adminEmail = $settings['ADMIN_EMAIL'] ?? $adminEmail;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">Update basic site settings for Ardave Apparel.</p>
    <h1>Settings</h1>
  </div>
</div>

<?php if (!empty($_GET['updated'])): ?>
  <div class="alert alert-success">Settings saved successfully.</div>
<?php endif; ?>

<div class="panel-card" style="padding:2rem;">
  <form method="post" style="max-width:700px;">
    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
    <div class="mb-3">
      <label class="form-label">Store Name</label>
      <input class="form-control" type="text" name="site_name" value="<?= h($siteName) ?>" placeholder="Store Name">
    </div>
    <div class="mb-3">
      <label class="form-label">Admin Email</label>
      <input class="form-control" type="email" name="admin_email" value="<?= h($adminEmail) ?>" placeholder="admin@example.com">
    </div>
    <button type="submit" class="btn btn-primary">Save Settings</button>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
