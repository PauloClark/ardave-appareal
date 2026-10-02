<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// fetch user and customer
$uStmt = $pdo->prepare('SELECT id,email,name FROM users WHERE id = ? LIMIT 1');
$uStmt->execute([$user_id]);
$user = $uStmt->fetch();

$cStmt = $pdo->prepare('SELECT id,name,email,phone,address FROM customers WHERE user_id = ? LIMIT 1');
$cStmt->execute([$user_id]);
$customer = $cStmt->fetch();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($email === '' || $name === '') {
            $error = 'Name and email are required.';
        } else {
            // update users table
            $u = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $u->execute([$name, $email, $user_id]);
            // update or insert customer row
            if ($customer) {
                $c = $pdo->prepare('UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
                $c->execute([$name, $email, $phone, $address, $customer['id']]);
            } else {
                $c = $pdo->prepare('INSERT INTO customers (user_id,name,email,phone,address) VALUES (?,?,?,?,?)');
                $c->execute([$user_id, $name, $email, $phone, $address]);
            }
            $message = 'Profile updated.';
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new === '' || $confirm === '') {
            $error = 'Please provide a new password and confirmation.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            // verify current
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$user_id]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($current, $row['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $u2 = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $u2->execute([$hash, $user_id]);
                $message = 'Password changed successfully.';
            }
        }
    }

    // refresh user/customer data
    $uStmt->execute([$user_id]); $user = $uStmt->fetch();
    $cStmt->execute([$user_id]); $customer = $cStmt->fetch();
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <h1 class="h3 mb-4">My Profile</h1>
    <p><a class="btn btn-outline-dark" href="<?= h(build_app_url('customer/phone.php')) ?>">Set up / change verified phone login</a></p>
    <p class="text-muted">The contact phone below is for orders. SMS login requires separate phone verification.</p>
    <?php if ($message): ?>
      <div class="alert alert-success"><?= h($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-6">
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" required value="<?= h($customer['name'] ?? $user['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required value="<?= h($customer['email'] ?? $user['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Mobile / Phone</label>
            <input name="phone" class="form-control" value="<?= h($customer['phone'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3"><?= h($customer['address'] ?? '') ?></textarea>
          </div>
          <div>
            <button name="update_profile" class="btn btn-dark">Update Profile</button>
          </div>
        </form>
      </div>

      <div class="col-md-6">
        <h5>Change Password</h5>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Current password</label>
            <input name="current_password" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New password</label>
            <input name="new_password" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input name="confirm_password" type="password" class="form-control" required>
          </div>
          <div>
            <button name="change_password" class="btn btn-outline-dark">Change Password</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

