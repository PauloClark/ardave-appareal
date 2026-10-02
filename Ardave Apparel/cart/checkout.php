<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_for_flow(basename(__FILE__));

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

function save_tracking(PDO $pdo, int $orderId, string $status, ?string $note = null): void {
    $stmt = $pdo->prepare('INSERT INTO tracking (order_id,status,note) VALUES (?,?,?)');
    $stmt->execute([$orderId, $status, $note]);
}

$cartJson = $_POST['cart'] ?? '[]';
$cart = json_decode($cartJson, true);
if (!is_array($cart)) {
    $cart = [];
}
$hasCheckoutFields = isset($_POST['name']) || isset($_POST['email']) || isset($_POST['phone']) || isset($_POST['address']) || isset($_POST['payment_method']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasCheckoutFields) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    $allowedPayments = ['gcash', 'maya', 'cod'];
    if (!in_array($payment_method, $allowedPayments, true)) {
        $error = 'Invalid payment method.';
    } elseif (!is_array($cart) || empty($cart)) {
        $error = 'Your cart is empty.';
    } elseif ($name === '' || $email === '' || $address === '') {
        $error = 'Please fill in required customer information.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        try {
            $pdo->beginTransaction();

            $customerId = null;
            if (!empty($_SESSION['user_id'])) {
                $stmt = $pdo->prepare('SELECT id FROM customers WHERE user_id = ? LIMIT 1');
                $stmt->execute([$_SESSION['user_id']]);
                $row = $stmt->fetch();
                if ($row) {
                    $customerId = (int)$row['id'];
                }
            }
            if (!$customerId) {
                $stmt = $pdo->prepare('INSERT INTO customers (user_id,name,email,phone,address) VALUES (?,?,?,?,?)');
                $uid = $_SESSION['user_id'] ?? null;
                $stmt->execute([$uid, $name, $email, $phone, $address]);
                $customerId = (int)$pdo->lastInsertId();
            }

            $total = 0.0;
            foreach ($cart as $item) {
                $pid = (int)($item['product_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $pstmt = $pdo->prepare('SELECT price,name FROM products WHERE id = ? LIMIT 1');
                $pstmt->execute([$pid]);
                $prod = $pstmt->fetch();
                $unit = $prod ? (float)$prod['price'] : (float)($item['price'] ?? 0);
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $total += $unit * $qty;
            }

            $orderNumber = 'ORD' . time() . mt_rand(1000,9999);
            $oStmt = $pdo->prepare('INSERT INTO orders (order_number,customer_id,status,total_amount,shipping_address,phone) VALUES (?,?,?,?,?,?)');
            $oStmt->execute([$orderNumber, $customerId, 'Waiting For Payment Verification', $total, $address, $phone]);
            $orderId = (int)$pdo->lastInsertId();

            $oiStmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,size,quantity,unit_price) VALUES (?,?,?,?,?,?)');
            $udStmt = $pdo->prepare('INSERT INTO uploaded_designs (order_item_id,file_path,type) VALUES (?,?,?)');

            foreach ($cart as $item) {
                $pid = (int)($item['product_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $pstmt = $pdo->prepare('SELECT price,name FROM products WHERE id = ? LIMIT 1');
                $pstmt->execute([$pid]);
                $prod = $pstmt->fetch();
                $unit = $prod ? (float)$prod['price'] : (float)($item['price'] ?? 0);
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $pname = $prod ? $prod['name'] : ($item['name'] ?? 'Product');

                $oiStmt->execute([$orderId, $pid, $pname, $item['size'] ?? null, $qty, $unit]);
                $orderItemId = (int)$pdo->lastInsertId();

                foreach (['design','logo'] as $type) {
                    if (!empty($item[$type]) && is_string($item[$type]) && strpos($item[$type], 'base64') !== false) {
                        if (preg_match('#^data:(image/[^;]+);base64,(.+)$#', $item[$type], $m)) {
                            $allowedUploadMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            if (!in_array($m[1], $allowedUploadMimes, true)) {
                                continue;
                            }
                            $data = base64_decode($m[2]);
                            if ($data === false || strlen($data) > 5 * 1024 * 1024) {
                                continue;
                            }
                            $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                            $ext = $extMap[$m[1]] ?? 'png';
                            $fname = 'uploads/' . uniqid('design_', true) . '.' . $ext;
                            file_put_contents(__DIR__ . '/../' . $fname, $data);
                            $udStmt->execute([$orderItemId, $fname, $type]);
                        }
                    }
                }
            }

            $pstmt2 = $pdo->prepare('INSERT INTO payments (order_id,amount,method,status,transaction_ref,receipt_image) VALUES (?,?,?,?,?,?)');
            $pstmt2->execute([$orderId, $total, $payment_method, 'pending', null, null]);

            save_tracking($pdo, $orderId, 'Pending', 'Order placed successfully.');

            // Deduct stock for ordered items
            foreach ($cart as $item) {
                $pid = (int)($item['product_id'] ?? 0);
                if ($pid <= 0) continue;
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $pdo->prepare('UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?')->execute([$qty, $pid]);
            }

            $pdo->commit();

            $_SESSION['checkout_success'] = [
                'order_number' => $orderNumber,
                'total' => $total,
            ];
            header('Location: ' . build_url('cart/checkout.php'));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Checkout error for user ' . ($_SESSION['user_id'] ?? 'guest') . ': ' . $e->getMessage());
            $error = 'Failed to place order. Please try again or contact support.';
        }
    }
    }
}

