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
$value = $input['value'] ?? '';

// ============================================
// DEBUG: Log what we received
// ============================================
error_log("=== update_preference.php ===");
error_log("type: " . var_export($type, true));
error_log("value: " . var_export($value, true));

// ============================================
// ALLOWED VALUES
// ============================================
$allowed = [
    'profile_picture'      => [
        'avatar-1.png', 'avatar-2.png', 'avatar-3.png', 'avatar-4.png',
        'avatar-5.png', 'avatar-6.png', 'avatar-7.png', 'avatar-8.png',
        null, // allow reset to default
    ],
    'preferred_background' => ['bg-home', 'bg-city', 'bg-forest', 'bg-space', 'bg-ocean'],
    'preferred_language'   => ['en', 'tl', 'es', 'ja', 'ko'],
];

if (!isset($allowed[$type])) {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid preference type: ' . $type,
        'received_type' => $type
    ]);
    exit;
}

// Normalize empty profile_picture to null
if ($type === 'profile_picture' && $value === '') {
    $value = null;
}

// Validate
if (!in_array($value, $allowed[$type], true)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid value for ' . $type,
        'received_value' => $value,
        'allowed_values' => $allowed[$type]
    ]);
    exit;
}

try {
    // Update DB
    $stmt = $pdo->prepare("UPDATE users SET `$type` = ? WHERE id = ?");
    $stmt->execute([$value, $_SESSION['user_id']]);

    // Verify it actually saved
    $verify = $pdo->prepare("SELECT `$type` FROM users WHERE id = ?");
    $verify->execute([$_SESSION['user_id']]);
    $saved = $verify->fetchColumn();

    error_log("Saved value in DB: " . var_export($saved, true));

    // ============================================
    // CRITICAL: Update SESSION immediately
    // ============================================
    if ($type === 'preferred_language') {
        $_SESSION['preferred_language'] = $value;
        error_log("Session language updated to: " . $_SESSION['preferred_language']);
    }
    if ($type === 'preferred_background') {
        $_SESSION['preferred_background'] = $value;
    }
    if ($type === 'profile_picture') {
        $_SESSION['profile_picture'] = $value;
    }

    logActivity($pdo, $_SESSION['user_id'], "Changed $type to " . ($value ?? 'default'));

    echo json_encode([
        'success'       => true,
        'type'          => $type,
        'value'         => $value,
        'saved_in_db'   => $saved,
        'session_value' => $_SESSION[$type] ?? null,
    ]);
} catch (PDOException $e) {
    error_log("Update Preference Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage()
    ]);
}