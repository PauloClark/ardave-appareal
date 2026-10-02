<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_for_flow(basename(__FILE__));

$account_number = '09179876543';
$account_name = 'ARDAVE APPAREL';
$qr_image = '/assets/images/maya-qr.png';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $order_number = trim($_POST['order_number'] ?? '');
        $amount = trim($_POST['amount'] ?? '');

        if (empty($_FILES['receipt_image']) || $_FILES['receipt_image']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Please upload a receipt image.';
        } else {
            $f = $_FILES['receipt_image'];
            $max = 5 * 1024 * 1024;
            if ($f['size'] > $max) {
                $message = 'File too large (max 5MB).';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $f['tmp_name']);
                finfo_close($finfo);

                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mime, $allowedMimes, true)) {
                    $message = 'Only JPEG, PNG, GIF, and WebP images are allowed.';
                } else {
                    $extMap = [
                        'image/jpeg' => '.jpg',
                        'image/png'  => '.png',
                        'image/gif'  => '.gif',
                        'image/webp' => '.webp',
                    ];
                    $ext = $extMap[$mime] ?? '.png';
                    $dir = __DIR__ . '/../uploads/payments';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $filename = 'uploads/payments/' . uniqid('maya_', true) . $ext;
                    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/../' . $filename)) {
                        $message = 'Failed to save uploaded file.';
                    } else {
                        $orderId = null;
                        if ($order_number !== '') {
                            $s = $pdo->prepare('SELECT id FROM orders WHERE order_number = ? LIMIT 1');
                            $s->execute([$order_number]);
                            $r = $s->fetch();
                            if ($r) {
                                $orderId = (int)$r['id'];
                                // Verify ownership
                                $ownStmt = $pdo->prepare('SELECT o.id FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ? AND c.user_id = ? LIMIT 1');
                                $ownStmt->execute([$orderId, $_SESSION['user_id']]);
                                if (!$ownStmt->fetch()) {
                                    $message = 'You do not have permission to upload payment for this order.';
                                    unlink(__DIR__ . '/../' . $filename);
                                    $orderId = null;
                                }
                            }
                        }

                        if ($message === '') {
                            $stmt = $pdo->prepare('INSERT INTO payments (order_id,amount,method,status,receipt_image,transaction_ref) VALUES (?,?,?,?,?,?)');
                            $ref = strtoupper(bin2hex(random_bytes(8)));
                            $amount = max(0, (float)$amount);
                            $stmt->execute([$orderId, $amount ?: 0.00, 'maya', 'pending', $filename, $ref]);
                            $message = 'Payment uploaded. Reference: ' . h($ref) . '. We will verify shortly.';
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Maya Payment - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <div class="row">
      <div class="col-md-6 mb-4">
        <h3>Pay with Maya</h3>
        <p><strong>Account Name:</strong> <?= h($account_name) ?></p>
        <p><strong>Account Number:</strong> <?= h($account_number) ?></p>
        <div class="mb-3">
          <img src="<?= h($qr_image) ?>" alt="Maya QR" class="img-fluid" style="max-width:260px">
        </div>
        <p class="text-muted small">Scan the QR or send payment to the account above. After payment, upload your receipt below.</p>
      </div>
      <div class="col-md-6">
        <h4>Upload Payment Receipt</h4>
        <?php if ($message): ?><div class="alert alert-info"><?= h($message) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label">Order Number (optional)</label>
            <input name="order_number" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Amount (PHP)</label>
            <input name="amount" type="number" step="0.01" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Receipt Image</label>
            <input name="receipt_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control" required>
          </div>
          <div>
            <button class="btn btn-dark">Upload Payment</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
