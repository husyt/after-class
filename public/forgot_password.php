<?php
define('REQUIRE_LOGIN', false);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/mailer.php';

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            // 1. Look up the user
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // 2. Generate token + expiry
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                                // 3. Remove any previous reset requests for this email
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$email]);

                // 4. Build INSERT dynamically based on which columns exist
                $cols = $pdo->query("DESCRIBE password_resets")->fetchAll(PDO::FETCH_COLUMN);
                $has_user_id = in_array('user_id', $cols, true);
                $has_used    = in_array('used', $cols, true);

                $fields = ['email', 'token', 'expires_at'];
                $values = [$email, $token, $expires];

                if ($has_user_id) {
                    $fields[] = 'user_id';
                    $values[] = $user['id'];
                }
                if ($has_used) {
                    $fields[] = 'used';
                    $values[] = 0;
                }

                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                $sql = "INSERT INTO password_resets (" . implode(', ', $fields) . ") 
                        VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);

                // 5. Build reset link — detect protocol + host dynamically
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host     = $_SERVER['HTTP_HOST'];
                $base     = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $reset_link = "{$protocol}://{$host}{$base}/reset_password.php?token=" . $token;

                // 6. Send email
                $sent = sendPasswordResetEmail($email, $user['username'], $reset_link);

                if ($sent) {
                    $message = 'A password reset link has been sent to <strong>' . htmlspecialchars($email) . '</strong>.';
                    $message_type = 'success';
                } else {
                    $message = 'Unable to send email. Please try again later.';
                    $message_type = 'error';
                }
            } else {
                // Security: don't reveal if the email exists
                $message = 'If that email is registered, a reset link has been sent.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            error_log("Forgot Password Error: " . $e->getMessage());
            $message = 'System error. Please try again.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | EqualPath</title>
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
            <h1>EqualPath</h1>
        </div>

        <h2 style="font-size:24px;font-weight:800;margin-bottom:8px;">Can't sign in?</h2>
        <p style="font-size:13px;color:#777;margin-bottom:24px;">Enter your email and we'll send you a reset link.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><span><?= $message ?></span></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="submit-row">
                <button type="submit" class="submit-btn" style="width:auto;padding:0 32px;border-radius:32px;">
                    <span style="font-weight:700;font-size:14px;">Send Reset Link</span>
                </button>
            </div>
        </form>

        <div class="footer-links" style="justify-content:center;">
            <a href="register.php">Don't have an account? Create one</a>
        </div>
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