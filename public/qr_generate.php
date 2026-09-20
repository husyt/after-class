<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$pdo->exec("DELETE FROM qr_sessions WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 300);

$stmt = $pdo->prepare("INSERT INTO qr_sessions (token, expires_at) VALUES (?, ?)");
$stmt->execute([$token, $expires]);

// Auto-detect IP
function getLocalIP() {
    $host = $_SERVER['HTTP_HOST'];
    if ($host !== 'localhost' && strpos($host, '127.0.0.1') === false) {
        return 'http://' . $host;
    }
    $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($sock) {
        @socket_connect($sock, '8.8.8.8', 53);
        @socket_getsockname($sock, $local_ip);
        @socket_close($sock);
        if (!empty($local_ip)) {
            $port = $_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT'];
            return 'http://' . $local_ip . $port;
        }
    }
    return 'http://' . $host;
}

$base_url = getLocalIP() . dirname($_SERVER['PHP_SELF']);
$scan_url = $base_url . "/qr_scan.php?token=" . $token;

echo json_encode([
    'success' => true,
    'token'   => $token,
    'scan_url' => $scan_url,
    'expires_in' => 300
]);
?>