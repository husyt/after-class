<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================================
// IF ALREADY LOGGED IN
// ============================================

if (!empty($_SESSION['user_id']) && !empty($_SESSION['2fa_verified'])) {
    header('Location: dashboard.php');
    exit;
}

if (!empty($_SESSION['user_id']) && empty($_SESSION['2fa_verified'])) {
    header('Location: verify_otp.php');
    exit;
}

// ============================================
// AUTO-LOGIN VIA REMEMBER COOKIE
// ============================================
if (!empty($_COOKIE['remember_token'])) {
    $cookie_token = $_COOKIE['remember_token'];

    try {
        $stmt = $pdo->prepare(
            "SELECT rt.user_id, rt.expires_at, u.username, u.email, u.role
             FROM remember_tokens rt
             JOIN users u ON u.id = rt.user_id
             WHERE rt.token = ? AND rt.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$cookie_token]);
        $remembered = $stmt->fetch();

        if ($remembered) {
            session_regenerate_id(true);

            $_SESSION['user_id']       = $remembered['user_id'];
            $_SESSION['username']      = $remembered['username'];
            $_SESSION['email']         = $remembered['email'];
            $_SESSION['role']          = $remembered['role'];
            $_SESSION['2fa_verified']  = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time']    = time();

            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$remembered['user_id']]);

            logActivity($pdo, $remembered['user_id'], 'Auto-login via remember-me cookie');

            header('Location: dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Remember-me lookup error: " . $e->getMessage());
    }
}

// ============================================
// MESSAGES
// ============================================
$error   = $_SESSION['login_error']   ?? '';
$success = $_SESSION['login_success'] ?? '';

unset($_SESSION['login_error'], $_SESSION['login_success']);

