<?php
require_once __DIR__ . '/../includes/session.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';
    
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    
    if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = 'Username: letters, numbers, underscores only.';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    elseif ($password !== $confirm) $errors[] = 'Passwords do not match.';
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) $errors[] = 'Username or email is already registered.';
    }
    
    if (empty($errors)) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, two_factor_enabled) VALUES (?, ?, ?, 'student', 1)");
            $stmt->execute([$username, $email, $hash]);
            
            $_SESSION['login_success'] = 'Account created! You can now sign in.';
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            error_log("Register Error: " . $e->getMessage());
            $errors[] = 'Unable to create account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account | EqualPath</title>
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

        <h2 style="font-size:24px;font-weight:800;margin-bottom:8px;">Create your account</h2>
        <p style="font-size:13px;color:#777;margin-bottom:24px;">Join EqualPath and start your journey.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><span><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></span></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="50">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            <div class="submit-row">
                <button type="submit" class="submit-btn" style="width:auto;padding:0 32px;border-radius:32px;">
                    <span style="font-weight:700;font-size:14px;">Create Account</span>
                </button>
            </div>
        </form>

        <div class="footer-links" style="justify-content:center;">
            <a href="index.php">Already have an account? Sign in</a>
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