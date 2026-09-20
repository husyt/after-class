<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Flash messages
$profile_error = $_SESSION['profile_error'] ?? '';
$profile_success = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_error'], $_SESSION['profile_success']);

// Recent activity
$stmt = $pdo->prepare(
    "SELECT activity, created_at FROM activity_logs 
     WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
);
$stmt->execute([$_SESSION['user_id']]);
$activities = $stmt->fetchAll();

// ============================================
// GAME STATISTICS (§11)
// ============================================
$stmt = $pdo->prepare(
    "SELECT 
        COUNT(*) as total_sessions,
        COALESCE(SUM(score), 0) as total_score,
        COALESCE(AVG(score), 0) as avg_score,
        COALESCE(MAX(score), 0) as high_score,
        COALESCE(SUM(duration_seconds), 0) as total_seconds
     FROM game_sessions
     WHERE user_id = ?"
);
$stmt->execute([$_SESSION['user_id']]);
$game_stats = $stmt->fetch();

// Per-game breakdown
$stmt = $pdo->prepare(
    "SELECT 
        game_id,
        COUNT(*) as plays,
        COALESCE(MAX(score), 0) as high_score,
        COALESCE(AVG(score), 0) as avg_score
     FROM game_sessions
     WHERE user_id = ?
     GROUP BY game_id
     ORDER BY plays DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$per_game = $stmt->fetchAll();

// Stats
$level   = (int)($user['level'] ?? 1);
$xp      = (int)($user['xp'] ?? 0);
$joined  = $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : '—';
$lastlog = $user['last_login'] ? date('M j, Y · g:i A', strtotime($user['last_login'])) : 'Never';

// XP progress
$xp_current = $xp % 1000;
$xp_percent = ($xp_current / 1000) * 100;

// Format playtime
$total_seconds = (int)$game_stats['total_seconds'];
$hours   = floor($total_seconds / 3600);
$minutes = floor(($total_seconds % 3600) / 60);
$playtime = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | CoreSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* ============================================
           GAME STATS SECTION
           ============================================ */
        .game-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .game-stat-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            transition: all 0.2s;
        }

        .game-stat-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
        }

        .game-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: white;
        }

        .game-stat-icon.purple {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
        }
        .game-stat-icon.red {
            background: linear-gradient(135deg, #d13639, #f97316);
            box-shadow: 0 4px 14px rgba(209, 54, 57, 0.35);
        }
        .game-stat-icon.gold {
            background: linear-gradient(135deg, #ffd700, #f59e0b);
            color: #1a0f00;
            box-shadow: 0 4px 14px rgba(255, 215, 0, 0.35);
        }
        .game-stat-icon.green {
            background: linear-gradient(135deg, #2ecc71, #059669);
            box-shadow: 0 4px 14px rgba(46, 204, 113, 0.35);
        }

        .game-stat-info {
            flex: 1;
            min-width: 0;
        }

        .game-stat-label {
            font-size: 9px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.45);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 3px;
        }

        .game-stat-value {
            font-size: 20px;
            font-weight: 800;
            color: white;
            line-height: 1;
        }

        /* XP Progress Bar */
        .xp-progress-wrap {
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .xp-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }

        .xp-progress-track {
            height: 8px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            overflow: hidden;
        }

        .xp-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #7c3aed, #a855f7);
            border-radius: 999px;
            transition: width 0.8s ease;
        }

        /* Per-game table */
        .per-game-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .per-game-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .per-game-row:last-child {
            border-bottom: none;
        }

        .per-game-name {
            flex: 1;
            font-size: 14px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .per-game-stats {
            display: flex;
            gap: 20px;
            font-size: 12px;
        }

        .per-game-stat {
            text-align: right;
        }

        .per-game-stat-label {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .per-game-stat-value {
            font-size: 14px;
            font-weight: 800;
            color: #2ecc71;
            font-family: monospace;
        }

        .empty-games {
            text-align: center;
            padding: 30px 20px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 13px;
        }
    </style>
</head>
<body>
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
            <a href="leaderboard.php" class="nav-tab" title="Leaderboard">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"/>
                </svg>
            </a>
            <a href="profile.php" class="nav-tab active" title="Profile">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                </svg>
            </a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="admin.php" class="nav-tab" title="Admin">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/>
                </svg>
            </a>
            <?php endif; ?>
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
            <a href="dashboard.php" class="back-link-small" title="Back to Home">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <h1>Profile</h1>
        </div>
        <span class="user-greeting">Member since <?= $joined ?></span>
    </div>

    <?php if ($profile_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($profile_error) ?></div>
    <?php endif; ?>
    <?php if ($profile_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($profile_success) ?></div>
    <?php endif; ?>

    <!-- Profile header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4"/>
                <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
            </svg>
        </div>
        <div class="profile-meta">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <div class="profile-tags">
                <span class="tag role"><?= htmlspecialchars($user['role']) ?></span>
                <span class="tag level">Level <?= $level ?></span>
            </div>
            <p class="profile-last-seen">Last login: <?= $lastlog ?></p>
        </div>
    </div>

    <!-- Display Name & Pronouns -->
    <div class="profile-info-cards">
        <div class="info-card">
            <div class="info-card-left">
                <div class="info-card-label">Display Name</div>
                <div class="info-card-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
            </div>
            <button class="info-card-edit" type="button" data-field="username">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit
            </button>
        </div>

        <div class="info-card">
            <div class="info-card-left">
                <div class="info-card-label">Pronouns</div>
                <div class="info-card-value <?= empty($user['pronouns']) ? 'empty-value' : '' ?>">
                    <?= htmlspecialchars($user['pronouns'] ?? 'Not set') ?>
                </div>
            </div>
            <button class="info-card-edit" type="button" data-field="pronouns">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit
            </button>
        </div>
    </div>

    <!-- ============================================
         GAME STATISTICS (§11)
         ============================================ -->
    <div class="profile-section">
        <h3>Game Statistics</h3>

        <div class="game-stats-grid">
            <div class="game-stat-card">
                <div class="game-stat-icon purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l3 7h7l-5.5 4 2 7-6.5-4.5L5.5 20l2-7L2 9h7z"/>
                    </svg>
                </div>
                <div class="game-stat-info">
                    <div class="game-stat-label">Level</div>
                    <div class="game-stat-value"><?= $level ?></div>
                </div>
            </div>

            <div class="game-stat-card">
                <div class="game-stat-icon red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </div>
                <div class="game-stat-info">
                    <div class="game-stat-label">Total XP</div>
                    <div class="game-stat-value"><?= number_format($xp) ?></div>
                </div>
            </div>

            <div class="game-stat-card">
                <div class="game-stat-icon gold">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"/>
                    </svg>
                </div>
                <div class="game-stat-info">
                    <div class="game-stat-label">High Score</div>
                    <div class="game-stat-value"><?= number_format($game_stats['high_score']) ?></div>
                </div>
            </div>

            <div class="game-stat-card">
                <div class="game-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="6" width="20" height="12" rx="4"/>
                        <path d="M6 12h4M8 10v4M15 11h.01M17 13h.01"/>
                    </svg>
                </div>
                <div class="game-stat-info">
                    <div class="game-stat-label">Games Played</div>
                    <div class="game-stat-value"><?= number_format($game_stats['total_sessions']) ?></div>
                </div>
            </div>
        </div>

        <!-- XP Progress to next level -->
        <div class="xp-progress-wrap">
            <div class="xp-progress-header">
                <span>Level <?= $level ?></span>
                <span><?= $xp_current ?> / 1000 XP</span>
            </div>
            <div class="xp-progress-track">
                <div class="xp-progress-fill" style="width: <?= $xp_percent ?>%"></div>
            </div>
        </div>

        <!-- Per-game stats -->
        <h3 style="margin-top: 24px;">Per-Game Breakdown</h3>
        <?php if (empty($per_game)): ?>
            <p class="empty-games">No games played yet. Start playing to see stats!</p>
        <?php else: ?>
            <ul class="per-game-list">
                <?php foreach ($per_game as $g): ?>
                    <li class="per-game-row">
                        <div class="per-game-name">
                            <?= htmlspecialchars(strtoupper(str_replace('-', ' ', $g['game_id']))) ?>
                        </div>
                        <div class="per-game-stats">
                            <div class="per-game-stat">
                                <div class="per-game-stat-label">Plays</div>
                                <div class="per-game-stat-value"><?= $g['plays'] ?></div>
                            </div>
                            <div class="per-game-stat">
                                <div class="per-game-stat-label">Best</div>
                                <div class="per-game-stat-value"><?= number_format($g['high_score']) ?></div>
                            </div>
                            <div class="per-game-stat">
                                <div class="per-game-stat-label">Avg</div>
                                <div class="per-game-stat-value"><?= number_format($g['avg_score'], 0) ?></div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Account Details -->
    <div class="profile-section">
        <h3>Account Details</h3>
        <div class="account-details-grid">
            <div class="account-detail">
                <div class="account-detail-label">Username</div>
                <div class="account-detail-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Email</div>
                <div class="account-detail-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Role</div>
                <div class="account-detail-value">
                    <span class="tag role"><?= htmlspecialchars($user['role'] ?? 'user') ?></span>
                </div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Member Since</div>
                <div class="account-detail-value"><?= $joined ?></div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Last Login</div>
                <div class="account-detail-value"><?= $lastlog ?></div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="profile-section">
        <h3>Recent Activity</h3>
        <?php if (empty($activities)): ?>
            <p class="empty-text">No activity yet. Play a game to get started!</p>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($activities as $a): ?>
                    <li>
                        <span class="activity-dot"></span>
                        <span class="activity-text"><?= htmlspecialchars($a['activity']) ?></span>
                        <span class="activity-time"><?= date('M j, g:i A', strtotime($a['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</main>

<!-- ============ EDIT PROFILE MODAL ============ -->
<div class="edit-modal" id="editModal" aria-hidden="true">
    <div class="edit-modal-backdrop" id="editBackdrop"></div>
    <div class="edit-modal-panel" role="dialog">
        <button class="edit-modal-close" id="editClose" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <h2 id="editTitle">Edit</h2>
        <p class="edit-modal-sub" id="editSub"></p>

        <form id="editForm" method="POST" action="update_profile.php">
            <input type="hidden" name="field" id="editField" value="">

            <div class="form-group">
                <label id="editLabel" for="editInput">Value</label>
                <input type="text" id="editInput" name="value" required autocomplete="off">
                <span class="field-error" id="editError"></span>
            </div>

            <div class="edit-modal-actions">
                <button type="button" class="action-btn secondary" id="editCancel">Cancel</button>
                <button type="submit" class="action-btn primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Background layer -->
<div class="bg-layer" id="bgLayer">
    <audio id="bgMusic" loop preload="auto">
        <source src="/after-class/assets/audio/theme.mp3" type="audio/mpeg">
    </audio>
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>

<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<script src="js/settings.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('editModal');
    const backdrop = document.getElementById('editBackdrop');
    const closeBtn = document.getElementById('editClose');
    const cancelBtn = document.getElementById('editCancel');
    const form = document.getElementById('editForm');
    const title = document.getElementById('editTitle');
    const sub = document.getElementById('editSub');
    const fieldInput = document.getElementById('editField');
    const inputField = document.getElementById('editInput');
    const inputLabel = document.getElementById('editLabel');
    const errorEl = document.getElementById('editError');

    const userData = {
        username: '<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>',
        pronouns: '<?= htmlspecialchars($user['pronouns'] ?? '', ENT_QUOTES) ?>',
    };

    function openEdit(field) {
        fieldInput.value = field;

        if (field === 'username') {
            title.textContent = 'Edit Display Name';
            sub.textContent = 'This is how your name appears throughout CoreSync.';
            inputLabel.textContent = 'Display Name';
            inputField.value = userData.username;
            inputField.placeholder = 'Enter display name';
            inputField.maxLength = 50;
        } else if (field === 'pronouns') {
            title.textContent = 'Edit Pronouns';
            sub.textContent = 'Let others know how to refer to you.';
            inputLabel.textContent = 'Pronouns';
            inputField.value = userData.pronouns;
            inputField.placeholder = 'e.g. he/him, she/her, they/them';
            inputField.maxLength = 30;
        }

        errorEl.textContent = '';
        inputField.classList.remove('error');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => inputField.focus(), 100);
    }

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.info-card-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            openEdit(btn.dataset.field);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });

    if (form) {
        form.addEventListener('submit', (e) => {
            const val = inputField.value.trim();
            const field = fieldInput.value;

            if (field === 'username') {
                if (val.length < 3) {
                    e.preventDefault();
                    errorEl.textContent = 'Display name must be at least 3 characters.';
                    inputField.classList.add('error');
                    return;
                }
                if (!/^[a-zA-Z0-9_\s]+$/.test(val)) {
                    e.preventDefault();
                    errorEl.textContent = 'Only letters, numbers, spaces, and underscores.';
                    inputField.classList.add('error');
                    return;
                }
            } else if (field === 'pronouns') {
                if (val.length > 30) {
                    e.preventDefault();
                    errorEl.textContent = 'Pronouns must be 30 characters or less.';
                    inputField.classList.add('error');
                    return;
                }
            }
        });
    }
});
</script>
</body>
</html>