<?php
require_once __DIR__ . '/../includes/functions.php';

$google_client_id = get_config('GOOGLE_CLIENT_ID');
$google_client_secret = get_config('GOOGLE_CLIENT_SECRET');
$google_redirect = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . build_app_url('authentication/google-callback.php');

$message = '';

if (!$google_client_id || !$google_client_secret) {
    $message = 'Google authentication is not configured yet. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your server environment.';
} elseif (isset($_GET['action']) && $_GET['action'] === 'connect') {
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_state'] = $state;

    if (!empty($_GET['redirect'])) {
        $_SESSION['oauth_redirect_after_login'] = $_GET['redirect'];
    } elseif (!empty($_SESSION['redirect_after_login'])) {
        $_SESSION['oauth_redirect_after_login'] = $_SESSION['redirect_after_login'];
    }

    $params = [
        'response_type' => 'code',
        'client_id' => $google_client_id,
        'redirect_uri' => $google_redirect,
        'scope' => 'openid email profile',
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Google Login</title></head>
<body>
  <div style="padding:20px;max-width:520px;margin:auto">
    <h3>Google Login</h3>
    <?php if ($message): ?><p><?= h($message) ?></p><?php endif; ?>
    <p><a href="login.php">Back to login</a></p>
  </div>
</body>
</html>