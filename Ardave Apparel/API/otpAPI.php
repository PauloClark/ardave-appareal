<?php

function generateOTP($length = 6) {
    $numbers = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $numbers[random_int(0, strlen($numbers) - 1)];
    }
    return $otp;
}

function sendOtpEmail($email, $otpCode) {
    $subject = 'Your Ardave Apparel verification code';
    $message = buildWelcomeEmail('', $otpCode);
    return sendEmail($email, $subject, $message);
}

function storeOtp($email, $otpCode) {
    if (!file_exists(__DIR__ . '/otp_store.json')) {
        file_put_contents(__DIR__ . '/otp_store.json', json_encode([]));
    }
    $store = json_decode(file_get_contents(__DIR__ . '/otp_store.json'), true);
    $store[$email] = [
        'code' => $otpCode,
        'expires' => time() + 300,
    ];
    file_put_contents(__DIR__ . '/otp_store.json', json_encode($store, JSON_PRETTY_PRINT));
}

function verifyOtp($email, $otpCode) {
    if (!file_exists(__DIR__ . '/otp_store.json')) {
        return false;
    }
    $store = json_decode(file_get_contents(__DIR__ . '/otp_store.json'), true);
    if (empty($store[$email])) {
        return false;
    }
    $entry = $store[$email];
    if ($entry['expires'] < time()) {
        unset($store[$email]);
        file_put_contents(__DIR__ . '/otp_store.json', json_encode($store, JSON_PRETTY_PRINT));
        return false;
    }
    return hash_equals($entry['code'], $otpCode);
}

?>
