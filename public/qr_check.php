<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
if (empty($token)) {
    echo json_encode(['status' => 'error']);
    exit;
}

$stmt = $pdo->prepare("SELECT status, user_id, expires_at FROM qr_sessions WHERE token = ?");
$stmt->execute([$token]);
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(['status' => 'expired']);
    exit;
}
if (strtotime($session['expires_at']) < time()) {
    echo json_encode(['status' => 'expired']);
    exit;
}

if ($session['status'] === 'approved' && $session['user_id']) {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$session['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['2fa_verified'] = true;
        
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $stmt = $pdo->prepare("DELETE FROM qr_sessions WHERE token = ?");
        $stmt->execute([$token]);
        
        echo json_encode(['status' => 'approved']);
        exit;
    }
}

echo json_encode(['status' => $session['status']]);
?>