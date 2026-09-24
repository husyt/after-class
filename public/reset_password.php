<?php
define('REQUIRE_LOGIN', false);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$message = '';
$message_type = '';
$valid_token = false;
$reset_row = null;

// ============================================
// VALIDATE TOKEN
// ============================================
if ($token) {
    try {
        // Check which columns exist in the table
        $cols = $pdo->query("DESCRIBE password_resets")->fetchAll(PDO::FETCH_COLUMN);
        $has_used    = in_array('used', $cols, true);
        $has_expires = in_array('expires_at', $cols, true);
        $has_user_id = in_array('user_id', $cols, true);

        // Build SELECT dynamically
        $select_fields = ['id', 'email', 'token'];
        if ($has_user_id) $select_fields[] = 'user_id';
        if ($has_expires) $select_fields[] = 'expires_at';
        if ($has_used)    $select_fields[] = 'used';

        $sql = "SELECT " . implode(', ', $select_fields) . " 
                FROM password_resets 
                WHERE token = ? 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            $message = 'This reset link is invalid. Please request a new one.';
            $message_type = 'error';
        } elseif ($has_used && (int)$reset['used'] === 1) {
            $message = 'This reset link has already been used. Please request a new one.';
            $message_type = 'error';
        } elseif ($has_expires && strtotime($reset['expires_at']) < time()) {
            $message = 'This reset link has expired. Please request a new one.';
            $message_type = 'error';
        } else {
            $valid_token = true;
            $reset_row   = $reset;
        }
    } catch (PDOException $e) {
        error_log("Reset Validate Error: " . $e->getMessage());
        $message = 'System error. Please try again.';
        $message_type = 'error';
    }
} else {
    $message = 'No reset token provided.';
    $message_type = 'error';
}

// ============================================
// HANDLE PASSWORD UPDATE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token && $reset_row) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $message_type = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Check if password_changed_at column exists
            $user_cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
            $has_pw_changed = in_array('password_changed_at', $user_cols, true);

            // Build UPDATE dynamically
            if ($has_pw_changed) {
                $update_sql = "UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE ";
            } else {
                $update_sql = "UPDATE users SET password_hash = ? WHERE ";
            }

            // Update password — prefer user_id, fall back to email
            if (!empty($reset_row['user_id'])) {
                $stmt = $pdo->prepare($update_sql . "id = ?");
                $stmt->execute([$hash, $reset_row['user_id']]);
            } else {
                $stmt = $pdo->prepare($update_sql . "email = ?");
                $stmt->execute([$hash, $reset_row['email']]);
            }

            // Mark token as used (only if column exists)
            if ($has_used) {
                $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $stmt->execute([$reset_row['id']]);
            } else {
                // If no 'used' column, just delete the row
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE id = ?");
                $stmt->execute([$reset_row['id']]);
            }

            // Clear any pending OTP codes
            if (!empty($reset_row['user_id'])) {
                $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ?");
                $stmt->execute([$reset_row['user_id']]);
            }

            $_SESSION['login_success'] = 'Password reset successfully. You can now sign in.';
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            error_log("Reset Password Error: " . $e->getMessage());
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
            <div class="alert alert-<?= $message_type ?>"><span><?= htmlspecialchars($message) ?></span></div>
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
            <source src="../assets/equal_paths.mp4" type="video/mp4">
        </video>
        <div class="visual-overlay"></div>
    </div>
</div>
</body>
</html>