$remembered_username = $_SESSION['remember_username'] ?? '';
unset($_SESSION['remember_username']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EqualPath | Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link rel="manifest" href="/after-class/public/manifest.json">
<meta name="theme-color" content="#d13639">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="EqualPath">
<link rel="apple-touch-icon" href="/after-class/assets/icons/icon-192.png">
<link rel="icon" type="image/png" href="/after-class/assets/icons/icon-192.png">

    <style>
        /* ============================================
           FORM AREA SPACING
           ============================================ */
        #signinPanel {
            margin-bottom: 48px;
        }

        /* ============================================
           SIGN IN BUTTON
           ============================================ */
        .submit-row {
            margin-top: 0;
            margin-bottom: 24px;
        }

        .submit-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #d13639 0%, #a02025 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(209, 54, 57, 0.35);
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(209, 54, 57, 0.5);
            background: linear-gradient(135deg, #e04447 0%, #b02429 100%);
        }

        .submit-btn:hover::before { left: 100%; }

        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(209, 54, 57, 0.4);
        }

        .submit-btn .btn-label { font-weight: 800; }

        .submit-btn .arrow-icon { transition: transform 0.25s ease; }

        .submit-btn:hover .arrow-icon { transform: translateX(4px); }

        .submit-btn .spinner {
            display: none;
            animation: spin 0.8s linear infinite;
        }

        .submit-btn.loading .arrow-icon,
        .submit-btn.loading .btn-label { display: none; }

        .submit-btn.loading .spinner { display: block; }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ============================================
           ERROR STATE — red border + red message
           ============================================ */
        .form-group.has-error input {
            border-color: #d13639 !important;
            background: #fff5f5 !important;
        }

        .field-error {
            display: block;
            color: #d13639;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
            min-height: 16px;
        }

        /* ============================================
           FOOTER
           ============================================ */
        .footer-links {
            margin-top: 0;
            padding-top: 0;
        }

        .legal {
            margin-top: 32px;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>

<!-- ============================================
     PASSWORD TOGGLE
     ============================================ -->
<script>
(function () {
    'use strict';

    function togglePassword() {
        const passInput = document.getElementById('password');
        const eyeOpen   = document.getElementById('iconEyeOpen');
        const eyeClosed = document.getElementById('iconEyeClosed');

        if (!passInput) return;

        if (passInput.type === 'password') {
            passInput.setAttribute('type', 'text');
            if (eyeOpen)   eyeOpen.style.display   = 'none';
            if (eyeClosed) eyeClosed.style.display = 'block';
        } else {
            passInput.setAttribute('type', 'password');
            if (eyeOpen)   eyeOpen.style.display   = 'block';
            if (eyeClosed) eyeClosed.style.display = 'none';
        }
    }

    document.addEventListener('click', function (e) {
        const toggleBtn = e.target.closest('#togglePassword');
        if (toggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            togglePassword();
        }
    }, true);
})();
</script>

<div class="login-wrapper">
    <div class="login-panel">

        <!-- ============================================
             HEADER
             ============================================ -->
        <div class="brand">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1>EqualPath</h1>
        </div>

        <div class="tabs">
            <button type="button" class="tab active" data-tab="signin">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                Sign-in
            </button>

            <button type="button" class="tab" data-tab="qr">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                QR Code
            </button>
        </div>

        <!-- ============================================
             ERROR / SUCCESS MESSAGES
             ============================================ -->
        <?php if ($error): ?>
            <div class="alert alert-error" id="alertBox" style="background:#fff5f5;border:1px solid #f5c2c2;color:#d13639;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:18px;">
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= sanitize($success) ?>
            </div>
        <?php endif; ?>

        <!-- ============================================
             FORM BODY
             ============================================ -->
        <div id="signinPanel">
            <form action="authenticate.php" method="POST" id="loginForm" novalidate>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           placeholder="Enter your username"
                           autocomplete="username"
                           value="<?= htmlspecialchars($remembered_username, ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="50">
                    <span class="field-error" id="username-error"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password"
                               placeholder="Enter your password"
                               autocomplete="current-password"
                               minlength="8">
                        <button type="button" id="togglePassword" class="toggle-password" aria-label="Show password">
                            <svg id="iconEyeOpen" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="pointer-events:none;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="iconEyeClosed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;pointer-events:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    <div class="caps-warning" id="capsWarning" style="display:none;">⚠️ Caps Lock is ON</div>
                    <span class="field-error" id="password-error"></span>
                </div>

                <!-- STAY SIGNED IN -->
                <div class="checkbox-row">
                    <label class="checkbox-label" for="stay_signed_in">
                        <input type="checkbox" name="stay_signed_in" id="stay_signed_in" value="1">
                        <span class="checkmark"></span>
                        Stay signed in
                    </label>
                </div>

            </form>
        </div>

        <!-- ============================================
             QR PANEL — no submit button
             ============================================ -->
        <div id="qrPanel" style="display:none;text-align:center;margin-bottom:24px;">

            <p style="font-size:13px;color:#666;margin-bottom:16px;line-height:1.5;">
                Scan this QR code with your phone.
                <br>
                Enter your email on your phone, then tap the approval link we send you.
            </p>

            <div class="qr-box">
                <div id="qrCodeContainer">
                    <div class="qr-loading">Generating...</div>
                </div>
            </div>

            <div id="qrStatus" class="qr-status">
                <span class="pulse-dot"></span>
                Scan QR with your phone
            </div>

            <button type="button" id="refreshQR" class="qr-refresh-btn" style="margin-top:12px;">
                Generate New QR
            </button>

        </div>

        <!-- ============================================
             FOOTER — button only inside signinPanel view
             ============================================ -->
        <div class="footer-section">

            <!-- SIGN IN BUTTON -->
            <div class="submit-row" id="signInRow">
                <button type="submit" form="loginForm" class="submit-btn" id="submitBtn" aria-label="Sign In">
                    <span class="btn-label">Sign In</span>
                    <svg class="arrow-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                    <svg class="spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" opacity="0.25"/>
                        <path d="M12 2a10 10 0 019.95 9" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <!-- FOOTER LINKS -->
            <div class="footer-links">
                <a href="forgot_password.php">Can't sign in?</a>
                <a href="register.php">Create account</a>
            </div>

            <!-- LEGAL -->
            <div class="legal">
                <p>This app is protected by hCaptcha and its Privacy Policy and Terms of Service apply.</p>
                <span class="version">v1.0.0</span>
            </div>

        </div>

    </div>

    <!-- RIGHT SIDE VIDEO -->
    <div class="visual-panel">
        <video class="bg-video" autoplay muted loop playsinline preload="auto">
            <source src="../assets/equal_paths.mp4" type="video/mp4">
        </video>
        <div class="visual-overlay"></div>
    </div>

</div>

<!-- SESSION TIMER -->
<div class="session-timer" id="sessionTimer" style="display:none;">
    ⏱️ Session expires in <span id="timerValue">30:00</span>
</div>

<!-- LOADING -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Signing in...</p>
    </div>
</div>

<!-- ============================================
     CLIENT-SIDE VALIDATION + LOADING STATE
     ============================================ -->
<script>
(function () {
    'use strict';

    const form        = document.getElementById('loginForm');
    const submitBtn   = document.getElementById('submitBtn');
    const signInRow   = document.getElementById('signInRow');
    const usernameEl  = document.getElementById('username');
    const passwordEl  = document.getElementById('password');
    const userErrEl   = document.getElementById('username-error');
    const passErrEl   = document.getElementById('password-error');

    // ============================================
    // Show / hide the Sign In button based on active tab
    // ============================================
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const which = tab.dataset.tab;
            if (which === 'qr') {
                if (signInRow) signInRow.style.display = 'none';
            } else {
                if (signInRow) signInRow.style.display = 'block';
            }
        });
    });

    // ============================================
    // Reset loading state on page load (in case of back-nav)
    // ============================================
    window.addEventListener('pageshow', function () {
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    });

    // ============================================
    // Client-side validation + loading on submit
    // ============================================
    if (form) {
        form.addEventListener('submit', function (e) {
            let ok = true;

            // Username
            const username = (usernameEl?.value || '').trim();
            if (username.length < 3) {
                e.preventDefault();
                if (userErrEl) userErrEl.textContent = 'Username must be at least 3 characters';
                if (usernameEl) usernameEl.parentElement.classList.add('has-error');
                ok = false;
            } else {
                if (userErrEl) userErrEl.textContent = '';
                if (usernameEl) usernameEl.parentElement.classList.remove('has-error');
            }

            // Password
            const password = passwordEl?.value || '';
            if (password.length < 8) {
                e.preventDefault();
                if (passErrEl) passErrEl.textContent = 'Password must be at least 8 characters';
                if (passwordEl) passwordEl.parentElement.classList.add('has-error');
                ok = false;
            } else {
                if (passErrEl) passErrEl.textContent = '';
                if (passwordEl) passwordEl.parentElement.classList.remove('has-error');
            }

            // Only show loading if form is valid
            if (ok && submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }

    // ============================================
    // Clear error styling when user types
    // ============================================
    if (usernameEl) {
        usernameEl.addEventListener('input', function () {
            if (usernameEl.value.trim().length >= 3) {
                usernameEl.parentElement.classList.remove('has-error');
                if (userErrEl) userErrEl.textContent = '';
            }
        });
    }
    if (passwordEl) {
        passwordEl.addEventListener('input', function () {
            if (passwordEl.value.length >= 8) {
                passwordEl.parentElement.classList.remove('has-error');
                if (passErrEl) passErrEl.textContent = '';
            }
        });
    }
})();
</script>

<!-- JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="js/script.js?v=<?= time() . rand() ?>"></script>
<script src="js/qr-login.js?v=<?= time() . rand() ?>"></script>
<script src="js/pwa.js?v=<?= time() . rand() ?>"></script>

</body>
</html>