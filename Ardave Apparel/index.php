<!doctype html>
<?php
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/functions.php';
$stmt = $pdo->query('SELECT id,name,price,description,image,category FROM products ORDER BY created_at DESC LIMIT 8');
$products = $stmt->fetchAll();
?>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>?v=2" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/editorial.css')) ?>?v=1" rel="stylesheet">
</head>
<body class="ardave-home">
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <main class="site-main">
    <section class="hero-section" aria-labelledby="home-hero-heading">
      <div class="container editorial-hero">
        <div class="editorial-copy">
          <span class="editorial-kicker">ARDAVE APPAREL / DAVAO CITY</span>
          <h1 id="home-hero-heading">Made for <em>your</em> team.</h1>
          <p>Custom jerseys, dry fit shirts, and apparel that carry your colors beyond the game.</p>
          <div class="editorial-actions">
            <a class="editorial-button" href="<?= h(build_app_url('products/products.php')) ?>">Explore the collection <span aria-hidden="true">↗</span></a>
            <a class="editorial-text-link" href="<?= h(build_app_url('tracking/track-order.php')) ?>">Track an order <span aria-hidden="true">→</span></a>
          </div>
          <div class="editorial-note"><span>01 / 04</span><span>Jerseys · Shirts · Hoodies · Printing</span></div>
        </div>
        <div class="editorial-visual">
          <img src="<?= h(build_app_url('assets/images/jersey.jpg')) ?>" alt="Custom basketball jersey made by Ardave Apparel" fetchpriority="high">
          <div class="editorial-visual-caption"><span>FEATURED WORK</span><strong>Team apparel, your way.</strong></div>
          <span class="editorial-visual-index" aria-hidden="true">A / 01</span>
        </div>
      </div>
    </section>

    <section class="feature-section container py-5">
      <div class="section-heading editorial-heading mb-5">
        <span class="eyebrow">Our specialties</span>
        <h2>High-performance apparel for every game</h2>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-sm-6 col-lg-3">
          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-basket3"></i></div>
            <h3>Custom Jerseys</h3>
            <p>Designed to move with your team and branded to stand out.</p>
          </article>
        </div>
        <div class="col-sm-6 col-lg-3">
          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-t-shirt"></i></div>
            <h3>Dry Fit Shirts</h3>
            <p>Breathable, lightweight fabrics that keep you cool under pressure.</p>
          </article>
        </div>
        <div class="col-sm-6 col-lg-3">
          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-journal-richtext"></i></div>
            <h3>Hoodies</h3>
            <p>Soft, premium layering for warmups, recovery, and street-ready style.</p>
          </article>
        </div>
        <div class="col-sm-6 col-lg-3">
          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-brush"></i></div>
            <h3>Sublimation Print</h3>
            <p>Vivid full-color graphics and durable printed details that pop.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="featured-products container py-5" aria-labelledby="featured-products-heading">
      <div class="section-heading editorial-heading mb-5">
        <span class="eyebrow">Featured collection</span>
        <h2 id="featured-products-heading">From our collection</h2>
        <a href="<?= h(build_app_url('products/products.php')) ?>" class="editorial-section-link">View all products <span aria-hidden="true">↗</span></a>
      </div>
      <div class="premium-grid">
        <?php foreach (array_slice($products, 0, 4) as $product): ?>
          <article class="product-card premium">
            <div class="product-image-wrap">
              <img src="<?= h($product['image'] ? build_app_url(ltrim($product['image'], '/')) : build_app_url('assets/images/jersey.jpg')) ?>" alt="<?= h($product['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= h(build_app_url('assets/images/jersey.jpg')) ?>';">
              <span class="product-badge"><?= h($product['category'] ?: 'Apparel') ?></span>
            </div>
            <div class="product-body">
              <h3><?= h($product['name']) ?></h3>
              <p><?= h($product['description']) ?></p>
              <div class="product-meta">
                <span class="product-price">₱<?= number_format($product['price'], 2) ?></span>
              </div>
              <div class="product-actions">
                <a class="btn btn-primary" href="<?= h(build_app_url('products/product-details.php') . '?id=' . (int)$product['id']) ?>">View Details</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php if (!$products): ?>
        <p class="editorial-empty">Products will appear here once they are added. <a href="<?= h(build_app_url('products/products.php')) ?>">Browse the shop</a></p>
      <?php endif; ?>
    </section>

    <section class="size-chart-section container py-5">
      <div class="section-heading text-center mb-5">
        <span class="eyebrow">Size guide</span>
        <h2>POLOSHIRT &amp; T-SHIRT / JERSEY</h2>
      </div>
      <div class="row g-4">
        <div class="col-md-6">
          <article class="chart-card">
            <h3>POLOSHIRT &amp; T-SHIRT</h3>
            <div class="table-responsive">
              <table class="size-chart-table">
                <thead>
                  <tr>
                    <th>SIZE</th>
                    <th>WIDTH (in)</th>
                    <th>LENGTH (in)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>XXS</td><td>34</td><td>24</td></tr>
                  <tr><td>XS</td><td>36</td><td>25</td></tr>
                  <tr><td>S</td><td>38</td><td>26</td></tr>
                  <tr><td>M</td><td>40</td><td>27</td></tr>
                  <tr><td>L</td><td>42</td><td>28</td></tr>
                  <tr><td>XL</td><td>44</td><td>29</td></tr>
                  <tr><td>XXL</td><td>46</td><td>30</td></tr>
                  <tr><td>3XL</td><td>48</td><td>31</td></tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>
        <div class="col-md-6">
          <article class="chart-card">
            <h3>JERSEY</h3>
            <div class="table-responsive">
              <table class="size-chart-table">
                <thead>
                  <tr>
                    <th>SIZE</th>
                    <th>WIDTH (in)</th>
                    <th>LENGTH (in)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>XXS</td><td>38</td><td>27</td></tr>
                  <tr><td>XS</td><td>40</td><td>28</td></tr>
                  <tr><td>S</td><td>42</td><td>29</td></tr>
                  <tr><td>M</td><td>44</td><td>30</td></tr>
                  <tr><td>L</td><td>46</td><td>31</td></tr>
                  <tr><td>XL</td><td>48</td><td>32</td></tr>
                  <tr><td>XXL</td><td>50</td><td>33</td></tr>
                  <tr><td>3XL</td><td>52</td><td>34</td></tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
