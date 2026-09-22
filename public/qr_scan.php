<?php
// public/qr_scan.php
// Phone page — user enters email, clicks Send Approval Email

define('REQUIRE_LOGIN', false);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$show_form = false;
$user_email = '';

// Validate token
if (!$token) {
    $message = 'No QR code provided.';
    $message_type = 'error';
} else {
    $stmt = $pdo->prepare(
        "SELECT id, status, expires_at FROM qr_sessions WHERE token = ? LIMIT 1"
    );
    $stmt->execute([$token]);
    $qr = $stmt->fetch();

    if (!$qr) {
        $message = 'This QR code is invalid or has been removed.';
        $message_type = 'error';
    } elseif (strtotime($qr['expires_at']) < time()) {
        $message = 'This QR code has expired. Please generate a new one on your computer.';
        $message_type = 'error';
    } elseif ($qr['status'] === 'approved') {
        $message = 'Login already approved. Check your computer.';
        $message_type = 'success';
    } else {
        $show_form = true;
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    require_once __DIR__ . '/../includes/mailer.php';

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Look up user by email
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = 'No account found with that email. Please sign up first.';
            $message_type = 'error';
        } else {
            // Save email + user_id on the qr_session
            $stmt = $pdo->prepare(
                "UPDATE qr_sessions SET email = ?, user_id = ? WHERE token = ?"
            );
            $stmt->execute([$email, $user['id'], $token]);

            // Build approval URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'];
            $base     = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $approve_url = "{$protocol}://{$host}{$base}/qr_approve.php?token=" . urlencode($token);

            // Send email
            $sent = sendQRApprovalEmail($email, $user['username'], $approve_url);

            if ($sent) {
                $message = 'Approval email sent to <strong>' . htmlspecialchars($email) . '</strong>. Open your Gmail and tap the Approve button.';
                $message_type = 'success';
                $show_form = false;
                $user_email = $email;
            } else {
                $message = 'Could not send email. Please try again.';
                $message_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Login | EqualPath</title>
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
            padding: 36px 28px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            text-align: center;
        }
        .brand-mark {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #d13639, #7c3aed);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin-bottom: 18px;
        }
        h1 { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
        .sub { color: rgba(255,255,255,0.55); font-size: 13px; margin-bottom: 24px; line-height: 1.55; }
        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
            text-align: left;
        }
        .alert-error { background: rgba(209,54,57,0.15); color: #ff7c7f; border: 1px solid rgba(209,54,57,0.3); }
        .alert-success { background: rgba(46,204,113,0.15); color: #2ecc71; border: 1px solid rgba(46,204,113,0.3); }
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            color: white;
            margin-bottom: 14px;
        }
        input::placeholder { color: rgba(255,255,255,0.4); }
        input:focus {
            outline: none;
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.3);
        }
        button {
            width: 100%;
            padding: 14px;
            background: #d13639;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }
        button:hover { background: #b02c2f; }
        .icon-big {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: rgba(46,204,113,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2ecc71;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand-mark">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>

        <?php if ($show_form): ?>
            <h1>Approve Login</h1>
            <p class="sub">Enter your email address. We'll send you a confirmation link to approve this login.</p>
        <?php elseif ($message_type === 'success' && $user_email): ?>
            <div class="icon-big">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
            </div>
            <h1>Check Your Email</h1>
            <p class="sub">We sent a login approval link to your inbox.</p>
        <?php else: ?>
            <h1>Approve Login</h1>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form method="POST">
                <input type="email" name="email" placeholder="you@gmail.com" required autofocus>
                <button type="submit">Send Approval Email</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>