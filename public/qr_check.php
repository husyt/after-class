<?php
// public/qr_check.php
// Computer polls this every 2 seconds to detect approval

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_SESSION['qr_token'] ?? '';

if (!$token) {
    echo json_encode(['success' => false, 'status' => 'no_token']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT status, user_id, expires_at FROM qr_sessions WHERE token = ? LIMIT 1"
);
$stmt->execute([$token]);
$qr = $stmt->fetch();

if (!$qr) {
    echo json_encode(['success' => false, 'status' => 'not_found']);
    exit;
}

// Expired?
if (strtotime($qr['expires_at']) < time() && $qr['status'] === 'pending') {
    $stmt = $pdo->prepare("UPDATE qr_sessions SET status = 'expired' WHERE token = ?");
    $stmt->execute([$token]);
    echo json_encode(['success' => true, 'status' => 'expired']);
    exit;
}

// Approved?
if ($qr['status'] === 'approved' && $qr['user_id']) {
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$qr['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'status' => 'user_not_found']);
        exit;
    }

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['2fa_verified']  = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time']    = time();

    session_regenerate_id(true);

    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['role']         = $user['role'];
    $_SESSION['2fa_verified'] = true;

    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    logActivity($pdo, $user['id'], 'QR login approved');

    $stmt = $pdo->prepare("DELETE FROM qr_sessions WHERE token = ?");
    $stmt->execute([$token]);

    unset($_SESSION['qr_token']);

    echo json_encode([
        'success'  => true,
        'status'   => 'approved',
        'redirect' => 'dashboard.php'
    ]);
    exit;
}

echo json_encode(['success' => true, 'status' => 'pending']);