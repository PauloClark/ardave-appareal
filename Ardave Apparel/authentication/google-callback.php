<?php
require_once __DIR__ . '/../includes/functions.php';

$google_client_id = get_config('GOOGLE_CLIENT_ID');
$google_client_secret = get_config('GOOGLE_CLIENT_SECRET');
$google_redirect = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . build_app_url('authentication/google-callback.php');

$message = '';

if (!$google_client_id || !$google_client_secret) {
    $message = 'Google authentication is not configured yet. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your server environment.';
} elseif (!isset($_GET['state'], $_GET['code']) || ($_GET['state'] ?? '') !== ($_SESSION['google_state'] ?? '')) {
    $message = 'Invalid Google login state. Please try again.';
} else {
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $post = http_build_query([
        'code' => $_GET['code'],
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect,
        'grant_type' => 'authorization_code'
    ]);
    $opts = ['http' => [
        'method' => 'POST',
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => $post,
        'timeout' => 15,
    ]];
    $resp = @file_get_contents($tokenUrl, false, stream_context_create($opts));

    if ($resp === false) {
        error_log('Google OAuth: token exchange failed; check server HTTPS access and matching OAuth client settings.');
        $message = 'Google login failed while exchanging the authorization code.';
    } else {
        $data = json_decode($resp, true);
        $access = $data['access_token'] ?? null;
        if (is_string($access) && $access !== '') {
            // Google OpenID Connect UserInfo requires the token in an Authorization header.
            // Never put an access token in a URL: URLs are commonly recorded in logs.
            $profileContext = stream_context_create(['http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$access}\r\nAccept: application/json\r\n",
                'timeout' => 15,
            ]]);
            $profile = @file_get_contents('https://openidconnect.googleapis.com/v1/userinfo', false, $profileContext);
            $profileStatus = isset($http_response_header[0]) && preg_match('/^HTTP\/\S+\s+(\d{3})/', $http_response_header[0], $matches)
                ? $matches[1]
                : 'unavailable';
            $p = $profile !== false ? json_decode($profile, true) : null;
            $email = is_array($p) ? ($p['email'] ?? null) : null;
            $name = $p['name'] ?? 'Google User';
            $picture = $p['picture'] ?? null;
            if (is_string($email) && $email !== '' && ($p['email_verified'] ?? false) === true) {
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

                persist_login_metadata($pdo, $userId, $name, $email, $picture, 'google');
                set_authenticated_user($pdo, $userId, $name, $email, $picture, 'google');

                $redirectTarget = $_SESSION['oauth_redirect_after_login'] ?? '';
                unset($_SESSION['oauth_redirect_after_login'], $_SESSION['google_state']);

                if ($redirectTarget !== '') {
                    header('Location: ' . $redirectTarget);
                    exit;
                }

                if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    header('Location: ' . build_app_url('admin/dashboard.php'));
                    exit;
                }

                header('Location: ' . build_app_url('index.php'));
                exit;
            }
            // Log the failure stage, never the provider response or personal data.
            error_log('Google OAuth: UserInfo request failed or returned no verified email; HTTP status ' . $profileStatus . '.');
        } else {
            error_log('Google OAuth: token exchange returned no access token.');
        }

        $message = 'Google login could not be completed. Please try again.';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Google Login | Ardave Apparel</title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #101015; color: #fafafa; font: 16px/1.5 system-ui, sans-serif; }
    main { width: min(100%, 480px); padding: 36px; border: 1px solid #303038; border-radius: 24px; background: #17171e; }
    h1 { margin: 0 0 12px; font-size: 27px; }
    p { margin: 0 0 24px; color: #babac1; }
    a { display: inline-block; padding: 12px 18px; border-radius: 10px; background: #ff741a; color: #17171e; font-weight: 700; text-decoration: none; }
    a:focus-visible { outline: 3px solid white; outline-offset: 3px; }
  </style>
</head>
<body>
  <main>
    <h1>Google Login</h1>
    <?php if ($message): ?><p><?= h($message) ?></p><?php endif; ?>
    <a href="<?= h(build_app_url('authentication/login.php')) ?>">Back to login</a>
  </main>
</body>
</html>
