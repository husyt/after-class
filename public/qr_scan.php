<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';
$message = '';
$show_approve = false;

if ($token) {
    $stmt = $pdo->prepare("SELECT id, status, expires_at FROM qr_sessions WHERE token = ?");
    $stmt->execute([$token]);
    $qr = $stmt->fetch();
    
    if (!$qr) $message = 'Invalid QR code.';
    elseif (strtotime($qr['expires_at']) < time()) $message = 'QR code expired.';
    elseif ($qr['status'] === 'approved') $message = 'QR already used.';
    else $show_approve = true;
} else {
    $message = 'No QR code provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_approve) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $stmt = $pdo->prepare("UPDATE qr_sessions SET status = 'approved', user_id = ? WHERE token = ?");
        $stmt->execute([$user['id'], $token]);
        $message = 'Approved! You can close this page.';
        $show_approve = false;
    } else {
        $message = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Login | After Class</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        body { background:#f5f5f5; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { background:#fff; border-radius:16px; padding:32px 24px; max-width:400px; width:100%; box-shadow:0 8px 32px rgba(0,0,0,0.08); text-align:center; }
        .brand-mark { width:56px; height:56px; background:#d13639; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#fff; margin-bottom:16px; }
        h1 { font-size:22px; margin-bottom:8px; }
        .sub { color:#777; font-size:14px; margin-bottom:24px; line-height:1.5; }
        .alert { padding:12px; border-radius:8px; font-size:13px; margin-bottom:20px; }
        .alert-error { background:#ffebee; color:#d32f2f; }
        .alert-success { background:#e8f5e9; color:#2e7d32; }
        input { width:100%; padding:14px; background:#f4f4f4; border:2px solid transparent; border-radius:10px; font-family:inherit; font-size:14px; margin-bottom:14px; }
        input:focus { outline:none; background:#fff; border-color:#1a1a1a; }
        button { width:100%; padding:14px; background:#d13639; color:#fff; border:none; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand-mark">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <h1>Approve Login</h1>
        <p class="sub">Confirm your identity to sign in on the other device.</p>

        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'Invalid') !== false || strpos($message, 'expired') !== false ? 'alert-error' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($show_approve): ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Approve & Sign In</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>