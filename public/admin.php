<?php
require_once __DIR__ . '/../includes/session.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================================
// FETCH CURRENT USER FIRST (critical for i18n)
// ============================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// ============================================
// LOAD TRANSLATION SYSTEM
// ============================================
require_once __DIR__ . '/../includes/i18n.php';

// ============================================
// LOAD AVATAR HELPER
// ============================================
require_once __DIR__ . '/../includes/avatar.php';

// ============================================
// SYSTEM STATISTICS
// ============================================
$stats = [];
$stats['total_users']    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['total_games']    = (int)$pdo->query("SELECT COUNT(*) FROM game_sessions")->fetchColumn();
$stats['total_sessions'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$stats['active_today']   = (int)$pdo->query(
    "SELECT COUNT(DISTINCT user_id) FROM activity_logs 
     WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

// ============================================
// GAME ANALYTICS (KILLER FEATURE)
// ============================================
$game_analytics = [];

// 1. Per-game aggregated stats
$stmt = $pdo->query(
    "SELECT 
        game_id,
        COUNT(*)                        AS total_plays,
        COUNT(DISTINCT user_id)         AS unique_players,
        COALESCE(AVG(score), 0)         AS avg_score,
        COALESCE(MAX(score), 0)         AS high_score,
        COALESCE(SUM(score), 0)         AS total_score,
        COALESCE(SUM(duration_seconds), 0) AS total_seconds,
        COALESCE(AVG(duration_seconds), 0) AS avg_seconds,
        MAX(played_at)                  AS last_played
     FROM game_sessions
     GROUP BY game_id
     ORDER BY total_plays DESC"
);
$rows = $stmt->fetchAll();

foreach ($rows as $r) {
    $total = (int)$r['total_plays'];
    $avg   = (int)round($r['avg_score']);
    $high  = (int)$r['high_score'];
    $secs  = (int)$r['total_seconds'];
    $avgSecs = (int)round($r['avg_seconds']);

    // Rough completion rate: % of sessions with score > 50% of high score
    $threshold = max(1, (int)($high / 2));
    $stmt2 = $pdo->prepare(
        "SELECT COUNT(*) FROM game_sessions 
         WHERE game_id = ? AND score >= ?"
    );
    $stmt2->execute([$r['game_id'], $threshold]);
    $completed = (int)$stmt2->fetchColumn();
    $completion = $total > 0 ? round(($completed / $total) * 100) : 0;

    $game_analytics[] = [
        'game_id'        => $r['game_id'],
        'title'          => strtoupper(str_replace('-', ' ', $r['game_id'])),
        'total_plays'    => $total,
        'unique_players' => (int)$r['unique_players'],
        'avg_score'      => $avg,
        'high_score'     => $high,
        'total_score'    => (int)$r['total_score'],
        'total_seconds'  => $secs,
        'avg_seconds'    => $avgSecs,
        'last_played'    => $r['last_played'],
        'completion'     => $completion,
    ];
}

// 2. Overall game stats
$overall = [
    'total_plays'    => 0,
    'unique_players' => 0,
    'total_time'     => 0,
    'avg_score_sum'  => 0,
    'high_score'     => 0,
    'avg_seconds'    => 0,
];

$stmt = $pdo->query(
    "SELECT 
        COUNT(*)                        AS total_plays,
        COUNT(DISTINCT user_id)         AS unique_players,
        COALESCE(SUM(duration_seconds),0) AS total_time,
        COALESCE(AVG(score),0)          AS avg_score,
        COALESCE(MAX(score),0)          AS high_score,
        COALESCE(AVG(duration_seconds),0) AS avg_seconds
     FROM game_sessions"
);
$overall_row = $stmt->fetch();
$overall['total_plays']    = (int)$overall_row['total_plays'];
$overall['unique_players'] = (int)$overall_row['unique_players'];
$overall['total_time']     = (int)$overall_row['total_time'];
$overall['avg_score']      = (int)round($overall_row['avg_score']);
$overall['high_score']     = (int)$overall_row['high_score'];
$overall['avg_seconds']    = (int)round($overall_row['avg_seconds']);

// 3. Plays per day (last 30 days) for the chart
$stmt = $pdo->query(
    "SELECT 
        DATE(played_at) AS day,
        COUNT(*) AS plays,
        COUNT(DISTINCT user_id) AS players
     FROM game_sessions
     WHERE played_at >= NOW() - INTERVAL 30 DAY
     GROUP BY DATE(played_at)
     ORDER BY day ASC"
);
$plays_per_day = $stmt->fetchAll();

// Fill in missing days with zero so the chart has 30 bars
$chart_data = [];
$chart_labels = [];
$today = new DateTime();
for ($i = 29; $i >= 0; $i--) {
    $d = clone $today;
    $d->modify("-$i days");
    $key = $d->format('Y-m-d');
    $chart_labels[] = $d->format('M j');
    $found = false;
    foreach ($plays_per_day as $row) {
        if ($row['day'] === $key) {
            $chart_data[] = (int)$row['plays'];
            $found = true;
            break;
        }
    }
    if (!$found) $chart_data[] = 0;
}

// 4. Top 5 players this week
$stmt = $pdo->query(
    "SELECT 
        u.id, u.username, u.profile_picture,
        COUNT(gs.id) AS sessions,
        COALESCE(SUM(gs.score), 0) AS total_score,
        COALESCE(MAX(gs.score), 0) AS high_score
     FROM users u
     JOIN game_sessions gs ON gs.user_id = u.id
     WHERE gs.played_at >= NOW() - INTERVAL 7 DAY
     GROUP BY u.id
     ORDER BY total_score DESC, sessions DESC
     LIMIT 5"
);
$top_players_week = $stmt->fetchAll();

// 5. Peak playtimes heatmap data
$stmt = $pdo->query(
    "SELECT 
        DAYOFWEEK(played_at) AS dow,
        HOUR(played_at)      AS hour,
        COUNT(*)             AS plays
     FROM game_sessions
     WHERE played_at >= NOW() - INTERVAL 30 DAY
     GROUP BY DAYOFWEEK(played_at), HOUR(played_at)"
);
$heatmap_raw = $stmt->fetchAll();

// Build 7x24 grid
$heatmap = [];
for ($d = 1; $d <= 7; $d++) {
    for ($h = 0; $h < 24; $h++) {
        $heatmap[$d][$h] = 0;
    }
}
$heatmap_max = 0;
foreach ($heatmap_raw as $r) {
    $heatmap[(int)$r['dow']][(int)$r['hour']] = (int)$r['plays'];
    if ((int)$r['plays'] > $heatmap_max) $heatmap_max = (int)$r['plays'];
}

// 6. Most recent 10 sessions feed
$stmt = $pdo->query(
    "SELECT 
        gs.id, gs.game_id, gs.score, gs.duration_seconds, gs.played_at,
        u.username, u.profile_picture
     FROM game_sessions gs
     JOIN users u ON u.id = gs.user_id
     ORDER BY gs.played_at DESC
     LIMIT 10"
);
$recent_sessions = $stmt->fetchAll();

// ============================================
// HANDLE POST ACTIONS
// ============================================
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'delete_user') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        if ($target_id === (int)$_SESSION['user_id']) {
            $message = 'You cannot delete your own account.';
            $message_type = 'error';
        } elseif ($target_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$target_id]);
            logActivity($pdo, $_SESSION['user_id'], "Deleted user ID $target_id");
            $message = 'User deleted successfully.';
            $message_type = 'success';
        }
    }

    if ($_POST['action'] === 'change_role') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $new_role = $_POST['new_role'] ?? '';
        $allowed_roles = ['admin', 'student'];

        if (!in_array($new_role, $allowed_roles, true)) {
            $message = 'Invalid role.';
            $message_type = 'error';
        } elseif ($target_id === (int)$_SESSION['user_id']) {
            $message = 'You cannot change your own role.';
            $message_type = 'error';
        } elseif ($target_id > 0) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_id]);
            logActivity($pdo, $_SESSION['user_id'], "Changed user $target_id role to $new_role");
            $message = 'Role updated successfully.';
            $message_type = 'success';
        }
    }

    if ($_POST['action'] === 'toggle_2fa') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $enable = isset($_POST['enable']) ? (int)(bool)$_POST['enable'] : 0;

        if ($target_id === (int)$_SESSION['user_id']) {
            $message = 'You cannot change your own 2FA setting.';
            $message_type = 'error';
        } elseif ($target_id > 0) {
            $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?");
            $stmt->execute([$enable, $target_id]);
            logActivity(
                $pdo,
                $_SESSION['user_id'],
                ($enable ? 'Enabled' : 'Disabled') . " 2FA for user ID $target_id"
            );
            $message = $enable ? '2FA enabled for user.' : '2FA disabled for user.';
            $message_type = 'success';
        }
    }
}

