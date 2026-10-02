<?php
// Shared helper functions for Ardave Apparel
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/connection.php';

// Ensure session cookie uses site-root path and consistent SameSite settings
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $cookieOptions = [
        'lifetime' => $cookieParams['lifetime'] ?? 0,
        'path' => '/', // ensure cookie sent for entire site (fixes subpath/path-with-space issues)
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $cookieParams['secure'] ?? false,
        'httponly' => $cookieParams['httponly'] ?? true,
        'samesite' => $cookieParams['samesite'] ?? 'Lax',
    ];
    session_set_cookie_params($cookieOptions);
    session_start();
}

// CSRF helpers
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // Optional: store creation time if you want to expire tokens
        $_SESSION['csrf_token_created_at'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Centralized logout helper to fully clear session and cookies
function logout_user(?string $redirectUrl = null): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Clear session variables
    $_SESSION = [];
    session_unset();

    // Explicitly unset known auth/session keys and any OAuth-related keys
    $keys = [
        'user_id', 'logged_in', 'user_name', 'user_email', 'user_profile_picture', 'user_login_provider',
        'google_state', 'oauth_redirect_after_login', 'auth_message', 'redirect_after_login', 'csrf_token', 'csrf_token_created_at'
    ];
    foreach ($keys as $k) {
        if (isset($_SESSION[$k])) unset($_SESSION[$k]);
        if (isset($_COOKIE[$k])) setcookie($k, '', time() - 42000, '/');
    }

    // Destroy the session cookie using options array for PHP 7.3+ (SameSite support)
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $params['secure'] ?? false,
            'httponly' => $params['httponly'] ?? true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    // Destroy session on server
    session_destroy();
    session_write_close();

    // Regenerate a new session id to avoid session fixation
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);

    // Redirect if requested
    if ($redirectUrl === null) {
        $redirectUrl = build_app_url('index.php');
    }
    header('Location: ' . $redirectUrl);
    exit;
}

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function build_app_url(string $path): string {
    static $baseUrl = null;
    if ($baseUrl === null) {
        $projectRoot = str_replace('\\', '/', dirname(__DIR__));
        $documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__));
        $baseUrl = str_replace($documentRoot, '', $projectRoot);
        $baseUrl = '/' . trim($baseUrl, '/');
        if ($baseUrl === '/') {
            $baseUrl = '';
        }
    }

    $base = trim($baseUrl, '/');
    $path = trim($path, '/');
    $segments = $base !== '' ? array_merge(explode('/', $base), explode('/', $path)) : explode('/', $path);
    return '/' . implode('/', array_map('rawurlencode', $segments));
}

function persist_login_metadata(PDO $pdo, int $userId, string $name, string $email, ?string $profilePicture = null, ?string $provider = null): void {
    $updateUser = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $updateUser->execute([$name, $email, $userId]);

    $customerStmt = $pdo->prepare('SELECT id FROM customers WHERE user_id = ? LIMIT 1');
    $customerStmt->execute([$userId]);
    $customer = $customerStmt->fetch();
    if ($customer) {
        $customerUpdate = $pdo->prepare('UPDATE customers SET name = ?, email = ? WHERE id = ?');
        $customerUpdate->execute([$name, $email, $customer['id']]);
    } else {
        $customerInsert = $pdo->prepare('INSERT INTO customers (user_id,name,email,phone,address) VALUES (?,?,?,?,NULL)');
        $customerInsert->execute([$userId, $name, $email, null]);
    }
}

function set_authenticated_user(PDO $pdo, int $userId, string $name, string $email, ?string $profilePicture = null, ?string $provider = null): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in'] = true;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_profile_picture'] = $profilePicture ?: '';
    $_SESSION['user_login_provider'] = $provider ?: 'email';

    persist_login_metadata($pdo, $userId, $name, $email, $profilePicture, $provider);

    $stmt = $pdo->prepare('SELECT id,email,name,phone,is_verified,role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $_SESSION['user_name'] = $row['name'] ?: $name;
        $_SESSION['user_email'] = $row['email'] ?: $email;
        $_SESSION['user_role'] = $row['role'] ?? 'customer';
    }
}

