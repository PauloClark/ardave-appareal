<?php
require_once __DIR__ . '/functions.php';

$user = is_logged_in() ? current_user($pdo) : null;
$profilePicture = $_SESSION['user_profile_picture'] ?? '';
$loginProvider = $_SESSION['user_login_provider'] ?? 'email';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-premium sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-3" href="<?= h(build_app_url('index.php')) ?>">
      <span class="brand-mark">A</span>
      <span class="brand-name">ARDAVE APPAREL</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 nav-premium">
        <li class="nav-item"><a class="nav-link premium-nav-link" href="<?= h(build_app_url('index.php')) ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link premium-nav-link" href="<?= h(build_app_url('products/products.php')) ?>">Products</a></li>
        <li class="nav-item"><a class="nav-link premium-nav-link" href="<?= h(build_app_url('tracking/track-order.php')) ?>">Track</a></li>
        <li class="nav-item"><a class="nav-link premium-nav-link" href="<?= h(build_app_url('about.php')) ?>">About</a></li>
      </ul>

      <ul class="navbar-nav align-items-center mb-2 mb-lg-0">
        <li class="nav-item me-3">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= h(build_app_url('cart/cart.php')) ?>">
            <i class="bi bi-cart4"></i>
            Cart
            <span class="badge cart-badge">0</span>
          </a>
        </li>
        <?php if ($user): ?>
          <li class="nav-item dropdown nav-user">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="avatar-circle"><?= $profilePicture ? '<img src="'.h($profilePicture).'" alt="Profile">' : '<i class="bi bi-person-circle"></i>' ?></span>
              <span class="user-name"><?= h($user['name'] ?? $user['email']) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-premium" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="<?= h(build_app_url('customer/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
              <li><a class="dropdown-item" href="<?= h(build_app_url('customer/my-orders.php')) ?>"><i class="bi bi-card-list me-2"></i>My Orders</a></li>
              <li><a class="dropdown-item" href="<?= h(build_app_url('tracking/track-order.php')) ?>"><i class="bi bi-clock-history me-2"></i>Track Order</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form id="logoutForm" action="<?= h(build_app_url('authentication/logout.php')) ?>" method="post" class="m-0">
                  <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
                  <button type="submit" class="dropdown-item text-danger logout-trigger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                </form>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= h(build_app_url('authentication/login.php')) ?>">Login</a></li>
          <li class="nav-item"><a class="nav-link nav-cta" href="<?= h(build_app_url('authentication/register.php')) ?>">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
