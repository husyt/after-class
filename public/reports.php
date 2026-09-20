<?php
require_once __DIR__ . '/../includes/session.php';
requireAdmin();   // ← Admin-only protection

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================================
// DATE RANGE (defaults to last 30 days)
// ============================================
$preset = $_GET['preset'] ?? '30d';
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

// Apply preset if no custom range
if (!$from || !$to) {
    switch ($preset) {
        case 'today':
            $from = date('Y-m-d');
            $to   = date('Y-m-d');
            break;
        case '7d':
            $from = date('Y-m-d', strtotime('-7 days'));
            $to   = date('Y-m-d');
            break;
        case '30d':
            $from = date('Y-m-d', strtotime('-30 days'));
            $to   = date('Y-m-d');
            break;
        case 'all':
            $from = '2000-01-01';
            $to   = date('Y-m-d');
            break;
        default:
            $from = date('Y-m-d', strtotime('-30 days'));
            $to   = date('Y-m-d');
            $preset = '30d';
    }
}

// For DB queries: expand "to" to end of day
$from_dt = $from . ' 00:00:00';
$to_dt   = $to   . ' 23:59:59';

// ============================================
// REPORT 1: SUMMARY STATS
// ============================================
$stmt = $pdo->prepare(
    "SELECT 
        COUNT(*) as total_sessions,
        COUNT(DISTINCT user_id) as unique_players,
        COUNT(DISTINCT game_id) as games_played,
        COALESCE(SUM(score), 0) as total_score,
        COALESCE(AVG(score), 0) as avg_score,
        COALESCE(MAX(score), 0) as high_score
     FROM game_sessions
     WHERE played_at BETWEEN ? AND ?"
);
$stmt->execute([$from_dt, $to_dt]);
$summary = $stmt->fetch();

// ============================================
// REPORT 2: GAME PARTICIPATION (per game)
// ============================================
$stmt = $pdo->prepare(
    "SELECT 
        game_id,
        COUNT(*) as sessions,
        COUNT(DISTINCT user_id) as players,
        COALESCE(AVG(score), 0) as avg_score,
        COALESCE(MAX(score), 0) as high_score,
        COALESCE(SUM(score), 0) as total_score
     FROM game_sessions
     WHERE played_at BETWEEN ? AND ?
     GROUP BY game_id
     ORDER BY sessions DESC"
);
$stmt->execute([$from_dt, $to_dt]);
$game_stats = $stmt->fetchAll();

// ============================================
// REPORT 3: USER PERFORMANCE
// ============================================
$stmt = $pdo->prepare(
    "SELECT 
        u.id,
        u.username,
        u.role,
        COUNT(gs.id) as sessions,
        COALESCE(SUM(gs.score), 0) as total_score,
        COALESCE(AVG(gs.score), 0) as avg_score,
        COALESCE(MAX(gs.score), 0) as high_score
     FROM users u
     LEFT JOIN game_sessions gs 
        ON gs.user_id = u.id 
        AND gs.played_at BETWEEN ? AND ?
     GROUP BY u.id
     ORDER BY total_score DESC, u.username ASC"
);
$stmt->execute([$from_dt, $to_dt]);
$user_stats = $stmt->fetchAll();

// ============================================
// REPORT 4: DAILY ACTIVITY (for chart/data table)
// ============================================
$stmt = $pdo->prepare(
    "SELECT 
        DATE(played_at) as day,
        COUNT(*) as sessions,
        COUNT(DISTINCT user_id) as players,
        COALESCE(SUM(score), 0) as total_score
     FROM game_sessions
     WHERE played_at BETWEEN ? AND ?
     GROUP BY DATE(played_at)
     ORDER BY day DESC
     LIMIT 30"
);
$stmt->execute([$from_dt, $to_dt]);
$daily_stats = $stmt->fetchAll();

// ============================================
// EXPORT CSV
// ============================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="coresync-report-' . date('Y-m-d') . '.csv"');
    
    $out = fopen('php://output', 'w');
    
    // Section 1: Summary
    fputcsv($out, ['CoreSync - Reports Export']);
    fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($out, ['Date Range', $from . ' to ' . $to]);
    fputcsv($out, []);
    
    fputcsv($out, ['SUMMARY']);
    fputcsv($out, ['Total Sessions', $summary['total_sessions']]);
    fputcsv($out, ['Unique Players', $summary['unique_players']]);
    fputcsv($out, ['Games Played', $summary['games_played']]);
    fputcsv($out, ['Total Score', $summary['total_score']]);
    fputcsv($out, ['Average Score', round($summary['avg_score'], 2)]);
    fputcsv($out, ['High Score', $summary['high_score']]);
    fputcsv($out, []);
    
    // Section 2: Game participation
    fputcsv($out, ['GAME PARTICIPATION']);
    fputcsv($out, ['Game', 'Sessions', 'Players', 'Avg Score', 'High Score', 'Total Score']);
    foreach ($game_stats as $g) {
        fputcsv($out, [
            $g['game_id'],
            $g['sessions'],
            $g['players'],
            round($g['avg_score'], 2),
            $g['high_score'],
            $g['total_score'],
        ]);
    }
    fputcsv($out, []);
    
    // Section 3: User performance
    fputcsv($out, ['USER PERFORMANCE']);
    fputcsv($out, ['Username', 'Role', 'Sessions', 'Avg Score', 'High Score', 'Total Score']);
    foreach ($user_stats as $u) {
        fputcsv($out, [
            $u['username'],
            $u['role'],
            $u['sessions'],
            round($u['avg_score'], 2),
            $u['high_score'],
            $u['total_score'],
        ]);
    }
    
    fclose($out);
    logActivity($pdo, $_SESSION['user_id'], 'Exported reports CSV');
    exit;
}

