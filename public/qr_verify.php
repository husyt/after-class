<?php

require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    $code = trim(
        $input['code'] ?? ''
    );


    // ==========================================
    // CHECK FORMAT
    // ==========================================

    if (!preg_match('/^\d{6}$/', $code)) {

        echo json_encode([
            'success' => false,
            'message' => 'Enter the 6-digit verification code.'
        ]);

        exit;
    }


    // ==========================================
    // CHECK ACTIVE QR
    // ==========================================

    if (
        empty($_SESSION['qr_verification_hash']) ||
        empty($_SESSION['qr_verification_expires'])
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'No active QR verification. Generate a new QR.'
        ]);

        exit;
    }


    // ==========================================
    // CHECK EXPIRATION
    // ==========================================

    if (
        time() >
        $_SESSION['qr_verification_expires']
    ) {

        unset(
            $_SESSION['qr_verification_hash'],
            $_SESSION['qr_verification_expires'],
            $_SESSION['qr_verification_attempts']
        );

        echo json_encode([
            'success' => false,
            'message' => 'QR code expired. Generate a new QR.'
        ]);

        exit;
    }


    // ==========================================
    // ATTEMPT LIMIT
    // ==========================================

    $_SESSION['qr_verification_attempts'] =
        ($_SESSION['qr_verification_attempts'] ?? 0) + 1;


    if (
        $_SESSION['qr_verification_attempts'] > 5
    ) {

        unset(
            $_SESSION['qr_verification_hash'],
            $_SESSION['qr_verification_expires'],
            $_SESSION['qr_verification_attempts']
        );

        echo json_encode([
            'success' => false,
            'message' => 'Too many attempts. Generate a new QR.'
        ]);

        exit;
    }


    // ==========================================
    // VERIFY CODE
    // ==========================================

    if (
        password_verify(
            $code,
            $_SESSION['qr_verification_hash']
        )
    ) {

        $_SESSION['qr_verified'] = true;
        $_SESSION['qr_verified_at'] = time();


        unset(
            $_SESSION['qr_verification_hash'],
            $_SESSION['qr_verification_expires'],
            $_SESSION['qr_verification_attempts']
        );


        echo json_encode([
            'success' => true,
            'message' => 'QR verification successful.'
        ]);

        exit;
    }


    echo json_encode([
        'success' => false,
        'message' => 'Incorrect verification code.'
    ]);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Verification server error.'
    ]);
}