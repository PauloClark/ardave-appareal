<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$baseUrl = str_replace($documentRoot, '', $projectRoot);
$baseUrl = '/' . trim($baseUrl, '/');
if ($baseUrl === '/') {
    $baseUrl = '';
}

function build_url(string $path): string {
    global $baseUrl;
    $base = trim($baseUrl, '/');
    $path = trim($path, '/');
    $segments = $base !== '' ? array_merge(explode('/', $base), explode('/', $path)) : explode('/', $path);
    return '/' . implode('/', array_map('rawurlencode', $segments));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo 'Product not found';
    exit;
}

$stmt = $pdo->prepare('SELECT id,sku,name,description,price,image,stock,category FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    http_response_code(404);
    echo 'Product not found';
    exit;
}

// load sizes
$sizesStmt = $pdo->query('SELECT size_label FROM sizes ORDER BY FIELD(size_label, "XS","S","M","L","XL","2XL","3XL","4XL","5XL","6XL")');
$sizes = $sizesStmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($product['name']) ?> - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_url('assets/css/style.css')) ?>" rel="stylesheet">
  <link href="<?= h(build_url('assets/css/products.css')) ?>" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/editorial.css')) ?>?v=1" rel="stylesheet">
</head>
<body class="ardave-detail">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <div class="detail-crumb"><a href="<?= h(build_app_url('products/products.php')) ?>">Collection</a> <span aria-hidden="true">/</span> <?= h($product['name']) ?></div>
    <div class="row g-4">
      <div class="col-12 col-md-6">
        <?php $detailImage = $product['image'] ? build_url(ltrim($product['image'], '/')) : build_url('assets/images/jersey.jpg'); ?>
        <div class="card">
          <img src="<?= h($detailImage) ?>" class="card-img-top img-fluid" alt="<?= h($product['name']) ?>">
        </div>
      </div>
      <div class="col-12 col-md-6">
        <h2><?= h($product['name']) ?></h2>
        <p class="text-muted"><?= h($product['category'] ?? '') ?></p>
        <p class="lead">₱<?= number_format($product['price'],2) ?></p>
        <p><?= nl2br(h($product['description'])) ?></p>

        <form id="productForm" class="mt-4" method="post" action="<?= h(build_url('cart/checkout.php')) ?>">
          <input type="hidden" name="cart" id="cartInput" value="[]">
          <div class="mb-3">
            <label class="form-label">Size</label>
            <select id="sizeSelect" class="form-select">
              <?php foreach ($sizes as $s): ?>
                <option value="<?= h($s) ?>"><?= h($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input id="qty" type="number" class="form-control" value="1" min="1">
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between">
              <label class="form-label mb-0">Total</label>
              <small id="calcSmall" class="text-muted"></small>
            </div>
            <div class="h4" id="totalAmount">₱<?= number_format($product['price'],2) ?></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Additional Notes (optional)</label>
            <textarea id="notes" class="form-control" rows="3"></textarea>
          </div>

          <div class="d-flex gap-2">
            <button id="addToCartBtn" type="button" class="btn btn-outline-dark">Add to cart</button>
            <button id="buyNowBtn" type="button" class="btn btn-dark">Buy now</button>
          </div>
        </form>

        <div class="mt-4 pt-3 border-top">
          <a href="<?= h(build_url('products/products.php')) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Products</a>
          <a href="<?= h(build_url('products/products.php')) ?>" class="btn btn-outline-dark btn-sm">Continue Shopping &rarr;</a>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script src="<?= h(build_url('assets/js/cart.js')) ?>"></script>
  <script>
  (function(){
    const price = <?= (float)$product['price'] ?>;
    const qtyEl = document.getElementById('qty');
    const totalEl = document.getElementById('totalAmount');
    const calcSmall = document.getElementById('calcSmall');

    function renderTotal(){
      const q = Math.max(1, parseInt(qtyEl.value || '1',10));
      const total = q * price;
      totalEl.textContent = '₱' + total.toFixed(2);
      calcSmall.textContent = `₱${price.toFixed(2)} × ${q} = ₱${total.toFixed(2)}`;
    }

    qtyEl.addEventListener('input', renderTotal);
    renderTotal();

    // read file as dataURL helper
    function readFileAsDataURL(file){
      return new Promise((resolve) => {
        if (!file) return resolve(null);
        const fr = new FileReader();
        fr.onload = () => resolve(fr.result);
        fr.onerror = () => resolve(null);
        fr.readAsDataURL(file);
      });
    }

    async function collectProductPayload(){
      const size = document.getElementById('sizeSelect').value;
      const qty = Math.max(1, parseInt(qtyEl.value||'1',10));
      const notes = document.getElementById('notes').value.trim();

      return {
        id: <?= json_encode((string)$product['id']) ?>,
        product_id: <?= (int)$product['id'] ?>,
        name: <?= json_encode($product['name']) ?>,
        price: <?= (float)$product['price'] ?>,
        image: <?= json_encode($product['image'] ?: '/assets/images/placeholder.png') ?>,
        size: size,
        quantity: qty,
        notes: notes,
      };
    }

    document.getElementById('addToCartBtn').addEventListener('click', async function(e){
      e.preventDefault();
      const payload = await collectProductPayload();
      window.cartApp?.addToCart(payload);
      alert(payload.name + ' added to cart.');
    });

    document.getElementById('buyNowBtn').addEventListener('click', async function(e){
      e.preventDefault();
      const payload = await collectProductPayload();
      const cart = [payload];
      document.getElementById('cartInput').value = JSON.stringify(cart);
      document.getElementById('productForm').submit();
    });
  })();
  </script>
</body>
</html>