// Get current admin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | CoreSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Reports-specific styles */
        .report-badge {
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

        /* Filter bar */
        .report-filter {
            display: flex;
            gap: 16px;
            align-items: flex-end;
            flex-wrap: wrap;
            padding: 20px 24px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            margin-bottom: 24px;
        }

        .report-filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 140px;
        }

        .report-filter-group label {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .report-input,
        .report-select {
            padding: 10px 14px;
            background: #1a1a25;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            color: white;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }

        .report-input:focus,
        .report-select:focus {
            border-color: rgba(255,255,255,0.25);
        }

        /* Presets */
        .preset-row {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .preset-btn {
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 999px;
            color: rgba(255,255,255,0.7);
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .preset-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .preset-btn.active {
            background: #d13639;
            border-color: #d13639;
            color: white;
        }

        /* Action buttons */
        .report-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .report-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            color: white;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .report-action-btn:hover {
            background: rgba(255,255,255,0.12);
        }

        .report-action-btn.primary {
            background: #2ecc71;
            border-color: #2ecc71;
            color: #0a1a10;
        }

        .report-action-btn.primary:hover {
            background: #27ae60;
        }

        /* Summary cards */
        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .report-card {
            padding: 20px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
        }

        .report-card-label {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }

        .report-card-value {
            font-size: 28px;
            font-weight: 800;
            color: white;
        }

        /* Section headings */
        .report-section-title {
            font-size: 14px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 32px 0 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        /* Tables */
        .report-table-wrap {
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            overflow: hidden;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .report-table thead {
            background: rgba(255,255,255,0.04);
        }

        .report-table th {
            text-align: left;
            padding: 14px 20px;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .report-table td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.85);
        }

        .report-table tr:last-child td { border-bottom: none; }
        .report-table tr:hover { background: rgba(255,255,255,0.02); }

        .report-table td.num {
            font-family: monospace;
            font-weight: 700;
            text-align: right;
        }

        .report-role {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-role.admin { background: #d13639; color: white; }
        .report-role.student { background: #2ecc71; color: #0a1a10; }

        /* Empty state */
        .report-empty {
            padding: 60px 20px;
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 14px;
        }

        /* Print styles */
        @media print {
            body { background: white !important; color: black !important; }
            .topnav, .report-actions, .report-filter, .preset-row, .bg-layer, .settings-overlay { display: none !important; }
            .report-card, .report-table-wrap { border: 1px solid #ccc !important; background: white !important; }
            .report-card-value, .report-section-title { color: black !important; }
            .report-table th, .report-table td { color: black !important; border-color: #eee !important; }
        }

        @media (max-width: 700px) {
            .report-filter { flex-direction: column; }
            .report-table-wrap { overflow-x: auto; }
        }
    </style>
</head>
<body class="reports-page">


<!-- ============ TOP NAV ============ -->
<header class="topnav">
    <div class="nav-left">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab" title="Home">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12l9-9 9 9M5 10v10h14V10"/>
                </svg>
            </a>
            <a href="library.php" class="nav-tab" title="Library">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
            </a>
            <a href="admin.php" class="nav-tab" title="Admin Panel">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/>
                </svg>
            </a>
            <a href="reports.php" class="nav-tab active" title="Reports">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3v18h18"/>
                    <path d="M18 17V9M13 17V5M8 17v-3"/>
                </svg>
            </a>
            <a href="profile.php" class="nav-tab" title="Profile">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                </svg>
            </a>
        </nav>
    </div>

    <div class="nav-right">
        <button class="icon-btn" aria-label="Settings">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
        </button>
        <div class="user-avatar">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4"/>
                <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
            </svg>
        </div>
        <a href="logout.php" class="icon-btn" aria-label="Sign out">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <path d="M16 17l5-5-5-5M21 12H9"/>
            </svg>
        </a>
    </div>
</header>

<!-- ============ MAIN ============ -->
<main class="dashboard">

    <div class="section-head">
        <div class="section-head-left">
            <a href="admin.php" class="back-link-small" title="Back to Admin">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <h1>Reports</h1>
            <span class="report-badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/>
                </svg>
                Admin Only
            </span>
        </div>
        <span class="user-greeting">
            Showing <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?>
        </span>
    </div>

    <!-- Preset buttons -->
    <div class="preset-row">
        <a href="?preset=today" class="preset-btn <?= $preset === 'today' ? 'active' : '' ?>">Today</a>
        <a href="?preset=7d"    class="preset-btn <?= $preset === '7d'    ? 'active' : '' ?>">Last 7 Days</a>
        <a href="?preset=30d"   class="preset-btn <?= $preset === '30d'   ? 'active' : '' ?>">Last 30 Days</a>
        <a href="?preset=all"   class="preset-btn <?= $preset === 'all'   ? 'active' : '' ?>">All Time</a>
    </div>

    <!-- Custom date filter -->
    <form method="GET" class="report-filter">
        <div class="report-filter-group">
            <label for="from">From</label>
            <input type="date" id="from" name="from" class="report-input" value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="report-filter-group">
            <label for="to">To</label>
            <input type="date" id="to" name="to" class="report-input" value="<?= htmlspecialchars($to) ?>">
        </div>
        <div class="report-filter-group" style="flex:0;">
            <button type="submit" class="report-action-btn primary" style="height:42px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                Apply Filter
            </button>
        </div>
    </form>

    <!-- Action buttons -->
    <div class="report-actions">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="report-action-btn primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <path d="M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Export CSV
        </a>
        <button type="button" class="report-action-btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print
        </button>
    </div>

    <!-- ============================================
         SUMMARY CARDS
         ============================================ -->
    <div class="report-summary">
        <div class="report-card">
            <div class="report-card-label">Total Sessions</div>
            <div class="report-card-value"><?= number_format($summary['total_sessions']) ?></div>
        </div>
        <div class="report-card">
            <div class="report-card-label">Unique Players</div>
            <div class="report-card-value"><?= number_format($summary['unique_players']) ?></div>
        </div>
        <div class="report-card">
            <div class="report-card-label">Games Played</div>
            <div class="report-card-value"><?= number_format($summary['games_played']) ?></div>
        </div>
        <div class="report-card">
            <div class="report-card-label">Total Score</div>
            <div class="report-card-value"><?= number_format($summary['total_score']) ?></div>
        </div>
        <div class="report-card">
            <div class="report-card-label">Avg Score</div>
            <div class="report-card-value"><?= number_format($summary['avg_score'], 0) ?></div>
        </div>
        <div class="report-card">
            <div class="report-card-label">High Score</div>
            <div class="report-card-value"><?= number_format($summary['high_score']) ?></div>
        </div>
    </div>

    <!-- ============================================
         GAME PARTICIPATION REPORT
         ============================================ -->
    <h2 class="report-section-title">Game Participation</h2>
    <div class="report-table-wrap">
        <?php if (empty($game_stats)): ?>
            <div class="report-empty">No game sessions in this date range.</div>
        <?php else: ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th style="text-align:right;">Sessions</th>
                        <th style="text-align:right;">Players</th>
                        <th style="text-align:right;">Avg Score</th>
                        <th style="text-align:right;">High Score</th>
                        <th style="text-align:right;">Total Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($game_stats as $g): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(strtoupper(str_replace('-', ' ', $g['game_id']))) ?></strong></td>
                            <td class="num"><?= number_format($g['sessions']) ?></td>
                            <td class="num"><?= number_format($g['players']) ?></td>
                            <td class="num"><?= number_format($g['avg_score'], 0) ?></td>
                            <td class="num"><?= number_format($g['high_score']) ?></td>
                            <td class="num"><?= number_format($g['total_score']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- ============================================
         USER PERFORMANCE REPORT
         ============================================ -->
    <h2 class="report-section-title">User Performance</h2>
    <div class="report-table-wrap">
        <?php if (empty($user_stats)): ?>
            <div class="report-empty">No users found.</div>
        <?php else: ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th style="text-align:right;">Sessions</th>
                        <th style="text-align:right;">Avg Score</th>
                        <th style="text-align:right;">High Score</th>
                        <th style="text-align:right;">Total Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_stats as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><span class="report-role <?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                            <td class="num"><?= number_format($u['sessions']) ?></td>
                            <td class="num"><?= number_format($u['avg_score'], 0) ?></td>
                            <td class="num"><?= number_format($u['high_score']) ?></td>
                            <td class="num"><?= number_format($u['total_score']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- ============================================
         DAILY ACTIVITY REPORT
         ============================================ -->
    <h2 class="report-section-title">Daily Activity</h2>
    <div class="report-table-wrap">
        <?php if (empty($daily_stats)): ?>
            <div class="report-empty">No activity in this date range.</div>
        <?php else: ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th style="text-align:right;">Sessions</th>
                        <th style="text-align:right;">Players</th>
                        <th style="text-align:right;">Total Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daily_stats as $d): ?>
                        <tr>
                            <td><?= date('l, M j, Y', strtotime($d['day'])) ?></td>
                            <td class="num"><?= number_format($d['sessions']) ?></td>
                            <td class="num"><?= number_format($d['players']) ?></td>
                            <td class="num"><?= number_format($d['total_score']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<!-- Background layer -->
<div class="bg-layer" id="bgLayer">
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>

<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<script src="js/settings.js"></script>
</body>
</html>