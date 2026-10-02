<?php

function sendEmail($to, $subject, $message, $from = 'no-reply@ardaveapparel.com') {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: Ardave Apparel <' . $from . '>';
    $headers[] = 'Reply-To: ' . $from;

    $headerString = implode("\r\n", $headers);
    return mail($to, $subject, $message, $headerString);
}

function buildWelcomeEmail($customerName, $otpCode = null) {
    $otpHtml = $otpCode ? "<p>Your verification code is <strong>{$otpCode}</strong>.</p>" : '';
    return "<html><body>" .
           "<h2>Welcome to Ardave Apparel, {$customerName}</h2>" .
           "<p>Thank you for joining our store. We are excited to help you customize apparel with premium quality and fast local delivery.</p>" .
           $otpHtml .
           "<p>If you did not request this email, please ignore this message.</p>" .
           "<p>Best regards,<br>Ardave Apparel Team</p>" .
           "</body></html>";
}

?>
