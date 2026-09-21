<?php
// public/toggle_favorite.php
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
$game_id = trim($input['game_id'] ?? '');

$allowed_games = ['lex-obscura', 'after-class'];

if (!in_array($game_id, $allowed_games, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid game ID']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$user_id, $game_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$user_id, $game_id]);

        logActivity($pdo, $user_id, "Removed $game_id from favorites");

        echo json_encode([
            'success' => true,
            'action'  => 'removed',
            'favorited' => false,
            'message' => 'Removed from favorites'
        ]);
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO user_favorites (user_id, game_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $game_id]);

        logActivity($pdo, $user_id, "Added $game_id to favorites");

        echo json_encode([
            'success' => true,
            'action'  => 'added',
            'favorited' => true,
            'message' => 'Added to favorites'
        ]);
    }
} catch (PDOException $e) {
    error_log("Toggle Favorite Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}