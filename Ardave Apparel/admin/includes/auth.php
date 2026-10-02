<?php
if (!defined('ADMIN_INIT')) {
    die('Unauthorized access.');
}
require_once __DIR__ . '/../../includes/functions.php';

function admin_redirect_login(): void {
    header('Location: ' . build_app_url('authentication/login.php') . '?message=login_required');
    exit;
}

if (!is_logged_in()) {
    admin_redirect_login();
}

if (!is_admin()) {
    http_response_code(403);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Access Denied</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-dark text-white"><div class="d-flex align-items-center justify-content-center vh-100"><div class="text-center"><h1 class="display-6">Access Denied</h1><p class="lead">You do not have permission to access this page.</p><a href="' . h(build_app_url('index.php')) . '" class="btn btn-light">Return Home</a></div></div></body></html>';
    exit;
}
