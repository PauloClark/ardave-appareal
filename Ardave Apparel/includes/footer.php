<?php
// Premium footer
?>
<footer class="footer-premium py-5">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-4">
        <a class="footer-brand" href="<?= h(build_app_url('index.php')) ?>">ARDAVE APPAREL</a>
        <p class="footer-copy mt-3">Luxury sportswear for teams, athletes, and events. Crafted for performance, style, and confidence.</p>
      </div>
      <div class="col-lg-2">
        <h6>Company</h6>
        <ul class="footer-links list-unstyled">
          <li><a href="<?= h(build_app_url('about.php')) ?>">About Us</a></li>
          <li><a href="<?= h(build_app_url('products/products.php')) ?>">Shop</a></li>
          <li><a href="<?= h(build_app_url('tracking/track-order.php')) ?>">Track Order</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h6>Services</h6>
        <ul class="footer-links list-unstyled">
          <li><a href="<?= h(build_app_url('products/products.php')) ?>">Custom Jerseys</a></li>
          <li><a href="<?= h(build_app_url('products/products.php')) ?>">Dry Fit Shirts</a></li>
          <li><a href="<?= h(build_app_url('products/products.php')) ?>">Sublimation</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h6>Contact</h6>
        <p class="mb-2">South San Juan Centro, Agdao, Davao City</p>
        <p class="mb-2">0966 461 4504</p>
        <div class="footer-socials d-flex gap-3">
          <a href="https://www.tiktok.com/@ardavechannel?lang=en" target="_blank"><i class="bi bi-tiktok"></i></a>
          <a href="https://www.facebook.com/ardaveappareldavao" target="_blank"><i class="bi bi-facebook"></i></a>
          <a href="https://www.instagram.com/ardaveapparel/?hl=en" target="_blank"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom mt-4 pt-4 border-top">
      <div class="row align-items-center">
        <div class="col-md-6 text-muted">© <?= date('Y') ?> Ardave Apparel. All rights reserved.</div>
        <div class="col-md-6 text-md-end text-muted">Designed for premium performance.</div>
      </div>
    </div>
  </div>
</footer>

<div class="modal fade premium-modal" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <div class="modal-icon mb-3"><i class="bi bi-box-arrow-right"></i></div>
        <h5 class="modal-title fw-bold mb-2" id="logoutModalLabel">Logging Out</h5>
        <p class="text-muted mb-4">Thank you for visiting Ardave Apparel.</p>
        <div class="d-flex justify-content-center gap-3">
          <button type="button" class="btn btn-outline-secondary btn-premium" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-dark btn-premium" id="confirmLogoutBtn">Logout</button>
        </div>
      </div>
    </div>
  </div>
</div>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= h(build_app_url('assets/js/script.js')) ?>"></script>