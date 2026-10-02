<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function phone_normalize(string $raw): ?string {
    if (strlen($raw) > 40 || preg_match('/[^0-9+() .-]/', $raw)) return null;
    $n = preg_replace('/[() .-]/', '', trim($raw));
    if (preg_match('/^09[0-9]{9}$/D', $n)) $n = '+63' . substr($n, 1);
    elseif (preg_match('/^9[0-9]{9}$/D', $n)) $n = '+63' . $n;
    elseif (preg_match('/^639[0-9]{9}$/D', $n)) $n = '+' . $n;
    return preg_match('/^\+639[0-9]{9}$/D', $n) ? $n : null;
}
function phone_enabled(): bool {
    return get_config('SMS_LOGIN_ENABLED', 'false') === 'true'
        && preg_match('/^AC[0-9a-fA-F]{32}$/D', (string)get_config('TWILIO_ACCOUNT_SID', ''))
        && strlen((string)get_config('TWILIO_AUTH_TOKEN', '')) >= 32
        && preg_match('/^VA[0-9a-fA-F]{32}$/D', (string)get_config('TWILIO_VERIFY_SERVICE_SID', ''))
        && extension_loaded('curl');
}
function phone_ready(PDO $pdo): bool {
    if (!phone_enabled()) return false;
    try {
        foreach (['auth_phone_identities', 'auth_phone_challenges', 'auth_phone_limits', 'auth_phone_consumed'] as $table) {
            $pdo->query("SELECT 1 FROM $table LIMIT 0");
        }
        return true;
    } catch (PDOException $e) { return false; }
}
// Provider errors and credentials never leave this server-side adapter.
function phone_twilio(string $resource, array $fields): ?array {
    if (!phone_enabled() || !in_array($resource, ['Verifications', 'VerificationCheck'], true)) return null;
    $url = 'https://verify.twilio.com/v2/Services/' . get_config('TWILIO_VERIFY_SERVICE_SID') . '/' . $resource;
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_USERPWD => get_config('TWILIO_ACCOUNT_SID') . ':' . get_config('TWILIO_AUTH_TOKEN'),
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_CONNECTTIMEOUT_MS => 1500, CURLOPT_TIMEOUT_MS => 3000,
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_FOLLOWLOCATION => false]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $status < 200 || $status >= 300) {
        error_log('Twilio Verify request failed; HTTP status ' . (int)$status);
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}
function phone_provider_matches(?array $data, string $phone, string $status, ?string $sid = null): bool {
    return $data !== null && ($data['status'] ?? '') === $status
        && ($data['to'] ?? '') === $phone && ($data['channel'] ?? '') === 'sms'
        && ($data['service_sid'] ?? '') === get_config('TWILIO_VERIFY_SERVICE_SID')
        && preg_match('/^VE[0-9a-fA-F]{32}$/D', (string)($data['sid'] ?? ''))
        && ($sid === null || hash_equals($sid, $data['sid']));
}
// Atomic counters with windows starting at the first request, including cooldowns.
function phone_limit(PDO $pdo, string $key, int $max, int $seconds): bool {
    $now = time();
    $bucket = hash('sha256', $key);
    $stmt = $pdo->prepare('INSERT INTO auth_phone_limits (bucket,hits,expires_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE hits=IF(expires_at<=?,1,hits+1), expires_at=IF(expires_at<=?, ?, expires_at)');
    $stmt->execute([$bucket, $now + $seconds, $now, $now, $now + $seconds]);
    $stmt = $pdo->prepare('SELECT hits FROM auth_phone_limits WHERE bucket=?');
    $stmt->execute([$bucket]);
    return (int)$stmt->fetchColumn() <= $max;
}
function phone_allowed(PDO $pdo, string $phone, string $action, ?int $userId): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown'; // Never trust client-supplied forwarding headers.
    $limits = $action === 'send'
        ? [["send:ip:$ip", 10, 3600], ["send:phone:$phone", 5, 3600], ["send:cooldown:$phone", 1, 60], ['send:global', 100, 3600]]
        : [["check:ip:$ip", 30, 900], ["check:phone:$phone", 10, 900]];
    if ($userId) $limits[] = ["$action:user:$userId", $action === 'send' ? 5 : 10, 900];
    $allowed = true;
    foreach ($limits as [$key, $max, $seconds]) {
        if (!phone_limit($pdo, $key, $max, $seconds)) $allowed = false;
    }
    return $allowed;
}
function phone_challenge(PDO $pdo): ?array {
    $id = $_SESSION['phone_challenge'] ?? '';
    if (!is_string($id) || !preg_match('/^[a-f0-9]{64}$/D', $id)) return null;
    $stmt = $pdo->prepare('SELECT * FROM auth_phone_challenges WHERE id=? AND consumed=0');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
function phone_start(PDO $pdo, string $phone, string $purpose, ?int $userId): void {
    if (!phone_ready($pdo)) return;
    $old = phone_challenge($pdo);
    if ($old) {
        if ((int)$old['next_send_at'] > time()) return;
        $pdo->prepare('UPDATE auth_phone_challenges SET consumed=1 WHERE id=?')->execute([$old['id']]);
    }
    $allowed = phone_allowed($pdo, $phone, 'send', $userId);
    $stmt = $pdo->prepare('SELECT user_id FROM auth_phone_identities WHERE phone=?');
    $stmt->execute([$phone]);
    $owner = $stmt->fetchColumn();
    $target = $purpose === 'login' ? ($owner ? (int)$owner : null) : $userId;
    $eligible = $purpose === 'login' ? $target !== null : ($userId !== null && (!$owner || (int)$owner === $userId));
    $id = bin2hex(random_bytes(32));
    $data = $allowed && $eligible ? phone_twilio('Verifications', ['To' => $phone, 'Channel' => 'sms']) : null;
    $sid = phone_provider_matches($data, $phone, 'pending') ? $data['sid'] : null;
    // Generic challenges are never proof of sending; only a provider SID can be checked.
    $expires = time() + 600;
    if ($sid && !empty($data['date_created'])) {
        $created = strtotime($data['date_created']);
        if ($created !== false) $expires = min($expires, $created + 600);
    }
    $pdo->prepare('INSERT INTO auth_phone_challenges (id,purpose,user_id,phone,verification_sid,expires_at,next_send_at) VALUES (?,?,?,?,?,?,?)')
        ->execute([$id, $purpose, $target, $phone, $sid, $expires, time() + 60]);
    $_SESSION['phone_challenge'] = $id;
}
function phone_resend(PDO $pdo, array $challenge): void {
    if ((int)$challenge['next_send_at'] > time() || (int)$challenge['expires_at'] <= time()) return;
    $pdo->prepare('UPDATE auth_phone_challenges SET next_send_at=? WHERE id=?')->execute([time()+60, $challenge['id']]);
    if (!phone_allowed($pdo, $challenge['phone'], 'send', $challenge['user_id'] ? (int)$challenge['user_id'] : null)) return;
    // Recheck eligibility; never send to an identity removed while this page was open.
    $stmt = $pdo->prepare('SELECT user_id FROM auth_phone_identities WHERE phone=?');
    $stmt->execute([$challenge['phone']]); $owner = $stmt->fetchColumn();
    $eligible = $challenge['purpose'] === 'login'
        ? $owner && (int)$owner === (int)$challenge['user_id']
        : $challenge['user_id'] && (!$owner || (int)$owner === (int)$challenge['user_id']);
    if (!$eligible) return;
    $data = phone_twilio('Verifications', ['To' => $challenge['phone'], 'Channel' => 'sms']);
    if (phone_provider_matches($data, $challenge['phone'], 'pending')) {
        $pdo->prepare('UPDATE auth_phone_challenges SET verification_sid=? WHERE id=?')->execute([$data['sid'], $challenge['id']]);
    }
}
// Returns a user only after an approved provider check and atomic one-time consumption.
function phone_check(PDO $pdo, string $code, ?int $authenticatedId): ?array {
    if (!phone_ready($pdo)) return null;
    $c = phone_challenge($pdo);
    if (!$c || (int)$c['expires_at'] <= time() || (int)$c['attempts'] >= 5) return null;
    if ($c['purpose'] === 'link' && (!$authenticatedId || $authenticatedId !== (int)$c['user_id'])) return null;
    $pdo->prepare('UPDATE auth_phone_challenges SET attempts=attempts+1 WHERE id=?')->execute([$c['id']]);
    if (!phone_allowed($pdo, $c['phone'], 'check', $authenticatedId) || !preg_match('/^[0-9]{4,10}$/D', $code) || !$c['verification_sid']) return null;
    $data = phone_twilio('VerificationCheck', ['VerificationSid' => $c['verification_sid'], 'Code' => $code]);
    if (!phone_provider_matches($data, $c['phone'], 'approved', $c['verification_sid'])) return null;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT consumed,expires_at FROM auth_phone_challenges WHERE id=? FOR UPDATE');
        $stmt->execute([$c['id']]); $locked = $stmt->fetch();
        if (!$locked || $locked['consumed'] || (int)$locked['expires_at'] <= time()) { $pdo->rollBack(); return null; }
        $pdo->prepare('INSERT INTO auth_phone_consumed (verification_sid,consumed_at) VALUES (?,NOW())')->execute([$c['verification_sid']]);
        $stmt = $pdo->prepare('SELECT id,name,email FROM users WHERE id=? FOR UPDATE');
        $stmt->execute([$c['user_id']]); $user = $stmt->fetch();
        if (!$user) { $pdo->rollBack(); return null; }
        if ($c['purpose'] === 'link') {
            // Delete and insert inside the transaction: a UNIQUE collision rolls back the old identity too.
            $pdo->prepare('DELETE FROM auth_phone_identities WHERE user_id=?')->execute([$c['user_id']]);
            $pdo->prepare('INSERT INTO auth_phone_identities (user_id,phone,verified_at) VALUES (?,?,NOW())')->execute([$c['user_id'], $c['phone']]);
        } else {
            $stmt = $pdo->prepare('SELECT user_id FROM auth_phone_identities WHERE phone=? AND user_id=? FOR UPDATE');
            $stmt->execute([$c['phone'], $c['user_id']]);
            if (!$stmt->fetch()) { $pdo->rollBack(); return null; }
        }
        $pdo->prepare('UPDATE auth_phone_challenges SET consumed=1 WHERE id=? OR verification_sid=?')->execute([$c['id'], $c['verification_sid']]);
        $pdo->commit(); unset($_SESSION['phone_challenge']);
        return $user;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return null; // Collision, replay, or storage failure must never authenticate.
    }
}
function phone_redirect(string $target): string {
    $decoded = rawurldecode($target);
    $root = rtrim(dirname(build_app_url('index.php')), '/') . '/';
    if (str_contains($decoded, "\\") || preg_match('/[\x00-\x20]/', str_replace(' ', '', $decoded)) || str_starts_with($decoded, '//')) return build_app_url('index.php');
    // Only local paths within this installation, without traversal or an external origin.
    if (!str_starts_with($target, $root) || preg_match('~(^|/)\.\.(/|$)~', $decoded)) return build_app_url('index.php');
    return $target;
}
