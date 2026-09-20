<?php
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function logActivity($pdo, $user_id, $activity) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $activity, $ip]);
}

function checkLockout($pdo, $username) {
    $stmt = $pdo->prepare("SELECT login_attempts, locked_until FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
        return "Account locked. Try again in {$remaining} minute(s).";
    }
    return false;
}

function recordFailedAttempt($pdo, $username) {
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE username = ?");
    $stmt->execute([$username]);
    
    $stmt = $pdo->prepare("SELECT login_attempts FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $attempts = $stmt->fetchColumn();
    
    if ($attempts >= 5) {
        $lock_until = date('Y-m-d H:i:s', time() + 900);
        $stmt = $pdo->prepare("UPDATE users SET locked_until = ? WHERE username = ?");
        $stmt->execute([$lock_until, $username]);
    }
}

function resetAttempts($pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
}
?>