<?php
// public/update_preference.php
require_once __DIR__ . '/../includes/session.php';
require2FA();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);

$type  = $input['type'] ?? '';
$value = trim($input['value'] ?? '');

// Allowed values per preference type
$allowed = [
    'profile_picture'       => [
        'avatar-1.png', 'avatar-2.png', 'avatar-3.png', 'avatar-4.png',
        'avatar-5.png', 'avatar-6.png', 'avatar-7.png', 'avatar-8.png',
        null, // allow reset
    ],
    'preferred_background'  => ['bg-home', 'bg-city', 'bg-forest', 'bg-space', 'bg-ocean'],
    'preferred_language'    => ['en', 'tl', 'es', 'ja', 'ko'],
];

if (!isset($allowed[$type])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid preference type']);
    exit;
}

// Normalize empty value to null for profile_picture
if ($type === 'profile_picture' && $value === '') {
    $value = null;
}

if (!in_array($value, $allowed[$type], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid value']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET $type = ? WHERE id = ?");
    $stmt->execute([$value, $_SESSION['user_id']]);

    logActivity(
        $pdo,
        $_SESSION['user_id'],
        "Changed $type to " . ($value ?? 'default')
    );

    echo json_encode([
        'success' => true,
        'type'    => $type,
        'value'   => $value,
    ]);
} catch (PDOException $e) {
    error_log("Update Preference Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}