// ============================================
// FETCH DATA (users, activities, reports)
// ============================================
$stmt = $pdo->query(
    "SELECT id, username, email, role, level, xp, high_score, games_played, 
            last_login, created_at, two_factor_enabled 
     FROM users ORDER BY created_at DESC"
);
$users = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT a.id, a.activity, a.ip_address, a.created_at, u.username 
     FROM activity_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC LIMIT 50"
);
$activities = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT r.*, 
            u.username as reporter_name, 
            u.email as reporter_email
     FROM issue_reports r
     LEFT JOIN users u ON u.id = r.user_id
     ORDER BY 
        CASE r.status 
            WHEN 'open' THEN 1 
            WHEN 'in_review' THEN 2 
            WHEN 'resolved' THEN 3 
            WHEN 'dismissed' THEN 4 
        END,
        CASE r.severity 
            WHEN 'critical' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
        END,
        r.created_at DESC"
);
$issue_reports = $stmt->fetchAll();

$open_reports_count = 0;
foreach ($issue_reports as $r) {
    if ($r['status'] === 'open') $open_reports_count++;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('admin_panel') ?> | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="manifest" href="/after-class/public/manifest.json">
<meta name="theme-color" content="#d13639">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="EqualPath">
<link rel="apple-touch-icon" href="/after-class/assets/icons/icon-192.png">
<link rel="icon" type="image/png" href="/after-class/assets/icons/icon-192.png">
    <style>
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: linear-gradient(135deg, #d13639, #7c3aed);
            color: white;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .admin-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            padding: 6px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            width: fit-content;
            flex-wrap: wrap;
        }

        .admin-tab {
            padding: 10px 22px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.6);
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .admin-tab:hover { color: white; }
        .admin-tab.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .admin-panel { display: none; }
        .admin-panel.active { display: block; }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .admin-stat {
            padding: 20px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
        }
        .admin-stat-label {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .admin-stat-value {
            font-size: 32px;
            font-weight: 800;
            color: white;
        }

        .admin-table-wrap {
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .admin-table thead { background: rgba(255,255,255,0.04); }
        .admin-table th {
            text-align: left;
            padding: 14px 20px;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            white-space: nowrap;
        }
        .admin-table td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.85);
        }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover { background: rgba(255,255,255,0.02); }

        .role-select {
            padding: 6px 10px;
            background: #1a1a25;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: white;
            font-family: inherit;
            font-size: 12px;
            cursor: pointer;
        }

        .admin-action-btn {
            padding: 6px 12px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            color: rgba(255,255,255,0.75);
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .admin-action-btn:hover { background: rgba(255,255,255,0.12); color: white; }
        .admin-action-btn.danger { color: #ff7c7f; border-color: rgba(209,54,57,0.3); }
        .admin-action-btn.danger:hover { background: rgba(209,54,57,0.15); }

        .tfa-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 66px;
            padding: 6px 12px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tfa-toggle.on {
            background: rgba(46, 204, 113, 0.15);
            border-color: rgba(46, 204, 113, 0.4);
            color: #2ecc71;
        }
        .tfa-toggle.on:hover { background: rgba(46, 204, 113, 0.25); }
        .tfa-toggle.off {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
        }
        .tfa-toggle.off:hover { background: rgba(255, 255, 255, 0.1); color: white; }

        .tfa-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 66px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            border-radius: 999px;
        }
        .tfa-badge.on { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .tfa-badge.off { background: rgba(255, 255, 255, 0.05); color: rgba(255, 255, 255, 0.4); }

        .admin-activity { display: flex; flex-direction: column; gap: 2px; }
        .admin-activity-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 20px;
            background: rgba(20, 20, 30, 0.4);
            border-left: 2px solid transparent;
            transition: all 0.2s;
        }
        .admin-activity-row:hover { background: rgba(20, 20, 30, 0.7); border-left-color: #d13639; }
        .admin-activity-user { font-weight: 700; color: #7c3aed; min-width: 120px; }
        .admin-activity-text { flex: 1; color: rgba(255,255,255,0.85); }
        .admin-activity-time { font-size: 11px; color: rgba(255,255,255,0.4); font-family: monospace; }

        .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            margin-left: 6px;
            background: #d13639;
            color: white;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
        }

        .issue-reports-list { display: flex; flex-direction: column; gap: 16px; }
        .issue-report-card {
            padding: 20px 24px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-left: 4px solid #3498db;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .issue-report-card:hover { background: rgba(20, 20, 30, 0.8); }
        .issue-report-card.status-resolved { border-left-color: #2ecc71; opacity: 0.7; }
        .issue-report-card.status-dismissed { border-left-color: #666; opacity: 0.5; }
        .issue-report-card.status-in_review { border-left-color: #f39c12; }

        .issue-report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .issue-report-left { display: flex; gap: 8px; flex-wrap: wrap; }
        .issue-status, .issue-severity, .issue-game {
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .issue-status.status-open { background: rgba(52,152,219,0.2); color: #3498db; }
        .issue-status.status-in_review { background: rgba(243,156,18,0.2); color: #f39c12; }
        .issue-status.status-resolved { background: rgba(46,204,113,0.2); color: #2ecc71; }
        .issue-status.status-dismissed { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.5); }
        .issue-severity.severity-low { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .issue-severity.severity-medium { background: rgba(243,156,18,0.15); color: #f39c12; }
        .issue-severity.severity-high { background: rgba(209,54,57,0.2); color: #ff7c7f; }
        .issue-severity.severity-critical { background: #d13639; color: white; }
        .issue-game { background: rgba(124,58,237,0.2); color: #a78bfa; }
        .issue-report-meta { font-size: 11px; color: rgba(255,255,255,0.4); font-family: monospace; }
        .issue-subject { font-size: 16px; font-weight: 800; color: white; margin-bottom: 10px; }
        .issue-description {
            font-size: 13px;
            color: rgba(255,255,255,0.75);
            line-height: 1.6;
            margin-bottom: 12px;
            padding: 12px 16px;
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
        }
        .issue-reporter { font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 12px; }
        .issue-reporter strong { color: rgba(255,255,255,0.85); }
        .issue-admin-notes {
            padding: 12px 16px;
            background: rgba(46,204,113,0.08);
            border-left: 3px solid #2ecc71;
            border-radius: 6px;
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 12px;
            line-height: 1.6;
        }
        .issue-admin-notes strong { color: #2ecc71; display: block; margin-bottom: 4px; }
        .issue-report-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        /* ============================================
           GAME ANALYTICS PANEL
           ============================================ */
        .analytics-section {
            margin-bottom: 32px;
        }
        .analytics-section-title {
            font-size: 11px;
            font-weight: 800;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        /* Game cards */
        .game-card {
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            transition: all 0.2s;
        }
        .game-card:hover {
            background: rgba(20, 20, 30, 0.8);
            border-color: rgba(255, 255, 255, 0.12);
        }
        .game-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .game-card-title {
            font-size: 20px;
            font-weight: 900;
            color: white;
            letter-spacing: 1px;
        }
        .game-health {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .game-health.healthy { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .game-health.warning { background: rgba(243,156,18,0.15); color: #f39c12; }
        .game-health.dead    { background: rgba(209,54,57,0.15); color: #ff7c7f; }

        /* Bar visualization */
        .game-bar-track {
            height: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .game-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #d13639, #f97316);
            transition: width 0.6s ease;
        }

        /* Game stat grid */
        .game-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 16px;
        }
        .game-stat-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .game-stat-label {
            font-size: 9px;
            font-weight: 700;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }
        .game-stat-value {
            font-size: 18px;
            font-weight: 800;
            color: white;
        }
        .game-stat-value.green { color: #2ecc71; }
        .game-stat-value.gold  { color: #f39c12; }
        .game-stat-value.purple{ color: #a78bfa; }

        /* Chart */
        .plays-chart {
            display: flex;
            align-items: flex-end;
            gap: 3px;
            height: 120px;
            padding: 12px 0;
            margin-bottom: 8px;
        }
        .plays-chart .bar {
            flex: 1;
            background: linear-gradient(180deg, #d13639, #7c3aed);
            border-radius: 4px 4px 0 0;
            min-height: 3px;
            position: relative;
            transition: all 0.2s;
            cursor: pointer;
        }
        .plays-chart .bar:hover {
            background: linear-gradient(180deg, #f97316, #d13639);
            transform: scaleY(1.05);
        }
        .plays-chart .bar::after {
            content: attr(data-label);
            position: absolute;
            bottom: -18px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 9px;
            color: rgba(255,255,255,0.3);
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .plays-chart .bar:hover::after { opacity: 1; }

        .chart-legend {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            margin-top: 8px;
        }

        /* Top players */
        .top-players-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .top-player-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            transition: all 0.2s;
        }
        .top-player-row:hover {
            background: rgba(20, 20, 30, 0.85);
            border-color: rgba(255,255,255,0.15);
        }
        .top-player-row.rank-1 { border-color: rgba(255,215,0,0.4); background: linear-gradient(90deg, rgba(255,215,0,0.08), rgba(20,20,30,0.6)); }
        .top-player-row.rank-2 { border-color: rgba(192,192,192,0.3); }
        .top-player-row.rank-3 { border-color: rgba(205,127,50,0.3); }

        .top-player-rank {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 14px;
            background: rgba(255,255,255,0.06);
            color: white;
            flex-shrink: 0;
        }
        .top-player-row.rank-1 .top-player-rank { background: #ffd700; color: #1a1a00; }
        .top-player-row.rank-2 .top-player-rank { background: #c0c0c0; color: #1a1a1a; }
        .top-player-row.rank-3 .top-player-rank { background: #cd7f32; color: #1a0f00; }

        .top-player-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #d13639);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
            overflow: hidden;
        }
        .top-player-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .top-player-info { flex: 1; min-width: 0; }
        .top-player-name { font-size: 14px; font-weight: 700; color: white; margin-bottom: 2px; }
        .top-player-meta { font-size: 11px; color: rgba(255,255,255,0.5); }
        .top-player-score { font-size: 18px; font-weight: 800; color: #2ecc71; font-family: monospace; text-align: right; }
        .top-player-score-label { font-size: 9px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1.2px; text-align: right; }

        /* Heatmap */
        .heatmap-wrap {
            overflow-x: auto;
            padding-bottom: 8px;
        }
        .heatmap {
            display: grid;
            grid-template-columns: 40px repeat(24, 1fr);
            gap: 3px;
            min-width: 700px;
        }
        .heatmap-cell {
            aspect-ratio: 1;
            border-radius: 4px;
            background: rgba(255,255,255,0.03);
            transition: all 0.15s;
            cursor: pointer;
        }
        .heatmap-cell:hover {
            outline: 2px solid rgba(255,255,255,0.4);
            outline-offset: 1px;
        }
        .heatmap-label {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 700;
            color: rgba(255,255,255,0.35);
            text-transform: uppercase;
        }
        .heatmap-label.hour {
            font-size: 8px;
            writing-mode: horizontal-tb;
        }

        /* Recent sessions */
        .sessions-feed {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .session-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            background: rgba(20, 20, 30, 0.4);
            border-left: 3px solid #7c3aed;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .session-row:hover { background: rgba(20, 20, 30, 0.7); border-left-color: #d13639; }
        .session-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #d13639);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 800; font-size: 12px;
            flex-shrink: 0; overflow: hidden;
        }
        .session-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .session-info { flex: 1; min-width: 0; }
        .session-user { font-size: 13px; font-weight: 700; color: white; }
        .session-game { font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; }
        .session-meta { font-size: 11px; color: rgba(255,255,255,0.5); text-align: right; flex-shrink: 0; }
        .session-score { font-size: 14px; font-weight: 800; color: #2ecc71; font-family: monospace; }
        .session-time { font-size: 10px; color: rgba(255,255,255,0.35); }

        /* Two column layout for smaller sections */
        .analytics-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 900px) {
            .analytics-grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="profile-page" data-bg="<?= htmlspecialchars($user['preferred_background'] ?? 'bg-home') ?>">

<header class="topnav">
    <div class="nav-left">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab" title="<?= __('home') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
            </a>
            <a href="library.php" class="nav-tab" title="<?= __('library') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </a>
            <a href="leaderboard.php" class="nav-tab" title="<?= __('leaderboard') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"/></svg>
            </a>
            <a href="admin.php" class="nav-tab active" title="<?= __('admin_panel') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/></svg>
            </a>
            <a href="profile.php" class="nav-tab" title="<?= __('profile') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
            </a>
        </nav>
    </div>
    <div class="nav-right">
        <button class="icon-btn" aria-label="<?= __('settings') ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        </button>
        
        <div class="user-avatar">
            <?php render_nav_avatar($user['profile_picture'] ?? ''); ?>
        </div>

        <a href="logout.php" class="icon-btn" aria-label="<?= __('sign_out') ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
        </a>
    </div>
</header>

<main class="dashboard">

    <div class="section-head">
        <div class="section-head-left">
            <a href="dashboard.php" class="back-link-small" title="<?= __('back_to_home') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <?= __('back') ?>
            </a>
            <h1><?= __('admin_panel') ?></h1>
            <span class="admin-badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/></svg>
                <?= __('admin_only') ?>
            </span>
        </div>
        <span class="user-greeting"><?= __('signed_in_as') ?> <?= htmlspecialchars($user['username']) ?></span>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>" style="margin-bottom:20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="admin-tabs">
        <button class="admin-tab active" data-panel="overview"><?= __('overview') ?></button>
        <button class="admin-tab" data-panel="games">🎮 Games</button>
        <button class="admin-tab" data-panel="users"><?= __('users') ?></button>
        <button class="admin-tab" data-panel="activity"><?= __('activity_logs') ?></button>
        <button class="admin-tab" data-panel="issues">
            <?= __('issue_reports') ?>
            <?php if ($open_reports_count > 0): ?>
                <span class="tab-badge"><?= $open_reports_count ?></span>
            <?php endif; ?>
        </button>
        <a href="reports.php" class="admin-tab">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
            <?= __('reports') ?>
        </a>
    </div>

    <!-- ============================================
         PANEL: OVERVIEW
         ============================================ -->
    <div class="admin-panel active" data-panel="overview">
        <div class="admin-stats">
            <div class="admin-stat">
                <div class="admin-stat-label"><?= __('total_users') ?></div>
                <div class="admin-stat-value"><?= $stats['total_users'] ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label"><?= __('games_played') ?></div>
                <div class="admin-stat-value"><?= $stats['total_games'] ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label"><?= __('total_activities') ?></div>
                <div class="admin-stat-value"><?= number_format($stats['total_sessions']) ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label"><?= __('active_today') ?></div>
                <div class="admin-stat-value"><?= $stats['active_today'] ?></div>
            </div>
        </div>
    </div>

    <!-- ============================================
         PANEL: GAME ANALYTICS (KILLER FEATURE)
         ============================================ -->
    <div class="admin-panel" data-panel="games">

        <!-- Overall Stats -->
        <div class="admin-stats">
            <div class="admin-stat">
                <div class="admin-stat-label">Total Plays</div>
                <div class="admin-stat-value"><?= number_format($overall['total_plays']) ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">Active Players</div>
                <div class="admin-stat-value"><?= number_format($overall['unique_players']) ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">Total Play Time</div>
                <div class="admin-stat-value">
                    <?php
                    $h = floor($overall['total_time'] / 3600);
                    $m = floor(($overall['total_time'] % 3600) / 60);
                    echo $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                    ?>
                </div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">Avg Score</div>
                <div class="admin-stat-value"><?= number_format($overall['avg_score']) ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">High Score</div>
                <div class="admin-stat-value"><?= number_format($overall['high_score']) ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">Avg Session</div>
                <div class="admin-stat-value"><?= gmdate('i:s', $overall['avg_seconds']) ?></div>
            </div>
        </div>

        <!-- Plays chart -->
        <div class="analytics-section">
            <div class="analytics-section-title">📈 Plays — Last 30 Days</div>
            <div class="plays-chart">
                <?php
                $max_plays = max(array_merge([1], $chart_data));
                foreach ($chart_data as $i => $plays):
                    $height = $max_plays > 0 ? ($plays / $max_plays) * 100 : 0;
                    if ($height < 3) $height = 3;
                ?>
                    <div class="bar"
                         style="height: <?= $height ?>%;"
                         data-label="<?= htmlspecialchars($chart_labels[$i]) ?>: <?= $plays ?> plays"
                         title="<?= htmlspecialchars($chart_labels[$i]) ?>: <?= $plays ?> plays">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-legend">
                <span><?= htmlspecialchars($chart_labels[0]) ?></span>
                <span>Total: <?= array_sum($chart_data) ?> plays</span>
                <span><?= htmlspecialchars($chart_labels[count($chart_labels)-1]) ?></span>
            </div>
        </div>

        <!-- Per-game cards -->
        <div class="analytics-section">
            <div class="analytics-section-title">🎮 By Game</div>

            <?php if (empty($game_analytics)): ?>
                <p style="padding:40px;text-align:center;color:rgba(255,255,255,0.4);">
                    No game sessions recorded yet.
                </p>
            <?php else: ?>
                <?php
                $max_plays_game = max(array_column($game_analytics, 'total_plays'));
                foreach ($game_analytics as $g):
                    $bar_pct = $max_plays_game > 0 ? ($g['total_plays'] / $max_plays_game) * 100 : 0;

                    // Determine health
                    $last = strtotime($g['last_played'] ?? '');
                    $days_ago = $last ? floor((time() - $last) / 86400) : 999;
                    if ($days_ago <= 3) {
                        $health_class = 'healthy';
                        $health_text  = '✅ Healthy';
                    } elseif ($days_ago <= 14) {
                        $health_class = 'warning';
                        $health_text  = '⚠️ Low engagement';
                    } else {
                        $health_class = 'dead';
                        $health_text  = '🚨 Inactive';
                    }
                ?>
                    <div class="game-card">
                        <div class="game-card-header">
                            <div class="game-card-title"><?= htmlspecialchars($g['title']) ?></div>
                            <span class="game-health <?= $health_class ?>"><?= $health_text ?></span>
                        </div>

                        <div class="game-bar-track">
                            <div class="game-bar-fill" style="width: <?= round($bar_pct) ?>%;"></div>
                        </div>

                        <div class="game-stat-grid">
                            <div class="game-stat-item">
                                <span class="game-stat-label">Plays</span>
                                <span class="game-stat-value"><?= number_format($g['total_plays']) ?></span>
                            </div>
                            <div class="game-stat-item">
                                <span class="game-stat-label">Players</span>
                                <span class="game-stat-value purple"><?= number_format($g['unique_players']) ?></span>
                            </div>
                            <div class="game-stat-item">
                                <span class="game-stat-label">Avg Score</span>
                                <span class="game-stat-value"><?= number_format($g['avg_score']) ?></span>
                            </div>
                            <div class="game-stat-item">
                                <span class="game-stat-label">High Score</span>
                                <span class="game-stat-value gold"><?= number_format($g['high_score']) ?></span>
                            </div>
                            <div class="game-stat-item">
                                <span class="game-stat-label">Completion</span>
                                <span class="game-stat-value green"><?= $g['completion'] ?>%</span>
                            </div>
                            <div class="game-stat-item">
                                <span class="game-stat-label">Avg Session</span>
                                <span class="game-stat-value"><?= gmdate('i:s', $g['avg_seconds']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Two column grid: Top Players + Recent Sessions -->
        <div class="analytics-grid-2">

            <!-- Top Players this week -->
            <div class="analytics-section">
                <div class="analytics-section-title">🏆 Top Players This Week</div>

                <?php if (empty($top_players_week)): ?>
                    <p style="padding:30px;text-align:center;color:rgba(255,255,255,0.4);font-size:13px;">
                        No sessions in the last 7 days.
                    </p>
                <?php else: ?>
                    <div class="top-players-list">
                        <?php foreach ($top_players_week as $i => $p):
                            $rank = $i + 1;
                            $rank_class = $rank <= 3 ? "rank-{$rank}" : '';
                            $avatar_url = get_avatar_url($p['profile_picture'] ?? '');
                        ?>
                            <div class="top-player-row <?= $rank_class ?>">
                                <div class="top-player-rank"><?= $rank ?></div>
                                <div class="top-player-avatar">
                                    <?php if ($avatar_url): ?>
                                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($p['username'], 0, 2)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="top-player-info">
                                    <div class="top-player-name"><?= htmlspecialchars($p['username']) ?></div>
                                    <div class="top-player-meta">
                                        <?= $p['sessions'] ?> session<?= $p['sessions'] != 1 ? 's' : '' ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="top-player-score"><?= number_format($p['total_score']) ?></div>
                                    <div class="top-player-score-label">Total Score</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent sessions -->
            <div class="analytics-section">
                <div class="analytics-section-title">⚡ Recent Sessions</div>

                <?php if (empty($recent_sessions)): ?>
                    <p style="padding:30px;text-align:center;color:rgba(255,255,255,0.4);font-size:13px;">
                        No sessions recorded yet.
                    </p>
                <?php else: ?>
                    <div class="sessions-feed">
                        <?php foreach ($recent_sessions as $s):
                            $avatar_url = get_avatar_url($s['profile_picture'] ?? '');
                            $mins = floor($s['duration_seconds'] / 60);
                            $secs = $s['duration_seconds'] % 60;
                        ?>
                            <div class="session-row">
                                <div class="session-avatar">
                                    <?php if ($avatar_url): ?>
                                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($s['username'], 0, 2)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="session-info">
                                    <div class="session-user"><?= htmlspecialchars($s['username']) ?></div>
                                    <div class="session-game"><?= htmlspecialchars(strtoupper(str_replace('-', ' ', $s['game_id']))) ?></div>
                                </div>
                                <div class="session-meta">
                                    <div class="session-score"><?= number_format($s['score']) ?></div>
                                    <div class="session-time">
                                        <?= $mins ?>m <?= $secs ?>s · <?= date('g:i A', strtotime($s['played_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Peak Playtimes Heatmap -->
        <div class="analytics-section">
            <div class="analytics-section-title">🔥 Peak Playtimes (Last 30 Days)</div>

            <div class="heatmap-wrap">
                <div class="heatmap">
                    <!-- Empty top-left corner -->
                    <div></div>

                    <!-- Hour labels -->
                    <?php for ($h = 0; $h < 24; $h++): ?>
                        <div class="heatmap-label hour"><?= $h ?></div>
                    <?php endfor; ?>

                    <!-- Day rows -->
                    <?php
                    $day_names = ['', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    for ($d = 1; $d <= 7; $d++): ?>
                        <div class="heatmap-label"><?= $day_names[$d] ?></div>
                        <?php for ($h = 0; $h < 24; $h++):
                            $plays = $heatmap[$d][$h] ?? 0;
                            $intensity = $heatmap_max > 0 ? $plays / $heatmap_max : 0;
                            if ($plays > 0) {
                                $alpha = 0.15 + ($intensity * 0.85);
                                $bg = "rgba(209, 54, 57, {$alpha})";
                            } else {
                                $bg = "rgba(255,255,255,0.03)";
                            }
                        ?>
                            <div class="heatmap-cell"
                                 style="background: <?= $bg ?>;"
                                 title="<?= $day_names[$d] ?> @ <?= $h ?>:00 — <?= $plays ?> play<?= $plays != 1 ? 's' : '' ?>">
                            </div>
                        <?php endfor; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="margin-top:12px;font-size:11px;color:rgba(255,255,255,0.4);text-align:center;">
                Darker = more plays · Hover a cell for exact numbers
            </div>
        </div>

    </div>

    <!-- ============================================
         PANEL: USERS
         ============================================ -->
    <div class="admin-panel" data-panel="users">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= __('username') ?></th>
                        <th><?= __('email') ?></th>
                        <th><?= __('role') ?></th>
                        <th>2FA</th>
                        <th><?= __('level') ?></th>
                        <th>XP</th>
                        <th><?= __('high_score') ?></th>
                        <th><?= __('last_login') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="new_role" class="role-select" onchange="this.form.submit()"
                                    <?= $u['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                    <option value="admin"   <?= $u['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                <span class="tfa-badge <?= $u['two_factor_enabled'] ? 'on' : 'off' ?>">
                                    <?= $u['two_factor_enabled'] ? '✓ ON' : 'OFF' ?>
                                </span>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_2fa">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="enable" value="<?= $u['two_factor_enabled'] ? 0 : 1 ?>">
                                    <button type="submit"
                                            class="tfa-toggle <?= $u['two_factor_enabled'] ? 'on' : 'off' ?>">
                                        <?= $u['two_factor_enabled'] ? '✓ ON' : 'OFF' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['level'] ?></td>
                        <td><?= number_format($u['xp']) ?></td>
                        <td><?= number_format($u['high_score']) ?></td>
                        <td><?= $u['last_login'] ? date('M j, Y', strtotime($u['last_login'])) : __('never') ?></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Delete <?= htmlspecialchars($u['username']) ?>?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="admin-action-btn danger"><?= __('delete') ?></button>
                                </form>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.3);font-size:12px;">(<?= __('you') ?>)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================
         PANEL: ACTIVITY LOGS
         ============================================ -->
    <div class="admin-panel" data-panel="activity">
        <div class="admin-activity">
            <?php if (empty($activities)): ?>
                <p style="padding:40px;text-align:center;color:rgba(255,255,255,0.4);"><?= __('no_activity') ?></p>
            <?php else: ?>
                <?php foreach ($activities as $a): ?>
                    <div class="admin-activity-row">
                        <div class="admin-activity-user"><?= htmlspecialchars($a['username'] ?? 'System') ?></div>
                        <div class="admin-activity-text"><?= htmlspecialchars($a['activity']) ?></div>
                        <div class="admin-activity-time"><?= date('M j, g:i A', strtotime($a['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================
         PANEL: ISSUE REPORTS
         ============================================ -->
    <div class="admin-panel" data-panel="issues">
        <?php if (empty($issue_reports)): ?>
            <div class="admin-table-wrap" style="padding:60px;text-align:center;">
                <p style="color:rgba(255,255,255,0.4);font-size:14px;">No issue reports yet.</p>
            </div>
        <?php else: ?>
            <div class="issue-reports-list">
                <?php foreach ($issue_reports as $r): ?>
                    <div class="issue-report-card status-<?= htmlspecialchars($r['status']) ?>">
                        <div class="issue-report-header">
                            <div class="issue-report-left">
                                <span class="issue-status status-<?= htmlspecialchars($r['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
                                </span>
                                <span class="issue-severity severity-<?= htmlspecialchars($r['severity']) ?>">
                                    <?= ucfirst($r['severity']) ?>
                                </span>
                                <?php if ($r['game_id']): ?>
                                    <span class="issue-game">
                                        <?= htmlspecialchars(strtoupper(str_replace('-', ' ', $r['game_id']))) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="issue-report-meta">
                                #<?= $r['id'] ?> · <?= date('M j, Y · g:i A', strtotime($r['created_at'])) ?>
                            </div>
                        </div>

                        <h4 class="issue-subject"><?= htmlspecialchars($r['subject']) ?></h4>
                        <p class="issue-description"><?= nl2br(htmlspecialchars($r['description'])) ?></p>

                        <div class="issue-reporter">
                            <strong><?= htmlspecialchars($r['reporter_name'] ?? 'Unknown') ?></strong>
                            · <?= htmlspecialchars($r['reporter_email'] ?? '—') ?>
                        </div>

                        <?php if ($r['admin_notes']): ?>
                            <div class="issue-admin-notes">
                                <strong>Admin Notes:</strong>
                                <?= nl2br(htmlspecialchars($r['admin_notes'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="issue-report-actions">
                            <?php if ($r['status'] === 'open'): ?>
                                <button class="admin-action-btn" onclick="resolveReport(<?= $r['id'] ?>, 'in_review')">
                                    Mark In Review
                                </button>
                            <?php endif; ?>

                            <?php if ($r['status'] !== 'resolved' && $r['status'] !== 'dismissed'): ?>
                                <button class="admin-action-btn" style="background:rgba(46,204,113,0.15);color:#2ecc71;"
                                        onclick="resolveReportWithNotes(<?= $r['id'] ?>, 'resolved')">
                                    ✓ Resolve
                                </button>
                                <button class="admin-action-btn" onclick="resolveReport(<?= $r['id'] ?>, 'dismissed')">
                                    Dismiss
                                </button>
                            <?php endif; ?>

                            <button class="admin-action-btn danger" onclick="deleteReport(<?= $r['id'] ?>)">
                                <?= __('delete') ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</main>

<div class="bg-layer" id="bgLayer">
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>

<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<script src="js/settings.js?v=<?= time() ?>"></script>
<script>
document.querySelectorAll('.admin-tab').forEach(tab => {
    if (!tab.dataset.panel) return;
    tab.addEventListener('click', () => {
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.querySelector(`.admin-panel[data-panel="${tab.dataset.panel}"]`).classList.add('active');
    });
});

async function resolveReport(reportId, action) {
    if (!confirm(`Mark report #${reportId} as "${action.replace('_', ' ')}"?`)) return;
    try {
        const res = await fetch('resolve_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: reportId, action: action })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert('Failed: ' + (data.error || 'Unknown error'));
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

async function resolveReportWithNotes(reportId, action) {
    const notes = prompt('Add admin notes (optional):');
    if (notes === null) return;
    try {
        const res = await fetch('resolve_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: reportId, action: action, notes: notes })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert('Failed: ' + (data.error || 'Unknown error'));
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

async function deleteReport(reportId) {
    if (!confirm(`Delete report #${reportId}? This cannot be undone.`)) return;
    try {
        const res = await fetch('resolve_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: reportId, action: 'delete' })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert('Failed: ' + (data.error || 'Unknown error'));
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}
</script>
<script src="js/pwa.js?v=<?= time() . rand() ?>"></script>
</body>
</html>