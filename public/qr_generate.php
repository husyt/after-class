<?php
// public/qr_generate.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Clean up any previous QR token for this session
    if (!empty($_SESSION['qr_token'])) {
        $stmt = $pdo->prepare("DELETE FROM qr_sessions WHERE token = ?");
        $stmt->execute([$_SESSION['qr_token']]);
    }

    // Generate a fresh token
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

    // Insert
    $stmt = $pdo->prepare(
        "INSERT INTO qr_sessions (token, status, expires_at) 
         VALUES (?, 'pending', ?)"
    );
    $stmt->execute([$token, $expires]);

    $_SESSION['qr_token'] = $token;

    // Build the URL that the phone will open
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $base     = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    // If the host is localhost, replace with LAN IP so phone can reach it
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $lan_ip = gethostbyname(gethostname());
        if ($lan_ip && $lan_ip !== '127.0.0.1') {
            $port = $_SERVER['SERVER_PORT'] ?? '80';
            $host = $lan_ip . ':' . $port;
        }
    }

    $scan_url = $protocol . '://' . $host . $base . '/qr_scan.php?token=' . $token;

    echo json_encode([
        'success'    => true,
        'qr_url'     => $scan_url,
        'token'      => $token,
        'expires_in' => 300,
    ]);

} catch (Throwable $e) {
    error_log("QR Generate Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to generate QR.']);
}