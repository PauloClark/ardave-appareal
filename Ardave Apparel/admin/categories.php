<?php
require_once __DIR__ . '/config.php';

$page_title = 'Categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    if (!empty($_POST['action']) && $_POST['action'] === 'rename_category') {
        $old = trim($_POST['old_category'] ?? '');
        $new = trim($_POST['new_category'] ?? '');
        if ($old !== '' && $new !== '') {
            if ($old === '::EMPTY::') {
                $stmt = $pdo->prepare('UPDATE products SET category = ? WHERE category IS NULL OR category = ?');
                $stmt->execute([$new, '']);
            } else {
                $stmt = $pdo->prepare('UPDATE products SET category = ? WHERE category = ?');
                $stmt->execute([$new, $old]);
            }
            $notice = 'Category renamed successfully.';
        }
    }
}

$categories = $pdo->query(
    'SELECT category, COALESCE(NULLIF(category, ""), "Uncategorized") AS display_category, COUNT(*) AS total
     FROM products
     GROUP BY category
     ORDER BY total DESC'
)->fetchAll();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">Keep your product category labels consistent.</p>
    <h1>Categories</h1>
  </div>
</div>

<?php if (!empty($notice)): ?><div class="alert alert-info"><?= h($notice) ?></div><?php endif; ?>

<table class="admin-table">
  <thead><tr><th>Category</th><th>Products</th><th>Actions</th></tr></thead>
  <tbody>
    <?php if ($categories): ?>
      <?php foreach ($categories as $cat): ?>
        <tr>
          <td><?= h($cat['display_category']) ?></td>
          <td><?= (int)$cat['total'] ?></td>
          <td>
            <form method="post" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
              <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="rename_category">
              <input type="hidden" name="old_category" value="<?= h($cat['category'] === null || $cat['category'] === '' ? '::EMPTY::' : $cat['category']) ?>">
              <input type="text" name="new_category" placeholder="Rename to" class="form-control form-control-sm" required>
              <button type="submit" class="btn btn-sm btn-primary">Rename</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="3">No categories defined yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
