<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'User logged out');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
setcookie('after_class_user', '', time() - 3600, '/');
session_destroy();
// Clear remember-me cookie and token
if (!empty($_COOKIE['remember_token'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
    } catch (PDOException $e) {
        error_log("Logout token cleanup error: " . $e->getMessage());
    }

    setcookie('remember_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

session_start();
$_SESSION['login_success'] = 'You have been logged out successfully.';
header('Location: index.php');
exit;
?>