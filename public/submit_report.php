<?php
// public/submit_report.php
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

if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$subject     = trim($input['subject'] ?? '');
$description = trim($input['description'] ?? '');
$severity    = $input['severity'] ?? 'medium';
$game_id     = trim($input['game_id'] ?? '');

if (strlen($subject) < 5 || strlen($subject) > 150) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Subject must be 5-150 characters']);
    exit;
}

if (strlen($description) < 10 || strlen($description) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Description must be 10-2000 characters']);
    exit;
}

$allowed_severity = ['low', 'medium', 'high', 'critical'];
if (!in_array($severity, $allowed_severity, true)) {
    $severity = 'medium';
}

if ($game_id !== '' && !in_array($game_id, ['lex-obscura', 'after-class'], true)) {
    $game_id = null;
} elseif ($game_id === '') {
    $game_id = null;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO issue_reports (user_id, game_id, subject, description, severity)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $_SESSION['user_id'],
        $game_id,
        $subject,
        $description,
        $severity
    ]);

    $report_id = $pdo->lastInsertId();

    logActivity($pdo, $_SESSION['user_id'], "Submitted issue report #$report_id: $subject");

    echo json_encode([
        'success' => true,
        'report_id' => (int)$report_id,
        'message' => 'Report submitted successfully'
    ]);
} catch (PDOException $e) {
    error_log("Submit Report Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}