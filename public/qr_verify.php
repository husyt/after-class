<?php
// public/qr_verify.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code  = preg_replace('/\D/', '', $input['code'] ?? '');

if (strlen($code) !== 6) {
    echo json_encode(['success' => false, 'error' => 'Please enter a 6-digit code.']);
    exit;
}

// Sanity checks
if (empty($_SESSION['qr_verification_hash']) || empty($_SESSION['qr_pending_user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No QR code generated. Please refresh.']);
    exit;
}

if (time() > ($_SESSION['qr_verification_expires'] ?? 0)) {
    unset(
        $_SESSION['qr_verification_hash'],
        $_SESSION['qr_verification_expires'],
        $_SESSION['qr_verification_attempts'],
        $_SESSION['qr_pending_user_id'],
        $_SESSION['qr_pending_username']
    );
    echo json_encode(['success' => false, 'error' => 'QR code expired. Please refresh.']);
    exit;
}

$attempts = $_SESSION['qr_verification_attempts'] ?? 0;
if ($attempts >= 3) {
    unset(
        $_SESSION['qr_verification_hash'],
        $_SESSION['qr_verification_expires'],
        $_SESSION['qr_verification_attempts'],
        $_SESSION['qr_pending_user_id'],
        $_SESSION['qr_pending_username']
    );
    echo json_encode(['success' => false, 'error' => 'Too many attempts. Please refresh.']);
    exit;
}

// Verify
if (!password_verify($code, $_SESSION['qr_verification_hash'])) {
    $_SESSION['qr_verification_attempts'] = $attempts + 1;
    $remaining = 2 - $attempts;
    echo json_encode([
        'success' => false,
        'error'   => "Incorrect code. {$remaining} attempt(s) remaining."
    ]);
    exit;
}

// ============================================
// SUCCESS
// ============================================
$user_id = $_SESSION['qr_pending_user_id'];

$stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit;
}

// Set up full login session
$_SESSION['user_id']       = $user['id'];
$_SESSION['username']      = $user['username'];
$_SESSION['email']         = $user['email'];
$_SESSION['role']          = $user['role'];
$_SESSION['2fa_verified']  = true;
$_SESSION['last_activity'] = time();
$_SESSION['login_time']    = time();

// Clean up QR session data
unset(
    $_SESSION['qr_verification_hash'],
    $_SESSION['qr_verification_expires'],
    $_SESSION['qr_verification_attempts'],
    $_SESSION['qr_pending_user_id'],
    $_SESSION['qr_pending_username']
);

// Update last_login
$stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$stmt->execute([$user['id']]);

logActivity($pdo, $user['id'], 'QR login verified');

echo json_encode([
    'success'  => true,
    'redirect' => 'dashboard.php'
]);