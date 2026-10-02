<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_for_flow(basename(__FILE__));

function build_url(string $path): string {
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $baseUrl = str_replace($documentRoot, '', $projectRoot);
    $baseUrl = '/' . trim($baseUrl, '/');
    if ($baseUrl === '/') {
        $baseUrl = '';
    }
    $base = trim($baseUrl, '/');
    $path = trim($path, '/');
    $segments = $base !== '' ? array_merge(explode('/', $base), explode('/', $path)) : explode('/', $path);
    return '/' . implode('/', array_map('rawurlencode', $segments));
}

function ensure_payment_columns(PDO $pdo): void {
    try {
        $stmt = $pdo->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME IN ('transaction_ref','receipt_image')"
        );
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('transaction_ref', $existing)) {
            $pdo->exec("ALTER TABLE payments ADD COLUMN transaction_ref VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('receipt_image', $existing)) {
            $pdo->exec("ALTER TABLE payments ADD COLUMN receipt_image VARCHAR(255) DEFAULT NULL");
        }
    } catch (Exception $e) {
        error_log('ensure_payment_columns: ' . $e->getMessage());
    }
}

function save_tracking(PDO $pdo, int $orderId, string $status, ?string $note = null): void {
    $stmt = $pdo->prepare('INSERT INTO tracking (order_id,status,note) VALUES (?,?,?)');
    $stmt->execute([$orderId, $status, $note]);
}

function validate_order_ownership(PDO $pdo, int $orderId, int $userId): bool {
    $stmt = $pdo->prepare(
        'SELECT o.id FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ? AND c.user_id = ? LIMIT 1'
    );
    $stmt->execute([$orderId, $userId]);
    return (bool)$stmt->fetch();
}

$account_number = '09920336613';
$account_name = 'Eduardo Jimenez';
$qr_image = build_url('assets/images/gcash-qr.png');

$message = '';
$order_number = trim($_GET['order_number'] ?? '');
$selectedMethod = 'gcash';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $order_number = trim($_POST['order_number'] ?? '');
        $amount = trim($_POST['amount'] ?? '');
        $payment_method = trim($_POST['payment_method'] ?? 'gcash');
        $transaction_number = trim($_POST['transaction_number'] ?? '');
        $selectedMethod = $payment_method;

        if (!in_array($payment_method, ['gcash', 'maya'], true)) {
            $message = 'Invalid payment method.';
        } elseif ($order_number === '') {
            $message = 'Please provide your order number.';
        } elseif (!preg_match('/^ORD\d{10,}$/', $order_number)) {
            $message = 'Invalid order number format.';
        } elseif (empty($_FILES['receipt_image']) || $_FILES['receipt_image']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Please upload a receipt image.';
        } else {
            $file = $_FILES['receipt_image'];
            $max = 5 * 1024 * 1024;
            if ($file['size'] > $max) {
                $message = 'Uploaded file is too large (max 5MB).';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
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
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $filename = 'uploads/payments/' . uniqid('pay_', true) . $ext;

                    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $filename)) {
                        $message = 'Failed to save uploaded file.';
                    } else {
                        $message = '';
                    }
                }
            }
        }

        if ($message === '') {
            $orderId = null;
            $oStmt = $pdo->prepare('SELECT id,total_amount FROM orders WHERE order_number = ? LIMIT 1');
            $oStmt->execute([$order_number]);
            $orderRow = $oStmt->fetch();

            if ($orderRow) {
                $orderId = (int)$orderRow['id'];

                // Authorization: verify this order belongs to the logged-in user
                if (!validate_order_ownership($pdo, $orderId, (int)$_SESSION['user_id'])) {
                    $message = 'You do not have permission to upload payment for this order.';
                    // Remove the uploaded file since we're rejecting
                    if (isset($filename) && file_exists(__DIR__ . '/../' . $filename)) {
                        unlink(__DIR__ . '/../' . $filename);
                    }
                } else {
                    if ($amount === '') {
                        $amount = (string)$orderRow['total_amount'];
                    }
                    // Validate amount is a positive number
                    $amount = max(0, (float)$amount);

                    ensure_payment_columns($pdo);
                    $stmt = $pdo->prepare('INSERT INTO payments (order_id,amount,method,status,transaction_ref,receipt_image) VALUES (?,?,?,?,?,?)');
                    $ref = $transaction_number !== '' ? $transaction_number : strtoupper(bin2hex(random_bytes(4)));
                    $stmt->execute([$orderId, $amount, strtolower($payment_method), 'pending', $ref, $filename ?? null]);

                    save_tracking($pdo, $orderId, 'Waiting Payment', 'Payment proof submitted and awaiting verification.');
                    $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute(['Waiting For Payment Verification', $orderId]);

                    $message = 'Payment submitted successfully. Your order is now waiting for payment verification.';
                }
            } else {
                $message = 'Order not found. Please check your order number.';
                // Remove uploaded file if order doesn't exist
                if (isset($filename) && file_exists(__DIR__ . '/../' . $filename)) {
                    unlink(__DIR__ . '/../' . $filename);
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
  <title>Payment Upload - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= h(build_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <div class="row">
      <div class="col-md-6 mb-4">
        <h3>Pay with <?= ucfirst($selectedMethod === 'maya' ? 'Maya' : 'Gcash') ?></h3>
        <p><strong>Account Name:</strong> <?= h($account_name) ?></p>
        <p><strong>Account Number:</strong> <?= h($account_number) ?></p>
        <div class="mb-3">
          <img src="<?= h($qr_image) ?>" alt="QR Code" class="img-fluid" style="max-width:260px">
        </div>
        <p class="text-muted small">Scan the QR or send payment to the account above. After payment, upload your proof below.</p>
      </div>
      <div class="col-md-6">
        <h4>Submit Payment Proof</h4>
        <?php if ($message): ?><div class="alert alert-info"><?= h($message) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label">Order Number</label>
            <input name="order_number" class="form-control" value="<?= h($order_number) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
              <option value="gcash" <?= $selectedMethod === 'gcash' ? 'selected' : '' ?>>GCASH</option>
              <option value="maya" <?= $selectedMethod === 'maya' ? 'selected' : '' ?>>MAYA</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Amount (PHP)</label>
            <input name="amount" type="number" step="0.01" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Transaction Reference Number</label>
            <input name="transaction_number" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Receipt Image</label>
            <input name="receipt_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control" required>
          </div>
          <div>
            <button class="btn btn-dark">Submit Payment</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
