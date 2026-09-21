<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$message = '';
$message_type = '';
$valid_token = false;
$user_email = '';

if ($token) {
    $stmt = $pdo->prepare(
        "SELECT email, expires_at, used FROM password_resets 
         WHERE token = ? AND used = 0 AND expires_at > NOW()"
    );
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if ($reset) {
        $valid_token = true;
        $user_email = $reset['email'];
    } else {
        $message = 'This reset link is invalid or has expired.';
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $message_type = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt->execute([$hash, $user_email]);
            
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $_SESSION['login_success'] = 'Password reset successfully. You can now sign in.';
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            error_log("Reset Error: " . $e->getMessage());
            $message = 'Unable to reset password. Please try again.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Password | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-panel">
        <a href="index.php" class="back-link" style="font-size:13px;color:#666;text-decoration:none;">← Back to sign in</a>
        <div class="brand" style="margin-top:24px;">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1>AFTER CLASS</h1>
        </div>

        <h2 style="font-size:24px;font-weight:800;margin-bottom:8px;">Create new password</h2>
        <p style="font-size:13px;color:#777;margin-bottom:24px;">Must be at least 8 characters.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><span><?= $message ?></span></div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            <div class="submit-row">
                <button type="submit" class="submit-btn" style="width:auto;padding:0 32px;border-radius:32px;">
                    <span style="font-weight:700;font-size:14px;">Reset Password</span>
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <div class="visual-panel">
        <video class="bg-video" autoplay muted loop playsinline preload="auto">
            <source src="../assets/cyberpunk.mp4" type="video/mp4">
        </video>
        <div class="visual-overlay"></div>
    </div>
</div>
</body>
</html>