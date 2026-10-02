<?php
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only accept POST for logout to avoid CSRF via GET/caching
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . build_app_url('index.php'));
    exit;
}

// Verify CSRF token
 $token = $_POST['csrf_token'] ?? null;

// Primary path: valid CSRF token
if (verify_csrf_token($token)) {
    logout_user();
    // logout_user() exits
}

// Fallback 1: if the request includes the active session cookie and server-side session still shows a logged-in user, allow logout
$sessName = session_name();
if (!empty($_COOKIE[$sessName]) && !empty($_SESSION['user_id'])) {
    // session cookie present and server session indicates logged in — proceed to clear
    error_log('logout.php: CSRF missing but session cookie present; proceeding with logout for user_id=' . ($_SESSION['user_id'] ?? ''));
    logout_user();
}

// Fallback 2: same-origin POST (covers cases where Origin/Referer is present but CSRF token missing)
$host = $_SERVER['HTTP_HOST'] ?? '';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$sameOrigin = false;
if ($origin !== '' && (parse_url($origin, PHP_URL_HOST) === $host)) $sameOrigin = true;
if ($referer !== '' && (parse_url($referer, PHP_URL_HOST) === $host)) $sameOrigin = true;
if ($sameOrigin) {
    error_log('logout.php: CSRF token invalid but same-origin POST, proceeding with logout. Host=' . $host . ' Origin=' . $origin . ' Referer=' . $referer);
    logout_user();
}

// Not allowed
header('Location: ' . build_app_url('authentication/login.php') . '?message=invalid_csrf');
exit;

