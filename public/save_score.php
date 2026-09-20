<?php
// public/save_score.php
// Save game score with retry logic (§24 Game Failure Handling)
require_once __DIR__ . '/../includes/session.php';
require2FA();

header('Content-Type: application/json');

// ============================================
// AUTH CHECK
// ============================================
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Not authenticated',
        'code'    => 'AUTH_REQUIRED',
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================================
// PARSE INPUT
// ============================================
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid JSON payload',
        'code'    => 'INVALID_JSON',
    ]);
    exit;
}

$game_id   = trim($input['game_id'] ?? '');
$score     = max(0, (int)($input['score'] ?? 0));
$duration  = max(0, (int)($input['duration'] ?? 0));
$level     = max(1, (int)($input['level'] ?? 1));
$completed = isset($input['completed']) ? (int)(bool)$input['completed'] : 0;

// ============================================
// VALIDATE INPUT
// ============================================
$allowed_games = ['lex-obscura', 'after-class'];

if (!in_array($game_id, $allowed_games, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Unknown game ID',
        'code'    => 'INVALID_GAME',
    ]);
    exit;
}

if ($score > 10000000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Score exceeds maximum allowed',
        'code'    => 'SCORE_TOO_HIGH',
    ]);
    exit;
}

if ($duration > 86400) {
    $duration = 86400;
}

$user_id = (int)$_SESSION['user_id'];

// ============================================
// SAVE SCORE WITH RETRY
// ============================================
$max_attempts = 3;
$attempt = 0;
$last_error = null;
$success = false;
$xp_earned = 0;
$new_level = 1;

while ($attempt < $max_attempts && !$success) {
    $attempt++;

    try {
        $pdo->beginTransaction();
        // TEMPORARY: simulate DB failure
if (!isset($_SESSION['retry_test'])) {
    $_SESSION['retry_test'] = true;
    throw new PDOException('Simulated DB failure for testing');
}
unset($_SESSION['retry_test']);

        // 1. Insert game session
        $stmt = $pdo->prepare(
            "INSERT INTO game_sessions 
                (user_id, game_id, score, duration_seconds, level_reached, completed)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $game_id, $score, $duration, $level, $completed]);

        // 2. Award XP (10% of score, min 5 XP)
        $xp_earned = max(5, (int)($score * 0.1));

        $stmt = $pdo->prepare(
            "UPDATE users 
             SET xp = xp + ?,
                 games_played = games_played + 1,
                 high_score = GREATEST(high_score, ?)
             WHERE id = ?"
        );
        $stmt->execute([$xp_earned, $score, $user_id]);

        // 3. Recalculate level (1000 XP per level)
        $stmt = $pdo->prepare("SELECT xp FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $xp_total = (int)$stmt->fetchColumn();
        $new_level = max(1, (int)floor($xp_total / 1000) + 1);

        $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
        $stmt->execute([$new_level, $user_id]);

        // 4. Log activity
        logActivity(
            $pdo,
            $user_id,
            "Played $game_id — scored $score, earned $xp_earned XP"
        );

        // 5. Commit
        $pdo->commit();
        $success = true;

        if ($attempt > 1) {
            error_log("[save_score] Succeeded on attempt $attempt for user $user_id");
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $last_error = $e->getMessage();
        error_log("[save_score] Attempt $attempt failed: " . $last_error);

        if ($attempt < $max_attempts) {
            usleep(100000 * $attempt); // 100ms, 200ms
            continue;
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $last_error = $e->getMessage();
        error_log("[save_score] Non-DB error on attempt $attempt: " . $last_error);
        break;
    }
}

// ============================================
// RESPONSE
// ============================================
if ($success) {
    echo json_encode([
        'success'    => true,
        'score'      => $score,
        'xp_earned'  => $xp_earned,
        'new_level'  => $new_level,
        'attempts'   => $attempt,
        'message'    => 'Score saved successfully',
    ]);
    exit;
}

http_response_code(503);
echo json_encode([
    'success'  => false,
    'error'    => 'Unable to save score. The system is temporarily unavailable.',
    'code'     => 'SAVE_FAILED',
    'attempts' => $attempt,
    'retry'    => true,
]);

error_log("[save_score] All $max_attempts attempts failed for user $user_id. Last error: $last_error");
exit;
?>