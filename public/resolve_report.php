<?php
// public/resolve_report.php
require_once __DIR__ . '/../includes/session.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);

$report_id = (int)($input['report_id'] ?? 0);
$action    = $input['action'] ?? '';
$notes     = trim($input['notes'] ?? '');

if ($report_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid report ID']);
    exit;
}

$allowed_actions = ['in_review', 'resolved', 'dismissed', 'delete'];

if (!in_array($action, $allowed_actions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

try {
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM issue_reports WHERE id = ?");
        $stmt->execute([$report_id]);
        logActivity($pdo, $_SESSION['user_id'], "Deleted report #$report_id");
    } else {
        $stmt = $pdo->prepare(
            "UPDATE issue_reports 
             SET status = ?, 
                 admin_notes = ?,
                 resolved_by = ?,
                 resolved_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$action, $notes ?: null, $_SESSION['user_id'], $report_id]);
        logActivity($pdo, $_SESSION['user_id'], "Marked report #$report_id as $action");
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Resolve Report Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}