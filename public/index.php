<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id']) && hasPassed2FA()) {
    header('Location: dashboard.php');
    exit;
}
if (isset($_SESSION['user_id']) && !hasPassed2FA()) {
    header('Location: verify_otp.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreSync | Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="login-wrapper">
    <div class="login-panel">
        <div class="brand">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1>CoreSync</h1>
        </div>

        <div class="tabs">
            <button class="tab active" data-tab="signin">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                Sign-in
            </button>
            <button class="tab" data-tab="qr">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                QR Code
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" id="alertBox">
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span><?= sanitize($success) ?></span>
            </div>
        <?php endif; ?>

        <div id="signinPanel">
            <form action="authenticate.php" method="POST" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required maxlength="50">
                    <span class="field-error" id="username-error"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required minlength="8">
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <span class="field-error" id="password-error"></span>
                </div>
               <div class="email-notice" title="You'll receive a verification code by email.">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="11" width="18" height="11" rx="2"/>
        <path d="M7 11V7a5 5 0 0110 0v4"/>
    </svg>
    <span>2FA code sent by email</span>
</div>
                <div class="social-row">
                    <button type="button" class="social-btn fb">f</button>
                    <button type="button" class="social-btn google">G</button>
                    <button type="button" class="social-btn apple">A</button>
                    <button type="button" class="social-btn xbox">X</button>
                    <button type="button" class="social-btn ps">PS</button>
                </div>

                <div class="checkbox-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="stay_signed_in" id="stay_signed_in">
                        <span class="checkmark"></span>
                        Stay signed in
                    </label>
                </div>

                <div class="submit-row">
                    <button type="submit" class="submit-btn" id="submitBtn" aria-label="Sign In">
                        <svg class="arrow-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                        </svg>
                        <svg class="spinner" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" opacity="0.25"/>
                            <path d="M12 2a10 10 0 019.95 9" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div id="qrPanel" style="display:none; text-align:center; margin-bottom:24px;">
            <p style="font-size:13px; color:#666; margin-bottom:16px;">Scan this QR code with your phone to sign in.</p>
            <div class="qr-box">
                <div id="qrCodeContainer"><div class="qr-loading">Generating...</div></div>
            </div>
            <div id="qrStatus" class="qr-status">
                <span class="pulse-dot"></span> Waiting for scan...
            </div>
            <button type="button" id="refreshQR" class="qr-refresh-btn">Refresh QR Code</button>
        </div>

        <div class="footer-links">
            <a href="forgot_password.php">Can't sign in?</a>
            <a href="register.php">Create account</a>
        </div>

        <div class="legal">
            <p>This app is protected by hCaptcha and its Privacy Policy and Terms of Service apply.</p>
            <span class="version">v1.0.0</span>
        </div>
    </div>

    <div class="visual-panel">
        <video class="bg-video" autoplay muted loop playsinline preload="auto">
            <source src="../assets/cyberpunk.mp4" type="video/mp4">
        </video>
        <div class="visual-overlay"></div>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Signing in...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="js/script.js"></script>
<script src="js/qr-login.js"></script>
</body>
</html>