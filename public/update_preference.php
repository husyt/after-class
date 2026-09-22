<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);
$type  = $input['type'] ?? '';
$value = trim($input['value'] ?? '');

$allowed = [
    'profile_picture'      => ['avatar-1.png','avatar-2.png','avatar-3.png','avatar-4.png',
                               'avatar-5.png','avatar-6.png','avatar-7.png','avatar-8.png', null],
    'preferred_background' => ['bg-home','bg-city','bg-forest','bg-space','bg-ocean'],
    'preferred_language'   => ['en','tl','es','ja','ko'],
    'reduce_motion'         => [0, 1, '0', '1'],
    'auto_play'             => [0, 1, '0', '1'],
    'bg_music'              => [0, 1, '0', '1'],
    'email_alerts'          => [0, 1, '0', '1'],
    'game_reminders'        => [0, 1, '0', '1'],
    'master_volume'         => range(0, 100),
];

if (!isset($allowed[$type])) {
    echo json_encode(['success' => false, 'error' => 'Invalid type']);
    exit;
}
if ($type === 'profile_picture' && $value === '') $value = null;
if (!in_array($value, $allowed[$type], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid value: ' . $value]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET `$type` = ? WHERE id = ?");
    $stmt->execute([$value, $_SESSION['user_id']]);

    // Update session immediately
    $_SESSION[$type] = $value;

    logActivity($pdo, $_SESSION['user_id'], "Changed $type to " . ($value ?? 'default'));

    echo json_encode(['success' => true, 'type' => $type, 'value' => $value]);
} catch (PDOException $e) {
    error_log("Update Preference Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}