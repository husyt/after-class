<?php
// public/qr_approve.php
// Target of the email button — marks the QR as approved

define('REQUIRE_LOGIN', false);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

$token   = $_GET['token'] ?? '';
$message = '';
$success = false;

if (!$token) {
    $message = 'No approval token provided.';
} else {
    $stmt = $pdo->prepare(
        "SELECT id, user_id, email, status, expires_at FROM qr_sessions WHERE token = ? LIMIT 1"
    );
    $stmt->execute([$token]);
    $qr = $stmt->fetch();

    if (!$qr) {
        $message = 'This approval link is invalid.';
    } elseif (strtotime($qr['expires_at']) < time()) {
        $message = 'This link has expired. Please scan the QR code again.';
    } elseif ($qr['status'] === 'approved') {
        $message = 'This login has already been approved.';
        $success = true;
    } else {
        $stmt = $pdo->prepare(
            "UPDATE qr_sessions 
             SET status = 'approved', approved = 1, approved_at = NOW() 
             WHERE token = ?"
        );
        $stmt->execute([$token]);

        $success = true;
        $message = 'Login approved! You can close this page and check your computer.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Approved | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #1a1a2e, #0a0a0f);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: white;
        }
        .card {
            background: rgba(20, 20, 30, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 40px 28px;
            max-width: 380px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            text-align: center;
        }
        .icon-big {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-success { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .icon-error   { background: rgba(209,54,57,0.15); color: #ff7c7f; }
        h1 { font-size: 24px; font-weight: 800; margin-bottom: 10px; }
        p { font-size: 14px; color: rgba(255,255,255,0.65); line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($success): ?>
            <div class="icon-big icon-success">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
            </div>
            <h1>Approved!</h1>
            <p><?= htmlspecialchars($message) ?></p>
        <?php else: ?>
            <div class="icon-big icon-error">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M15 9l-6 6M9 9l6 6"/>
                </svg>
            </div>
            <h1>Something went wrong</h1>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>