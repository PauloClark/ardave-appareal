<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$error = '';
$authMessage = '';
if (!empty($_SESSION['auth_message'])) {
    $authMessage = $_SESSION['auth_message'];
    unset($_SESSION['auth_message']);
}
if (isset($_GET['message']) && $_GET['message'] === 'login_required') {
    $authMessage = 'Please Login First';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'Provide valid email and password.';
        } else {
            $stmt = $pdo->prepare('SELECT id, password, name, email FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                set_authenticated_user($pdo, (int)$user['id'], $user['name'] ?: $email, $user['email'] ?: $email, null, 'email');
                if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    header('Location: ' . build_app_url('admin/dashboard.php'));
                    exit;
                }
                $redirectTo = $_POST['redirect'] ?? ($_SESSION['redirect_after_login'] ?? '');
                if ($redirectTo !== '') {
                    $redirectTarget = urldecode($redirectTo);
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirectTarget);
                    exit;
                }
                header('Location: ' . build_app_url('index.php'));
                exit;
            } else {
                $error = 'Invalid email or password.';
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
    <title>Login - Ardave Apparel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(build_app_url('assets/css/login.css')) ?>?v=20260924" rel="stylesheet">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <main class="auth-shell">
        <section class="form-panel">
            <div class="form-card">
                <div class="auth-heading">
                    <span class="eyebrow">Welcome Back</span>
                    <h1>Sign in to your account</h1>
                    <p>Login to view your orders and manage custom apparel requests.</p>
                </div>

                <?php if ($authMessage): ?>
                    <div class="alert auth-alert warning"><span>⚠️</span><?= h($authMessage) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert auth-alert error"><span>❌</span><?= h($error) ?></div>
                <?php endif; ?>

                <form id="loginForm" method="post" action="" novalidate>
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

                    <div class="auth-field">
                        <label for="loginEmail">Email Address</label>
                        <input id="loginEmail" name="email" type="email" autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false" class="form-control" placeholder="name@example.com" required>
                    </div>

                    <div class="auth-field">
                        <label for="loginPassword">Password</label>
                        <div class="input-password-field">
                            <input id="loginPassword" name="password" type="password" autocomplete="current-password" class="form-control" placeholder="Enter your password" required>
                            <button type="button" class="show-password" aria-pressed="false" data-target="loginPassword">Show</button>
                        </div>
                    </div>

                    <div class="form-meta">
                        <label class="checkbox-wrap"><input name="remember" type="checkbox"> Remember me</label>
                        <a class="text-link" href="forgot-password.php">Forgot Password?</a>
                    </div>

                    <button id="loginSubmit" class="button-primary" type="submit">Login</button>
                </form>

                <div class="divider"><span>Or continue with</span></div>
                <div class="social-grid">
                    <a class="social-btn" href="<?= h(build_app_url('authentication/phone-login.php')) ?>">Continue with SMS code</a>
                    <a class="social-btn google" href="<?= h(build_app_url('authentication/google-login.php')) ?>?action=connect"><svg class="google-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                        <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.02v2.51h3.24c1.89-1.74 2.98-4.31 2.98-7.36Z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.62-2.41l-3.24-2.51c-.9.6-2.05.96-3.38.96-2.6 0-4.81-1.76-5.6-4.12H3.06v2.59A10 10 0 0 0 12 22Z"/>
                        <path fill="#FBBC05" d="M6.4 13.92a6 6 0 0 1 0-3.84V7.49H3.06a10 10 0 0 0 0 9.02l3.34-2.59Z"/>
                        <path fill="#EA4335" d="M12 5.96c1.47 0 2.79.51 3.82 1.51l2.87-2.87A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.94 5.49l3.34 2.59C7.19 7.72 9.4 5.96 12 5.96Z"/>
                    </svg><span>Continue with Google</span></a>
                    <?php if (get_config('FB_APP_ID') && get_config('FB_APP_SECRET')): ?>
                        <a class="social-btn facebook" href="<?= h(build_app_url('authentication/facebook-login.php')) ?>?action=connect"><span>f</span>Continue with Facebook</a>
                    <?php endif; ?>
                </div>

                <div class="auth-footer-card">
                    <span>New here?</span>
                    <a href="register.php">Create an account</a>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        document.querySelectorAll('.show-password').forEach(button => {
            button.addEventListener('click', () => {
                const target = document.getElementById(button.dataset.target);
                if (!target) return;
                const type = target.type === 'password' ? 'text' : 'password';
                target.type = type;
                button.textContent = type === 'password' ? 'Show' : 'Hide';
                button.setAttribute('aria-pressed', String(type === 'text'));
            });
        });
        document.getElementById('loginForm')?.addEventListener('submit', event => {
            const button = document.getElementById('loginSubmit');
            if (button) {
                button.classList.add('button-loading');
                button.disabled = true;
            }
        });
    </script>
</body>
</html>
