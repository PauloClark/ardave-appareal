<?php
// Negative/security tests only: no SMS, fake approval, or successful OTP login.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/phone-auth.php';
putenv('SMS_LOGIN_ENABLED=false');
function check(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
    echo "PASS: $label\n";
}
foreach (['0917 123 4567', '9171234567', '639171234567', '+63 (917) 123-4567'] as $phone) {
    check(phone_normalize($phone) === '+639171234567', "normalize $phone");
}
foreach (['', '+12025550123', '0917123456', '+6309171234567', '09171234567<script>', '++639171234567', '0281234567'] as $phone) {
    check(phone_normalize($phone) === null, 'reject invalid/non-PH mobile');
}
check(!phone_enabled(), 'live SMS disabled');
check(phone_twilio('Verifications', ['To' => '+639171234567']) === null, 'no provider call while disabled');
check(!phone_provider_matches(null, '+639171234567', 'approved'), 'missing provider response rejected');
check(!phone_provider_matches(['status' => 'pending'], '+639171234567', 'approved'), 'pending is not approved');
check(!phone_provider_matches(['status' => 'approved'], '+639171234567', 'approved'), 'unbound approval rejected');
check(phone_check($pdo, '123456', null) === null && !is_logged_in(), 'disabled checks cannot create login');
check(!verify_csrf_token('invalid'), 'invalid CSRF rejected');
$token = get_csrf_token();
check(verify_csrf_token($token), 'session CSRF accepted');
foreach (['https://example.com', '//example.com', '/\\example.com', '/%0d%0aLocation:evil'] as $target) {
    check(phone_redirect($target) === build_app_url('index.php'), 'unsafe redirect rejected');
}
$pdo->beginTransaction();
try {
    $key = 'test:' . bin2hex(random_bytes(16));
    check(phone_limit($pdo, $key, 2, 60), 'rate limit first request');
    check(phone_limit($pdo, $key, 2, 60), 'rate limit second request');
    check(!phone_limit($pdo, $key, 2, 60), 'rate limit excess rejected');
    $pdo->prepare('UPDATE auth_phone_limits SET expires_at=0 WHERE bucket=?')->execute([hash('sha256', $key)]);
    check(phone_limit($pdo, $key, 2, 60), 'rate limit resets after expiry');
    $ids = [];
    for ($i = 0; $i < 2; $i++) {
        $pdo->prepare('INSERT INTO users (email,password,name) VALUES (?,?,?)')->execute([bin2hex(random_bytes(12)) . '@test.invalid', password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), 'Rollback-only phone constraint test']);
        $ids[] = (int)$pdo->lastInsertId();
    }
    $insert = $pdo->prepare('INSERT INTO auth_phone_identities (user_id,phone,verified_at) VALUES (?,?,NOW())');
    // Transaction-only rows test storage constraints; they are never used to authenticate.
    $insert->execute([$ids[0], '+639000000000']);
    $rejected = false;
    try { $insert->execute([$ids[1], '+639000000000']); } catch (PDOException $e) { $rejected = $e->getCode() === '23000'; }
    check($rejected, 'one phone cannot belong to two accounts');
    $rejected = false;
    try { $insert->execute([$ids[0], '+639000000001']); } catch (PDOException $e) { $rejected = $e->getCode() === '23000'; }
    check($rejected, 'one account cannot hold two login identities');
    $sid = 'VE' . bin2hex(random_bytes(16));
    $consume = $pdo->prepare('INSERT INTO auth_phone_consumed VALUES (?,NOW())');
    $consume->execute([$sid]); $rejected = false;
    try { $consume->execute([$sid]); } catch (PDOException $e) { $rejected = $e->getCode() === '23000'; }
    check($rejected, 'provider verification cannot be consumed twice');
} finally { $pdo->rollBack(); }
echo "No SMS was sent and no OTP login was simulated. All test database writes rolled back.\n";
