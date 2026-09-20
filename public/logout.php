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

session_start();
$_SESSION['login_success'] = 'You have been logged out successfully.';
header('Location: index.php');
exit;
?>