<?php

require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

try {

    // Generate random 6-digit code
    $code = (string) random_int(100000, 999999);

    // Store hashed version in the session
    $_SESSION['qr_verification_hash'] =
        password_hash($code, PASSWORD_DEFAULT);

    // Code expires after 5 minutes
    $_SESSION['qr_verification_expires'] =
        time() + 300;

    // Reset failed attempts
    $_SESSION['qr_verification_attempts'] = 0;


    // Text stored inside the QR
    // Phone camera will read this
    $qrText =
        'EqualPath Verification Code: ' . $code;


    echo json_encode([
        'success' => true,
        'qr_text' => $qrText,
        'expires_in' => 300
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to generate QR verification.'
    ]);
}