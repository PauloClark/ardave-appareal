<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_for_flow(basename(__FILE__));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Your Cart - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>
  <main class="container page-panel py-5">
    <div class="row g-4">
      <div class="col-lg-8">
        <h2 class="section-title mb-3">Your Cart</h2>
        <div class="card card-elevated p-4">
          <div class="cart-items"></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card card-elevated p-4">
          <h4 class="mb-3">Order Summary</h4>
          <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span class="cart-subtotal">₱0.00</span></div>
          <div class="d-flex justify-content-between mb-2"><span>Delivery</span><span>Calculated at checkout</span></div>
          <hr>
          <div class="d-flex justify-content-between fw-bold"><span>Total</span><span class="cart-total">₱0.00</span></div>
          <div class="d-grid gap-2 mt-4">
            <a class="btn btn-dark" href="<?= h(build_app_url('cart/checkout.php')) ?>">Proceed to Checkout</a>
            <a class="btn btn-outline-dark" href="<?= h(build_app_url('products/products.php')) ?>">Continue Shopping</a>
          </div>
        </div>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="<?= h(build_app_url('assets/js/cart.js')) ?>"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => window.cartApp?.initCartPage());
  </script>
</body>
</html>
