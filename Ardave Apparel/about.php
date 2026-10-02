<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>About Us - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <section class="row g-5 align-items-center">
      <div class="col-lg-7">
        <div class="eyebrow">Since 2018</div>
        <h1 class="section-title">ARDAVE APPAREL</h1>
        <p class="section-subtitle">Ardave Apparel is a premium custom apparel business specializing in Jerseys, Dry Fit Shirts, Hoodies, and Team Uniforms for schools, companies, organizations, and events.</p>
        <p class="section-subtitle">We are committed to delivering premium quality products with affordable prices, fast production, and excellent customer service.</p>
      </div>
      <div class="col-lg-5">
        <div class="card card-elevated p-4">
          <h5 class="mb-3">What We Offer</h5>
          <ul class="mb-0 ps-3 text-muted">
            <li>Custom jerseys and sublimation apparel</li>
            <li>Dry fit shirts and team uniforms</li>
            <li>Premium hoodies and corporate wear</li>
            <li>Fast production and custom design support</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="mt-5">
      <h3 class="mb-3">Available Sizes</h3>
      <div class="d-flex flex-wrap gap-2">
        <?php $sizes = ['XS','S','M','L','XL','2XL','3XL','4XL','5XL','6XL']; foreach ($sizes as $s): ?>
          <span class="badge rounded-pill dark-badge px-3 py-2"><?= h($s) ?></span>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

