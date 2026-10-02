<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Provide a valid name and email.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (email, password, name, phone, is_verified, created_at) VALUES (?, ?, ?, NULL, 1, NOW())');
                $stmt->execute([$email, $hash, $name]);
                $userId = (int)$pdo->lastInsertId();
                set_authenticated_user($pdo, $userId, $name, $email, null, 'email');
                header('Location: ../index.php');
                exit;
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
    <title>Register - Ardave Apparel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(build_app_url('assets/css/login.css')) ?>?v=20260924" rel="stylesheet">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <main class="auth-shell">
        <section class="form-panel">
            <div class="form-card">
                <div class="auth-heading">
                    <span class="eyebrow">Create Account</span>
                    <h1>Get started with Ardave Apparel</h1>
                    <p>Register to manage custom uniforms, jerseys and team orders.</p>
                </div>

                <?php if ($errors): ?>
                    <div class="alert auth-alert error"><span>❌</span><ul><?php foreach ($errors as $e) echo '<li>'.h($e).'</li>'; ?></ul></div>
                <?php endif; ?>

                <form id="registerForm" method="post" action="" novalidate>
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

                    <div class="auth-field">
                        <label for="registerName">Full Name</label>
                        <input id="registerName" name="name" type="text" autocomplete="name" class="form-control" placeholder="Your full name" required>
                    </div>

                    <div class="auth-field">
                        <label for="registerEmail">Email Address</label>
                        <input id="registerEmail" name="email" type="email" autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false" class="form-control" placeholder="name@example.com" required>
                    </div>

                    <div class="auth-field">
                        <label for="registerPassword">Password</label>
                        <div class="input-password-field">
                            <input id="registerPassword" aria-describedby="passwordStrength" name="password" type="password" autocomplete="new-password" class="form-control" placeholder="Create a password" required>
                            <button type="button" class="show-password" aria-pressed="false" data-target="registerPassword">Show</button>
                        </div>
                    </div>

                    <div class="password-strength" id="passwordStrength" aria-live="polite">Strength: <span>Enter password</span></div>

                    <div class="auth-field">
                        <label for="registerConfirmPassword">Confirm Password</label>
                        <div class="input-password-field">
                            <input id="registerConfirmPassword" name="confirm_password" type="password" autocomplete="new-password" class="form-control" placeholder="Confirm your password" required>
                            <button type="button" class="show-password" aria-pressed="false" data-target="registerConfirmPassword">Show</button>
                        </div>
                    </div>

                    <button id="registerSubmit" class="button-primary" type="submit">Register</button>
                </form>

                <div class="divider"><span>Or continue with</span></div>
                <div class="social-grid">
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
                    <span>Already have an account?</span>
                    <a href="login.php">Login</a>
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
        const strengthLabel = document.getElementById('passwordStrength')?.querySelector('span');
        document.getElementById('registerPassword')?.addEventListener('input', event => {
            const value = event.target.value;
            let strength = 'Weak';
            if (value.length >= 10 && /[A-Z]/.test(value) && /[0-9]/.test(value)) strength = 'Strong';
            else if (value.length >= 7) strength = 'Fair';
            if (strengthLabel) strengthLabel.textContent = strength;
        });
        document.getElementById('registerForm')?.addEventListener('submit', event => {
            const button = document.getElementById('registerSubmit');
            if (button) {
                button.classList.add('button-loading');
                button.disabled = true;
            }
        });
    </script>
</body>
</html>

    