<?php
// public/send_reminders.php
// Sends reminder emails to users who haven't played in 3+ days
// Only sends to users with game_reminders = 1 AND email_alerts = 1

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

$sent_count = 0;
$skipped_count = 0;

try {
    $stmt = $pdo->query(
        "SELECT id, username, email FROM users 
         WHERE game_reminders = 1 AND email_alerts = 1"
    );

    while ($u = $stmt->fetch()) {
        // Only send if they haven't played in 3 days
        $recent = $pdo->prepare(
            "SELECT COUNT(*) FROM game_sessions 
             WHERE user_id = ? AND played_at > NOW() - INTERVAL 3 DAY"
        );
        $recent->execute([$u['id']]);

        if ($recent->fetchColumn() == 0) {
            $ok = sendReminderEmail($u['email'], $u['username']);
            if ($ok) {
                $sent_count++;
                echo "✓ Sent to {$u['email']}\n";
            } else {
                echo "✗ Failed to send to {$u['email']}\n";
            }
        } else {
            $skipped_count++;
        }
    }

    echo "\n--- Done ---\n";
    echo "Sent:    $sent_count\n";
    echo "Skipped: $skipped_count (played recently)\n";

} catch (Throwable $e) {
    error_log("Reminder script error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}