<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {

    // Remove expired QR sessions
    $pdo->exec("
        DELETE FROM qr_sessions
        WHERE expires_at < NOW()
    ");

    // Secure random QR token
    $token = bin2hex(random_bytes(32));


    // ==========================================
    // CREATE QR SESSION
    // Let MySQL handle the expiration time
    // ==========================================

    $stmt = $pdo->prepare("
        INSERT INTO qr_sessions
            (token, status, expires_at)
        VALUES
            (?, 'pending', DATE_ADD(NOW(), INTERVAL 5 MINUTE))
    ");

    $stmt->execute([$token]);


    // ==========================================
    // AUTOMATIC LOCAL IP DETECTION
    // ==========================================

    function getLocalIP(): string
    {
        /*
         * If the website was opened using:
         * http://192.168.x.x/...
         *
         * use that address directly.
         */

        $host = $_SERVER['HTTP_HOST'] ?? '';

        // Remove port if present
        $hostWithoutPort = explode(':', $host)[0];

        if (
            $hostWithoutPort !== '' &&
            $hostWithoutPort !== 'localhost' &&
            $hostWithoutPort !== '127.0.0.1'
        ) {
            return $hostWithoutPort;
        }


        /*
         * Detect the IP Windows uses for
         * the current network connection.
         */

        if (
            function_exists('socket_create') &&
            function_exists('socket_connect') &&
            function_exists('socket_getsockname')
        ) {

            $socket = @socket_create(
                AF_INET,
                SOCK_DGRAM,
                SOL_UDP
            );

            if ($socket !== false) {

                @socket_connect(
                    $socket,
                    '8.8.8.8',
                    53
                );

                $localIP = '';

                @socket_getsockname(
                    $socket,
                    $localIP
                );

                @socket_close($socket);

                if (
                    !empty($localIP) &&
                    filter_var(
                        $localIP,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_IPV4
                    ) &&
                    $localIP !== '127.0.0.1'
                ) {
                    return $localIP;
                }
            }
        }


        /*
         * Windows/XAMPP fallback
         */

        $hostname = gethostname();

        if ($hostname !== false) {

            $ip = gethostbyname($hostname);

            if (
                filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4
                ) &&
                $ip !== '127.0.0.1'
            ) {
                return $ip;
            }
        }


        return '127.0.0.1';
    }


    // ==========================================
    // BUILD PHONE SCAN URL
    // ==========================================

    $localIP = getLocalIP();

    $port = (int) ($_SERVER['SERVER_PORT'] ?? 80);

    $portPart = ($port === 80)
        ? ''
        : ':' . $port;


    $directory = rtrim(
        dirname($_SERVER['PHP_SELF']),
        '/'
    );


    $baseURL =
        'http://' .
        $localIP .
        $portPart .
        $directory;


    $scanURL =
        $baseURL .
        '/qr_scan.php?token=' .
        urlencode($token);


    // ==========================================
    // RETURN JSON TO qr-login.js
    // ==========================================

    echo json_encode([
        'success'    => true,
        'token'      => $token,
        'scan_url'   => $scanURL,
        'local_ip'   => $localIP,
        'expires_in' => 300
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}