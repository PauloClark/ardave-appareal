<?php
require_once __DIR__ . '/config.php';

$page_title = 'Products';

function normalize_admin_product_image(?string $image): string {
    if (empty($image)) {
        return '';
    }
    $image = trim($image);
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    return build_app_url(ltrim($image, '/'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO products (sku, name, description, price, stock, category, image, sizes, colors, is_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            trim($_POST['sku'] ?? ''),
            trim($_POST['name'] ?? ''),
            trim($_POST['description'] ?? ''),
            (float)($_POST['price'] ?? 0),
            (int)($_POST['stock'] ?? 0),
            trim($_POST['category'] ?? ''),
            trim($_POST['image'] ?? ''),
            trim($_POST['sizes'] ?? ''),
            trim($_POST['colors'] ?? ''),
            1,
        ]);
        header('Location: products.php');
        exit;
    }
    if ($action === 'delete' && !empty($_POST['product_id'])) {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([(int)$_POST['product_id']]);
        header('Location: products.php');
        exit;
    }
    if ($action === 'toggle_enabled' && !empty($_POST['product_id'])) {
        $stmt = $pdo->prepare('UPDATE products SET is_enabled = NOT is_enabled WHERE id = ?');
        $stmt->execute([(int)$_POST['product_id']]);
        header('Location: products.php');
        exit;
    }
}

$products = $pdo->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="admin-header">
  <div>
    <p class="text-muted mb-1">Add, edit, and manage product listings.</p>
    <h1>Products</h1>
  </div>
  <button class="btn btn-primary" onclick="document.getElementById('addForm').classList.toggle('hidden')">+ Add Product</button>
</div>

<form id="addForm" class="inline-form hidden" method="POST" action="products.php">
  <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
  <input type="hidden" name="action" value="add">
  <input type="text" name="sku" placeholder="SKU" required>
  <input type="text" name="name" placeholder="Product name" required>
  <input type="text" name="category" placeholder="Category">
  <input type="number" step="0.01" name="price" placeholder="Price" required>
  <input type="number" name="stock" placeholder="Stock qty" required>
  <input type="text" name="sizes" placeholder="Sizes (comma separated)">
  <input type="text" name="colors" placeholder="Colors (comma separated)">
  <input type="text" name="image" placeholder="Image URL">
  <textarea name="description" placeholder="Description"></textarea>
  <button type="submit" class="btn btn-primary">Save Product</button>
</form>

<div class="products-grid">
  <?php if ($products): ?>
    <?php foreach ($products as $p): ?>
      <div class="product-card">
        <?php $imageUrl = normalize_admin_product_image($p['image']); ?>
        <img src="<?= h($imageUrl ?: 'https://via.placeholder.com/320x220?text=No+Image') ?>" alt="<?= h($p['name']) ?>">
        <div class="product-info">
          <h3><?= h($p['name']) ?></h3>
          <p class="product-price">₱<?= number_format($p['price'], 2) ?></p>
          <p>Stock: <?= (int)$p['stock'] ?> · Category: <?= h($p['category'] ?: 'Uncategorized') ?></p>
          <p>Sizes: <?= h($p['sizes'] ?: 'N/A') ?></p>
          <p>Colors: <?= h($p['colors'] ?: 'N/A') ?></p>
          <div class="product-actions" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <form method="post" style="margin:0;">
              <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="action" value="toggle_enabled">
              <button type="submit" class="btn btn-sm btn-outline-light"><?= $p['is_enabled'] ? 'Disable' : 'Enable' ?></button>
            </form>
            <form method="post" style="margin:0;">
              <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?');">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No products found.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '../includes/footer.php'; ?>

