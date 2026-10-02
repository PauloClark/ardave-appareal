<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

$categories = ['tshirt' => 'T-shirts', 'jersey' => 'Jerseys', 'sublimation' => 'Sublimation', 'dryfit' => 'Dry Fit', 'hoodie' => 'Hoodies'];
$categoryWords = [
    'tshirt' => ['%t-shirt%', '%tshirt%', '%t shirt%'],
    'jersey' => ['%jersey%'],
    'sublimation' => ['%sublim%'],
    'dryfit' => ['%dry fit%', '%dry-fit%', '%dryfit%'],
    'hoodie' => ['%hoodie%', '%warmer%'],
];
$sortOptions = ['newest' => 'Newest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name: A to Z'];
$orderBy = ['newest' => 'created_at DESC, id DESC', 'price_asc' => 'price ASC, id DESC', 'price_desc' => 'price DESC, id DESC', 'name' => 'name ASC, id DESC'];
$search = trim((string)($_GET['q'] ?? ''));
$search = substr($search, 0, 100);
$category = (string)($_GET['category'] ?? '');
$sort = (string)($_GET['sort'] ?? 'newest');
if (!isset($categories[$category])) $category = '';
if (!isset($orderBy[$sort])) $sort = 'newest';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE :search_name OR description LIKE :search_description OR category LIKE :search_category)';
    $params[':search_name'] = '%' . $search . '%';
    $params[':search_description'] = '%' . $search . '%';
    $params[':search_category'] = '%' . $search . '%';
}
if ($category !== '') {
    $parts = [];
    foreach ($categoryWords[$category] as $i => $word) {
        $key = ':category' . $i;
        $parts[] = '(LOWER(name) LIKE ' . $key . ' OR LOWER(category) LIKE ' . $key . 'b)';
        $params[$key] = $word;
        $params[$key . 'b'] = $word;
    }
    $where[] = '(' . implode(' OR ', $parts) . ')';
}
$sql = 'SELECT id,sku,name,description,price,image,category FROM products';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY ' . $orderBy[$sort];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$shopUrl = build_app_url('products/products.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Products - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/editorial.css')) ?>?v=1" rel="stylesheet">
</head>
<body class="ardave-shop">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>
  <main class="container editorial-shop">
    <header class="shop-intro">
      <span class="editorial-kicker">THE ARDAVE COLLECTION / 2026</span>
      <h1>Find your <em>fit.</em></h1>
      <p>Explore apparel made for teams, everyday movement, and the moments that bring people together.</p>
    </header>

    <nav class="shop-categories" aria-label="Product categories">
      <a class="<?= $category === '' ? 'selected' : '' ?>" href="<?= h($shopUrl . '?' . http_build_query(['q' => $search, 'sort' => $sort])) ?>" <?= $category === '' ? 'aria-current="page"' : '' ?>>All products</a>
      <?php foreach ($categories as $key => $label): ?>
        <a class="<?= $category === $key ? 'selected' : '' ?>" href="<?= h($shopUrl . '?' . http_build_query(['q' => $search, 'category' => $key, 'sort' => $sort])) ?>" <?= $category === $key ? 'aria-current="page"' : '' ?>><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="shop-toolbar">
      <p class="shop-count" role="status"><?= count($products) ?> <?= count($products) === 1 ? 'piece' : 'pieces' ?> found</p>
      <form class="shop-filter" method="get" action="<?= h($shopUrl) ?>">
        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= h($category) ?>"><?php endif; ?>
        <label class="visually-hidden" for="productSearch">Search products</label>
        <input name="q" id="productSearch" value="<?= h($search) ?>" type="search" placeholder="Search products" maxlength="100">
        <label class="visually-hidden" for="productSort">Sort products</label>
        <select name="sort" id="productSort">
          <?php foreach ($sortOptions as $key => $label): ?><option value="<?= h($key) ?>" <?= $key === $sort ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
        </select>
        <button type="submit">Apply <span aria-hidden="true">↗</span></button>
      </form>
    </div>

    <?php if (!$products): ?>
      <div class="shop-empty">
        <span aria-hidden="true">A.</span>
        <h2>No products found</h2>
        <p>Try a different search or browse the full collection.</p>
        <a class="editorial-button" href="<?= h($shopUrl) ?>">See all products <span aria-hidden="true">↗</span></a>
      </div>
    <?php else: ?>
      <div class="shop-grid">
        <?php foreach ($products as $p): ?>
          <?php
            $imgFile = trim((string)($p['image'] ?? ''));
            $imgSrc = $imgFile !== '' ? build_app_url(ltrim($imgFile, '/')) : build_app_url('assets/images/jersey.jpg');
          ?>
          <article class="shop-card" data-product-id="<?= (int)$p['id'] ?>" data-product-price="<?= (float)$p['price'] ?>" data-product-name="<?= h($p['name']) ?>">
            <a class="shop-card-image" href="<?= h(build_app_url('products/product-details.php') . '?id=' . (int)$p['id']) ?>" aria-label="View <?= h($p['name']) ?>">
              <img src="<?= h($imgSrc) ?>" alt="<?= h($p['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= h(build_app_url('assets/images/jersey.jpg')) ?>';">
              <span class="shop-image-arrow" aria-hidden="true">↗</span>
            </a>
            <div class="shop-card-body">
              <span class="shop-card-category"><?= h($p['category'] ?: 'Apparel') ?></span>
              <h2><a href="<?= h(build_app_url('products/product-details.php') . '?id=' . (int)$p['id']) ?>"><?= h($p['name']) ?></a></h2>
              <div class="shop-card-price">₱<?= number_format((float)$p['price'], 2) ?></div>
              <div class="shop-card-actions">
                <label class="visually-hidden" for="size-<?= (int)$p['id'] ?>">Size for <?= h($p['name']) ?></label>
                <select id="size-<?= (int)$p['id'] ?>" class="shop-size" aria-label="Size for <?= h($p['name']) ?>">
                  <option value="" selected>Choose size</option>
                  <?php foreach (['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL'] as $size): ?><option value="<?= $size ?>"><?= $size ?></option><?php endforeach; ?>
                </select>
                <button type="button" data-add-to-cart>Add to cart <span aria-hidden="true">+</span></button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="shop-toast" role="status" aria-live="polite" hidden></div>
  </main>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="<?= h(build_app_url('assets/js/cart.js')) ?>"></script>
  <script src="<?= h(build_app_url('assets/js/products.js')) ?>"></script>
  <script>document.addEventListener('DOMContentLoaded', () => window.productsApp?.initProductsPage());</script>
</body>
</html>
