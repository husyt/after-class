<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

$timeout_duration = 1800;
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $timeout_duration) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['login_error'] = 'Session expired. Please log in again.';
    }
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 300) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

function hasPassed2FA() {
    return isset($_SESSION['2fa_verified']) && $_SESSION['2fa_verified'] === true;
}

function require2FA() {
    if (isset($_SESSION['user_id']) && !hasPassed2FA()) {
        $current = basename($_SERVER['PHP_SELF']);
        if ($current !== 'verify_otp.php' && $current !== 'logout.php') {
            header('Location: verify_otp.php');
            exit;
        }
    }
}
/**
 * Require admin role — blocks non-admins with 403
 */
function requireAdmin() {
   // Public pages can define REQUIRE_LOGIN = false before including this file
if (!defined('REQUIRE_LOGIN')) {
    define('REQUIRE_LOGIN', true);
}

if (REQUIRE_LOGIN && !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
    
    // Must have passed 2FA
    if (!hasPassed2FA()) {
        header('Location: verify_otp.php');
        exit;
    }
    
    // Must be admin
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Access Denied</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Inter', sans-serif;
                    background: #0a0a0f;
                    color: white;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 24px;
                }
                .card {
                    max-width: 480px;
                    text-align: center;
                    padding: 48px 40px;
                    background: linear-gradient(180deg, #14141a, #0a0a0f);
                    border: 1px solid rgba(255,255,255,0.08);
                    border-radius: 24px;
                    box-shadow: 0 40px 100px rgba(0,0,0,0.8);
                }
                .icon {
                    width: 72px;
                    height: 72px;
                    margin: 0 auto 24px;
                    background: rgba(209,54,57,0.15);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #d13639;
                }
                h1 { font-size: 26px; font-weight: 800; margin-bottom: 12px; }
                p { font-size: 14px; color: rgba(255,255,255,0.6); line-height: 1.6; margin-bottom: 24px; }
                .btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 12px 24px;
                    background: rgba(255,255,255,0.08);
                    color: white;
                    text-decoration: none;
                    border-radius: 999px;
                    font-size: 14px;
                    font-weight: 700;
                    transition: background 0.2s;
                }
                .btn:hover { background: rgba(255,255,255,0.15); }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                </div>
                <h1>Access Denied</h1>
                <p>You don't have permission to view this page. This area is restricted to administrators only.</p>
                <a href="dashboard.php" class="btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to Home
                </a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
?>