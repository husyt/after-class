<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$stay_signed_in = isset($_POST['stay_signed_in']);

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: index.php');
    exit;
}

$lockout_message = checkLockout($pdo, $username);
if ($lockout_message) {
    $_SESSION['login_error'] = $lockout_message;
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, username, email, password_hash, role, two_factor_enabled 
         FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        resetAttempts($pdo, $user['id']);
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['stay_signed_in'] = $stay_signed_in;
        
        // ============================================
        // REMEMBER LAST USERNAME (for pre-fill on next visit)
        // ============================================
        $_SESSION['remember_username'] = $user['username'];
        
        if (!$user['two_factor_enabled']) {
            $_SESSION['2fa_verified'] = true;
            logActivity($pdo, $user['id'], 'Login successful (2FA disabled)');
            
            if ($stay_signed_in) {
                setcookie('after_class_user', $user['id'], [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
            
            header('Location: dashboard.php');
            exit;
        }
        
        // Generate OTP
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 300);
        
        $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $code, $expires]);
        
        $sent = sendOTPEmail($user['email'], $user['username'], $code);
        
        if (!$sent) {
            $_SESSION['login_error'] = 'Unable to send verification code. Please try again.';
            header('Location: index.php');
            exit;
        }
        
        logActivity($pdo, $user['id'], 'Password verified, OTP sent');
        header('Location: verify_otp.php');
        exit;
        
    } else {
        if ($user) {
            recordFailedAttempt($pdo, $username);
            logActivity($pdo, $user['id'], 'Failed login attempt');
        }
        $_SESSION['login_error'] = 'Invalid username or password.';
        header('Location: index.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    $_SESSION['login_error'] = 'Unable to sign in. Please try again.';
    header('Location: index.php');
    exit;
}
?>