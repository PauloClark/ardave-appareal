<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$message = '';
// Request reset or perform reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!empty($_POST['email'])) {
		$email = trim($_POST['email']);
		// create password reset token and send mail
		$token = create_password_reset($email, $pdo);
		$link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/forgot-password.php?token=' . $token;
		send_mail($email, 'Password reset - Ardave Apparel', "Click here to reset your password: $link");
		$message = 'If this email exists we sent a reset link. (Check server mail log when using XAMPP)';
	} elseif (!empty($_POST['token']) && !empty($_POST['password'])) {
		$token = $_POST['token'];
		$reset = verify_password_reset($token, $pdo);
		if ($reset) {
			$password = $_POST['password'];
			if (strlen($password) < 6) {
				$message = 'Password must be at least 6 characters.';
			} else {
				$hash = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
				$stmt->execute([$hash, $reset['email']]);
				mark_password_reset_used($reset['id'], $pdo);
				$message = 'Password updated. You may now login.';
			}
		} else {
			$message = 'Invalid or expired token.';
		}
	}
}

$tokenParam = $_GET['token'] ?? null;
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Forgot Password - Ardave Apparel</title>
	<link href="/assets/css/login.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
	<?php include __DIR__ . '/../includes/navbar.php'; ?>
	<div class="container" style="max-width:520px">
		<h2 class="mb-3">Forgot Password</h2>
		<?php if ($message): ?><div class="alert alert-info"><?= h($message) ?></div><?php endif; ?>

		<?php if ($tokenParam): ?>
			<form method="post">
				<input type="hidden" name="token" value="<?= h($tokenParam) ?>">
				<div class="mb-2">
					<label class="form-label">New password</label>
					<input name="password" type="password" class="form-control" required>
				</div>
				<div class="d-flex justify-content-end"><button class="btn btn-primary">Reset password</button></div>
			</form>
		<?php else: ?>
			<form method="post">
				<div class="mb-2">
					<label class="form-label">Your email</label>
					<input name="email" type="email" class="form-control" required>
				</div>
				<div class="d-flex justify-content-end"><button class="btn btn-primary">Send reset link</button></div>
			</form>
		<?php endif; ?>
	</div>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