$placedOrder = $_SESSION['checkout_success'] ?? null;
unset($_SESSION['checkout_success']);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Checkout - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <?php if ($placedOrder): ?>
      <script>localStorage.removeItem('ardaveCart');</script>
      <div class="row justify-content-center">
        <div class="col-md-7">
          <div class="card card-elevated p-5 text-center">
            <div class="mb-3" style="font-size:3rem;color:#198754">&#10003;</div>
            <h3 class="mb-2">Order Placed Successfully!</h3>
            <p class="text-muted mb-3">Thank you for your order. Your order number is:</p>
            <div class="fs-4 fw-bold mb-4"><?= h($placedOrder['order_number']) ?></div>
            <p class="text-muted mb-2">Total: &#8369;<?= number_format($placedOrder['total'], 2) ?></p>
            <p class="text-muted mb-4">Your order has been placed successfully.</p>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
              <a href="<?= h(build_url('tracking/track-order.php?order_number=' . urlencode($placedOrder['order_number']))) ?>" class="btn btn-outline-primary">Track Order</a>
              <a href="<?= h(build_app_url('customer/my-orders.php')) ?>" class="btn btn-outline-dark">My Orders</a>
            </div>
          </div>
        </div>
      </div>
    <?php else: ?>
    <div class="row">
      <div class="col-md-7">
        <h3>Checkout</h3>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?= h($success) ?></div>
          <script>localStorage.removeItem('ardaveCart');</script>
        <?php endif; ?>

        <form id="checkoutForm" method="post">
          <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
          <input type="hidden" name="cart" id="cartInput" value="<?= h($cartJson) ?>">
          <div class="mb-3">
            <label class="form-label">Full name</label>
            <input name="name" class="form-control" required value="<?= h($_SESSION['user_name'] ?? $_SESSION['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required value="<?= h($_SESSION['user_email'] ?? $_SESSION['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Mobile / Phone</label>
            <input name="phone" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Shipping address</label>
            <textarea name="address" class="form-control" rows="3" required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Payment method</label>
            <select name="payment_method" class="form-select" required>
              <option value="gcash">Gcash</option>
              <option value="maya">Maya</option>
              <option value="cod">Cash on Delivery</option>
            </select>
          </div>

          <div class="mb-3">
            <button id="placeOrderBtn" type="submit" class="btn btn-dark">Place Order</button>
          </div>
        </form>
      </div>

      <div class="col-md-5">
        <h4>Order Summary</h4>
        <div id="orderSummary" class="mb-3"></div>
        <div class="d-flex justify-content-between fw-bold">
          <div>Total</div>
          <div id="orderTotal">₱0.00</div>
        </div>
          <div class="card p-3 mt-3">
            <h5 class="mb-3">Payment Options</h5>
            <div class="mb-2"><strong>GCASH</strong><br>Account: Eduardo Jimenez<br>Number: 09920336613</div>
            <div class="mb-2"><strong>MAYA</strong><br>Account: Eduardo Jimenez<br>Number: 09920336613</div>
            <p class="small text-muted mb-0">Select your preferred payment method when checking out.</p>
          </div>
      </div>
    </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
    const cartKey = 'ardaveCart';
    const cartInput = document.getElementById('cartInput');

    function getCart(){
      const raw = cartInput ? cartInput.value : '';
      if (raw) {
        try {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed) && parsed.length) return parsed;
        } catch (e) {}
      }
      try { return JSON.parse(localStorage.getItem(cartKey) || '[]'); } catch(e){ return []; }
    }

    function setCart(cart){
      if (cartInput) cartInput.value = JSON.stringify(cart);
      try { localStorage.setItem(cartKey, JSON.stringify(cart)); } catch (e) {}
    }

    function renderSummary(){
      const cart = getCart();
      const container = document.getElementById('orderSummary');
      if (!cart.length){ container.innerHTML = '<p class="text-muted">Cart is empty.</p>'; document.getElementById('orderTotal').textContent = '₱0.00'; return; }
      container.innerHTML = '';
      let total = 0;
      cart.forEach(it => {
        const price = Number(it.price) || 0;
        const qty = Number(it.quantity) || 1;
        total += price * qty;
        const div = document.createElement('div');
        div.className = 'mb-2';
        div.innerHTML = `<div><strong>${escapeHtml(it.name)}</strong> <small class="text-muted">${escapeHtml(it.size||'')}</small></div><div>₱${price.toFixed(2)} × ${qty}</div>`;
        container.appendChild(div);
      });
        // Calculate delivery fee (business rule). Default 0 for now.
        const deliveryFee = 0;
        const totalWithDelivery = total + deliveryFee;
        // Show delivery row
        const deliveryRow = document.createElement('div');
        deliveryRow.className = 'mb-2';
        deliveryRow.innerHTML = `<div><strong>Delivery</strong></div><div>₱${deliveryFee.toFixed(2)}</div>`;
        container.appendChild(deliveryRow);
        document.getElementById('orderTotal').textContent = '₱' + totalWithDelivery.toFixed(2);
    }
    function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); }

    document.getElementById('checkoutForm').addEventListener('submit', function(e){
      const cart = getCart();
      if (!cart.length){ alert('Your cart is empty'); e.preventDefault(); return; }
      setCart(cart);
    });

    renderSummary();
  </script>
</body>
</html>

