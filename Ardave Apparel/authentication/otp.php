<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$message = '';
$email = $_GET['email'] ?? $_POST['email'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$code = trim($_POST['otp'] ?? '');
	if (!$email) {
		$message = 'Missing email.';
	} else {
		if (verify_otp($email, $code, $pdo)) {
			// Mark user as verified
			$stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE email = ?');
			$stmt->execute([$email]);
			// Log the user in
			$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
			$stmt->execute([$email]);
			$user = $stmt->fetch();
			if ($user) {
				session_regenerate_id(true);
				$_SESSION['user_id'] = (int)$user['id'];
				$_SESSION['logged_in'] = true;
				header('Location: ../index.php');
				exit;
			}
		} else {
			$message = 'Invalid or expired code.';
		}
	}
}

// Resend
if (isset($_GET['resend']) && $email) {
	$otp = create_otp($email, null, $pdo);
	send_mail($email, 'Your Ardave OTP', "Your verification code: $otp");
	$message = 'A new code was sent to your email.';
}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Verify OTP - Ardave Apparel</title>
	<link href="/assets/css/login.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
	<?php include __DIR__ . '/../includes/navbar.php'; ?>
	<div class="container" style="max-width:520px">
		<h2 class="mb-3">Enter verification code</h2>
		<?php if ($message): ?>
			<div class="alert alert-info"><?= h($message) ?></div>
		<?php endif; ?>
		<form method="post">
			<div class="mb-2">
				<label class="form-label">OTP Code</label>
				<input name="otp" class="form-control" required>
			</div>
			<div class="d-flex justify-content-between align-items-center">
				<a href="register.php">Back</a>
				<button class="btn btn-primary">Verify</button>
			</div>
		</form>
		<p class="mt-3"><a href="?resend=1">Resend code</a></p>
	</div>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