function is_admin(): bool {
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_admin(): void {
    if (!is_admin()) {
        header('HTTP/1.1 403 Forbidden');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Access Denied</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-dark text-white"><div class="d-flex align-items-center justify-content-center vh-100"><div class="text-center"><h1 class="display-6">Access Denied</h1><p class="lead">You do not have permission to access this page.</p><a href="' . h(build_app_url('index.php')) . '" class="btn btn-light">Return Home</a></div></div></body></html>';
        exit;
    }
}

function require_login_for_flow(?string $scriptName = null): void {
    $scriptName = $scriptName ?: basename($_SERVER['SCRIPT_FILENAME'] ?? '');
    $guardedScripts = ['checkout.php', 'upload-payment.php', 'track-order.php'];
    $allowedScripts = ['login.php', 'register.php', 'google-login.php', 'facebook-login.php', 'logout.php', 'otp.php', 'forgot-password.php'];

    if (!in_array($scriptName, $guardedScripts, true) || in_array($scriptName, $allowedScripts, true)) {
        return;
    }

    if (!is_logged_in()) {
        $_SESSION['auth_message'] = 'Please Login First';
        $redirectUri = $_SERVER['REQUEST_URI'] ?? '';
        if ($redirectUri !== '') {
            $_SESSION['redirect_after_login'] = $redirectUri;
        }
        header('Location: ' . build_app_url('authentication/login.php') . '?message=login_required' . ($redirectUri !== '' ? '&redirect=' . rawurlencode($redirectUri) : ''));
        exit;
    }
}

// Authentication helpers
function is_logged_in(): bool {
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . build_app_url('authentication/login.php'));
        exit;
    }
}

function current_user(?PDO $pdo = null) {
    if (!is_logged_in()) return null;
    $pdo = $pdo ?? $GLOBALS['pdo'];
    $stmt = $pdo->prepare('SELECT id,email,name,phone,is_verified FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

require_login_for_flow();

// OTP functions (DB-backed)
function create_otp(string $email, ?int $user_id = null, ?PDO $pdo = null): string {
    $pdo = $pdo ?? $GLOBALS['pdo'];
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO otp_codes (user_id,email,code,expires_at) VALUES (?,?,?,?)');
    $stmt->execute([$user_id, $email, $code, $expires]);
    return $code;
}

function verify_otp(string $email, string $code, ?PDO $pdo = null): bool {
    $pdo = $pdo ?? $GLOBALS['pdo'];
    $stmt = $pdo->prepare('SELECT id,user_id,expires_at,used FROM otp_codes WHERE email = ? AND code = ? AND used = 0 ORDER BY id DESC LIMIT 1');
    $stmt->execute([$email, $code]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if (new DateTime($row['expires_at']) < new DateTime()) return false;
    // mark used
    $u = $pdo->prepare('UPDATE otp_codes SET used = 1 WHERE id = ?');
    $u->execute([$row['id']]);
    return true;
}

// Password reset functions
function create_password_reset(string $email, ?PDO $pdo = null): string {
    $pdo = $pdo ?? $GLOBALS['pdo'];
    // find user id if exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $user_id = $user['id'] ?? null;
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id,email,token,expires_at) VALUES (?,?,?,?)');
    $stmt->execute([$user_id, $email, $token, $expires]);
    return $token;
}

function verify_password_reset(string $token, ?PDO $pdo = null) {
    $pdo = $pdo ?? $GLOBALS['pdo'];
    $stmt = $pdo->prepare('SELECT id,user_id,email,expires_at,used FROM password_resets WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) return null;
    if ($row['used']) return null;
    if (new DateTime($row['expires_at']) < new DateTime()) return null;
    return $row;
}

function mark_password_reset_used(int $id, ?PDO $pdo = null): void {
    $pdo = $pdo ?? $GLOBALS['pdo'];
    $stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
    $stmt->execute([$id]);
}

// Simple mail wrapper
function send_mail(string $to, string $subject, string $body): bool {
    $headers = "From: no-reply@ardaveapparel.local\r\nContent-Type: text/plain; charset=utf-8";
    return @mail($to, $subject, $body, $headers);
}

// Cart helpers (basic persistent cart)
function get_or_create_cart(?int $user_id = null, ?PDO $pdo = null) {
    $pdo = $pdo ?? $GLOBALS['pdo'];
    if ($user_id) {
        $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if ($row) return $row['id'];
    }
    // create a guest cart
    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare('INSERT INTO carts (user_id, session_token) VALUES (?,?)');
    $stmt->execute([$user_id, $token]);
    return $pdo->lastInsertId();
}
