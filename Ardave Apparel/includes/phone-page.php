<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/phone-auth.php';
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
$purpose = ($phonePurpose ?? '') === 'link' ? 'link' : 'login';
if ($purpose === 'link') require_login();
$authenticatedId = is_logged_in() ? (int)$_SESSION['user_id'] : null;
$endpoint = build_app_url($purpose === 'link' ? 'customer/phone.php' : 'authentication/phone-login.php');
$ready = phone_ready($pdo);
$error = '';
$notice = $_SESSION['phone_notice'] ?? '';
unset($_SESSION['phone_notice']);
$generic = 'If this number is eligible, a code has been requested. Delivery may take a moment. Otherwise, use email or Google login and verify a phone in your profile.';
$step = ($_GET['step'] ?? '') === 'code' ? 'code' : 'phone';
if ($purpose === 'login' && isset($_GET['redirect']) && is_string($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = phone_redirect($_GET['redirect']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $started = microtime(true);
    try {
        if (!verify_csrf_token(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
            http_response_code(400); $error = 'Your session could not be confirmed. Reload the page and try again.';
        } elseif (!$ready) {
            $error = 'SMS verification is currently unavailable. Please use email or Google login.';
        } else {
            $action = $_POST['action'] ?? '';
            if ($action === 'send') {
                $phone = phone_normalize(is_string($_POST['phone'] ?? null) ? $_POST['phone'] : '');
                if (!$phone) $error = 'Enter a Philippine mobile number, such as 0917 123 4567.';
                else {
                    phone_start($pdo, $phone, $purpose, $authenticatedId);
                    $_SESSION['phone_notice'] = $generic;
                    $step = 'code';
                }
            } elseif (in_array($action, ['resend', 'check'], true)) {
                $step = 'code';
                $challenge = phone_challenge($pdo);
                if (!$challenge || $challenge['purpose'] !== $purpose || ($purpose === 'link' && (int)$challenge['user_id'] !== $authenticatedId)) {
                    $error = 'This request is no longer valid. Start again.';
                } elseif ($action === 'resend') {
                    phone_resend($pdo, $challenge);
                    $_SESSION['phone_notice'] = $generic;
                } else {
                    $code = is_string($_POST['code'] ?? null) ? trim($_POST['code']) : '';
                    $user = phone_check($pdo, $code, $authenticatedId);
                    if ($user) {
                        if ($purpose === 'login') {
                            $target = phone_redirect((string)($_SESSION['redirect_after_login'] ?? ''));
                            set_authenticated_user($pdo, (int)$user['id'], $user['name'] ?: $user['email'], $user['email'], null, 'sms');
                            unset($_SESSION['redirect_after_login']);
                            $successRedirect = is_admin() ? build_app_url('admin/dashboard.php') : $target;
                        } else {
                            $_SESSION['phone_notice'] = 'Your phone number is verified and ready for SMS login.';
                            $successRedirect = $endpoint;
                        }
                    } else $error = 'The code could not be verified. It may be incorrect, expired, or unavailable. Retry or start again later.';
                }
            } else $error = 'Invalid request.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'SMS verification is currently unavailable. Please use email or Google login.';
        error_log('Phone authentication operation failed.');
    } finally {
        // Bound provider timeouts and pad responses to reduce account timing disclosure.
        $remaining = 3.3 - (microtime(true) - $started);
        if ($remaining > 0) usleep((int)($remaining * 1000000));
    }
    if (isset($successRedirect)) { header('Location: ' . $successRedirect, true, 303); exit; }
    if (!$error) { header('Location: ' . $endpoint . '?step=' . $step, true, 303); exit; }
}
$challenge = $ready ? phone_challenge($pdo) : null;
if ($challenge && ($challenge['purpose'] !== $purpose || ($purpose === 'link' && (int)$challenge['user_id'] !== $authenticatedId))) $challenge = null;
$expired = !$challenge || (int)$challenge['expires_at'] <= time() || (int)$challenge['attempts'] >= 5;
$cooldown = $challenge ? max(0, (int)$challenge['next_send_at'] - time()) : 0;
$linked = null;
if ($purpose === 'link') {
    try {
        $stmt = $pdo->prepare('SELECT phone FROM auth_phone_identities WHERE user_id=?');
        $stmt->execute([$authenticatedId]); $linked = $stmt->fetchColumn();
    } catch (PDOException $e) { /* Migration may not have been installed yet. */ }
}
$title = $step === 'code' ? 'Enter your SMS code' : ($purpose === 'link' ? 'Set up phone login' : 'Sign in with your phone');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> - Ardave Apparel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= h(build_app_url('assets/css/login.css')) ?>?v=20260924" rel="stylesheet">
</head>
<body class="auth-page">
<?php include __DIR__ . '/navbar.php'; ?>
<main class="auth-shell"><section class="form-panel"><div class="form-card">
<div class="auth-heading"><span class="eyebrow">Ardave / SMS verification</span><h1><?= h($title) ?></h1>
<p><?= $purpose === 'link' ? 'Verify a mobile number you own to enable SMS login. Your email and Google sign-in will remain available.' : 'Use a mobile number you have already verified in your Ardave profile.' ?></p></div>
<?php if (!$ready): ?><div class="alert auth-alert warning" role="status">SMS verification is currently unavailable. Please use email or Google login.</div><?php endif; ?>
<?php if ($notice): ?><div class="alert auth-alert warning" role="status"><?= h($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert auth-alert error" role="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($linked): ?><p>Verified login number: <?= h(substr($linked, 0, 3) . ' ••• ••• ' . substr($linked, -4)) ?></p><?php endif; ?>
<?php if ($step === 'phone'): ?>
<form method="post" action="<?= h($endpoint) ?>">
<input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>"><input type="hidden" name="action" value="send">
<div class="auth-field"><label for="phone">Philippine mobile number</label><input class="form-control" id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0917 123 4567" maxlength="40" aria-describedby="phoneHelp" required></div>
<p id="phoneHelp" class="password-strength">Accepts 09…, 9…, 639…, or +639…. By continuing, you request an SMS verification code.</p>
<button class="button-primary" type="submit" <?= !$ready ? 'disabled' : '' ?>>Request SMS code</button>
</form>
<?php elseif (!$expired && $ready): ?>
<p class="password-strength">Request for <?= h(substr($challenge['phone'], 0, 3) . ' ••• ••• ' . substr($challenge['phone'], -4)) ?>. This request expires within 10 minutes; resending does not extend it.</p>
<form method="post" action="<?= h($endpoint) ?>?step=code">
<input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>"><input type="hidden" name="action" value="check">
<div class="auth-field"><label for="code">Verification code</label><input class="form-control" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{4,10}" minlength="4" maxlength="10" required></div>
<button class="button-primary" type="submit"><?= $purpose === 'link' ? 'Verify phone number' : 'Verify and log in' ?></button>
</form>
<form method="post" action="<?= h($endpoint) ?>?step=code" class="mt-3">
<input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>"><input type="hidden" name="action" value="resend">
<button class="social-btn w-100" type="submit" id="resend" data-cooldown="<?= $cooldown ?>" <?= $cooldown ? 'disabled' : '' ?>><?= $cooldown ? 'Resend in ' . $cooldown . 's' : 'Request another code' ?></button>
<noscript><p>Wait 60 seconds, then reload to request another code.</p></noscript>
</form>
<?php else: ?><p role="status">This request has expired, reached its attempt limit, or is unavailable. Start again to request a code.</p><?php endif; ?>
<div class="auth-footer-card">
<?php if ($step === 'code'): ?><a href="<?= h($endpoint) ?>">Start again</a><?php endif; ?>
<a href="<?= h(build_app_url($purpose === 'link' ? 'customer/profile.php' : 'authentication/login.php')) ?>"><?= $purpose === 'link' ? 'Back to profile' : 'Use email or Google' ?></a>
</div></div></section></main>
<?php include __DIR__ . '/footer.php'; ?>
<script>
const resend = document.getElementById('resend');
if (resend) {
    const until = Date.now() + Number(resend.dataset.cooldown) * 1000;
    function updateCooldown() {
        const remaining = Math.max(0, Math.ceil((until - Date.now()) / 1000));
        resend.disabled = remaining > 0;
        resend.textContent = remaining ? `Resend in ${remaining}s` : 'Request another code';
        if (!remaining) clearInterval(timer);
    }
    const timer = setInterval(updateCooldown, 1000);
    updateCooldown();
}
</script>
</body></html>
