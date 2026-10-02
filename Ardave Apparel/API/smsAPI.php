<?php
// Deprecated placeholder: never report delivery without a provider.
function sendSms($phoneNumber, $message) {
    return ['success' => false, 'message' => 'Legacy SMS sending is disabled. Use Twilio Verify.'];
}
