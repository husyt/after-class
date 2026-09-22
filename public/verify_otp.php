<?php
define('REQUIRE_LOGIN', false);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
if (hasPassed2FA()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$resend_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $entered = preg_replace('/\D/', '', $_POST['code'] ?? '');
    
    if (strlen($entered) !== 6) {
        $error = 'Please enter the 6-digit code.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, code, attempts, expires_at FROM otp_codes 
                 WHERE user_id = ? AND used = 0 
                 ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([$_SESSION['user_id']]);
            $otp = $stmt->fetch();
            
            if (!$otp) {
                $error = 'No active code found. Please log in again.';
            } elseif (strtotime($otp['expires_at']) < time()) {
                $error = 'This code has expired. Please request a new one.';
            } elseif ($otp['attempts'] >= 3) {
                $error = 'Too many failed attempts. Please log in again.';
            } elseif (hash_equals($otp['code'], $entered)) {
                $stmt = $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
                $stmt->execute([$otp['id']]);
                
                // ============================================
                // SUCCESS — Mark 2FA verified
                // ============================================
                $user_id = $_SESSION['user_id'];  // capture before regenerate
                
                $_SESSION['2fa_verified'] = true;
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time']    = time();
                
                // Regenerate session ID for security, but keep session data
                session_regenerate_id(true);
                
                // Re-assign after regenerate (session data should persist, but be safe)
                $_SESSION['user_id']       = $user_id;
                $_SESSION['2fa_verified']  = true;
                
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
                
                logActivity($pdo, $user_id, '2FA verified - full login');
                
                if (!empty($_SESSION['stay_signed_in'])) {
                    setcookie('after_class_user', $user_id, [
                        'expires'  => time() + (86400 * 30),
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                }
                
                header('Location: dashboard.php');
                exit;
            } else {
                $stmt = $pdo->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?");
                $stmt->execute([$otp['id']]);
                
                $remaining = 2 - $otp['attempts'];
                $error = "Incorrect code. {$remaining} attempt(s) remaining.";
            }
        } catch (PDOException $e) {
            error_log("OTP Verify Error: " . $e->getMessage());
            $error = 'Verification error. Please try again.';
        }
    }
}

if (isset($_POST['resend'])) {
    require_once __DIR__ . '/../includes/mailer.php';
    try {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 300);
        
        $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $code, $expires]);
        
        $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();
        
        if (sendOTPEmail($u['email'], $u['username'], $code)) {
            $resend_success = 'A new code has been sent to your email.';
        } else {
            $error = 'Unable to send code. Try again later.';
        }
    } catch (PDOException $e) {
        error_log("Resend Error: " . $e->getMessage());
        $error = 'System error. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify | After Class</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .otp-inputs { display: flex; gap: 10px; justify-content: center; margin: 24px 0; }
        .otp-inputs input {
            width: 52px; height: 60px; text-align: center; font-size: 24px; font-weight: 700;
            background: #f4f4f4; border: 2px solid transparent; border-radius: 10px;
            color: #1a1a1a; font-family: inherit;
        }
        .otp-inputs input:focus { outline: none; background: #fff; border-color: #1a1a1a; }
        .otp-inputs input.filled { background: #fff; border-color: #d13639; }
        .resend-link { text-align: center; font-size: 13px; color: #777; margin-top: 20px; }
        .resend-link button { background: none; border: none; color: #d13639; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 13px; padding: 0; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-panel">
        <a href="logout.php" class="back-link" style="font-size:13px;color:#666;text-decoration:none;">← Cancel & sign out</a>

        <div class="brand" style="margin-top:24px;">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1>AFTER CLASS</h1>
        </div>

        <h2 style="font-size:24px;font-weight:800;margin-bottom:8px;">Two-Factor Verification</h2>
        <p style="font-size:13px;color:#777;margin-bottom:24px;line-height:1.6;">
            We sent a 6-digit code to <strong><?= htmlspecialchars($_SESSION['email'] ?? 'your email') ?></strong>.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error"><span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>
        <?php if ($resend_success): ?>
            <div class="alert alert-success"><span><?= htmlspecialchars($resend_success) ?></span></div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <div class="otp-inputs" id="otpInputs">
                <input type="text" maxlength="1" inputmode="numeric" autofocus>
                <input type="text" maxlength="1" inputmode="numeric">
                <input type="text" maxlength="1" inputmode="numeric">
                <input type="text" maxlength="1" inputmode="numeric">
                <input type="text" maxlength="1" inputmode="numeric">
                <input type="text" maxlength="1" inputmode="numeric">
            </div>
            <input type="hidden" name="code" id="codeHidden">

            <div class="submit-row">
                <button type="submit" class="submit-btn" style="width:auto;padding:0 32px;border-radius:32px;">
                    <span style="font-weight:700;font-size:14px;">Verify Code</span>
                </button>
            </div>
        </form>

        <div class="resend-link">
            Didn't receive it?
            <form method="POST" style="display:inline;">
                <button type="submit" name="resend" value="1">Resend code</button>
            </form>
        </div>
    </div>

    <div class="visual-panel">
        <video class="bg-video" autoplay muted loop playsinline preload="auto">
            <source src="../assets/cyberpunk.mp4" type="video/mp4">
        </video>
        <div class="visual-overlay"></div>
    </div>
</div>

<script>
const inputs = document.querySelectorAll('#otpInputs input');
const hidden = document.getElementById('codeHidden');
const form = document.getElementById('otpForm');

function updateHidden() {
    hidden.value = Array.from(inputs).map(i => i.value).join('');
    inputs.forEach(i => i.classList.toggle('filled', !!i.value));
    if (hidden.value.length === 6) setTimeout(() => form.submit(), 200);
}

inputs.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < inputs.length - 1) inputs[idx + 1].focus();
        updateHidden();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) inputs[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        pasted.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
        if (pasted.length === 6) inputs[5].focus();
        updateHidden();
    });
});
</script>
</body>
</html>