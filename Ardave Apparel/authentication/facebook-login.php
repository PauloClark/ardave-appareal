<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

$fb_app_id = get_config('FB_APP_ID');
$fb_app_secret = get_config('FB_APP_SECRET');
$fb_redirect = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/facebook-login.php';

$message = '';

if (!$fb_app_id || !$fb_app_secret) {
    $message = 'Facebook authentication is not configured yet. Set FB_APP_ID and FB_APP_SECRET in your server environment.';
} elseif (isset($_GET['action']) && $_GET['action'] === 'connect') {
    $state = bin2hex(random_bytes(8));
    $_SESSION['fb_state'] = $state;
    $scope = 'email';
    $dialog = 'https://www.facebook.com/v15.0/dialog/oauth?client_id=' . $fb_app_id . '&redirect_uri=' . urlencode($fb_redirect) . '&state=' . $state . '&scope=' . urlencode($scope);
    header('Location: ' . $dialog);
    exit;
}

if (isset($_GET['code']) && isset($_GET['state']) && ($_GET['state'] ?? '') === ($_SESSION['fb_state'] ?? '')) {
    $tokenUrl = 'https://graph.facebook.com/v15.0/oauth/access_token?client_id=' . $fb_app_id . '&redirect_uri=' . urlencode($fb_redirect) . '&client_secret=' . $fb_app_secret . '&code=' . urlencode($_GET['code']);
    $resp = @file_get_contents($tokenUrl);
    if ($resp === false) {
        $message = 'Facebook login failed while exchanging the authorization code.';
    } else {
        $data = json_decode($resp, true);
        $access = $data['access_token'] ?? null;
        if ($access) {
            $profile = @file_get_contents('https://graph.facebook.com/me?fields=id,name,email,picture&type=large&access_token=' . urlencode($access));
            $p = json_decode($profile, true);
            $email = $p['email'] ?? null;
            $name = $p['name'] ?? 'Facebook User';
            $picture = $p['picture']['data']['url'] ?? null;
            if ($email) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if (!$user) {
                    $stmt = $pdo->prepare('INSERT INTO users (email, password, name, phone, is_verified, created_at) VALUES (?, ?, ?, NULL, 1, NOW())');
                    $randomPassword = bin2hex(random_bytes(8));
                    $stmt->execute([$email, password_hash($randomPassword, PASSWORD_DEFAULT), $name]);
                    $userId = (int)$pdo->lastInsertId();
                } else {
                    $userId = (int)$user['id'];
                }
                persist_login_metadata($pdo, $userId, $name, $email, $picture, 'facebook');
                set_authenticated_user($pdo, $userId, $name, $email, $picture, 'facebook');
                if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    header('Location: ' . build_app_url('admin/dashboard.php'));
                    exit;
                }
                header('Location: ' . build_app_url('index.php'));
                exit;
            }
        }
        $message = 'Facebook login failed.';
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Facebook Login</title></head>
<body>
  <div style="padding:20px;max-width:520px;margin:auto">
    <h3>Facebook Login</h3>
    <?php if ($message): ?><p><?= h($message) ?></p><?php endif; ?>
    <p><a href="login.php">Back to login</a></p>
  </div>
</body>
</html>

