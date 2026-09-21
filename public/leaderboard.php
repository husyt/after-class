<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Current user
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
// LEADERBOARD: Top 10 users by high score
// ============================================
$stmt = $pdo->query(
    "SELECT id, username, role, level, xp, high_score, games_played, profile_picture
     FROM users
     WHERE games_played > 0
     ORDER BY high_score DESC, xp DESC
     LIMIT 10"
);
$leaderboard = $stmt->fetchAll();

// Find current user's rank
$user_rank = null;
foreach ($leaderboard as $i => $row) {
    if ($row['id'] == $_SESSION['user_id']) {
        $user_rank = $i + 1;
        break;
    }
}

// If user is not in top 10, compute their actual rank
if ($user_rank === null && $user['games_played'] > 0) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) + 1 FROM users 
         WHERE high_score > ? AND games_played > 0"
    );
    $stmt->execute([$user['high_score']]);
    $user_rank = (int)$stmt->fetchColumn();
}

// ============================================
// PER-GAME HIGH SCORES
// ============================================
$stmt = $pdo->query(
    "SELECT game_id, 
            MAX(score) as high_score,
            COUNT(*) as plays,
            COUNT(DISTINCT user_id) as players
     FROM game_sessions
     GROUP BY game_id
     ORDER BY plays DESC"
);
$game_leaders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('leaderboard') ?> | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <style>
        .leaderboard-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            padding: 6px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            width: fit-content;
        }

        .lb-tab {
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
        }

        .lb-tab:hover { color: white; }
        .lb-tab.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .lb-panel { display: none; }
        .lb-panel.active { display: block; }

        .rank-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rank-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 18px 24px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            transition: all 0.2s;
        }

        .rank-row:hover {
            background: rgba(20, 20, 30, 0.8);
            border-color: rgba(255,255,255,0.12);
        }

        .rank-row.you {
            background: linear-gradient(90deg, rgba(209,54,57,0.15), rgba(124,58,237,0.15));
            border-color: #d13639;
        }

        .rank-row.top-1 {
            background: linear-gradient(90deg, rgba(255,215,0,0.15), rgba(20,20,30,0.6));
            border-color: #ffd700;
        }

        .rank-row.top-2 {
            background: linear-gradient(90deg, rgba(192,192,192,0.12), rgba(20,20,30,0.6));
            border-color: #c0c0c0;
        }

        .rank-row.top-3 {
            background: linear-gradient(90deg, rgba(205,127,50,0.12), rgba(20,20,30,0.6));
            border-color: #cd7f32;
        }

        .rank-number {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 900;
            color: white;
            flex-shrink: 0;
        }

        .rank-row.top-1 .rank-number { background: #ffd700; color: #1a1a00; }
        .rank-row.top-2 .rank-number { background: #c0c0c0; color: #1a1a1a; }
        .rank-row.top-3 .rank-number { background: #cd7f32; color: #1a0f00; }

        .rank-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #d13639);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 16px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .rank-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .rank-info {
            flex: 1;
            min-width: 0;
        }

        .rank-name {
            font-size: 15px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rank-meta {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            display: flex;
            gap: 12px;
        }

        .rank-score {
            text-align: right;
            flex-shrink: 0;
        }

        .rank-score-value {
            font-size: 20px;
            font-weight: 800;
            color: #2ecc71;
            font-family: monospace;
        }

        .rank-score-label {
            font-size: 9px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .game-leaders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .game-leader-card {
            padding: 24px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
        }

        .game-leader-name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
            color: white;
        }

        .game-leader-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .game-leader-stat-label {
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }

        .game-leader-stat-value {
            font-size: 20px;
            font-weight: 800;
            color: #2ecc71;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.4);
            font-size: 14px;
        }
    </style>
</head>
<body class="profile-page" data-bg="<?= htmlspecialchars($user['preferred_background'] ?? 'bg-home') ?>">

<!-- ============ TOP NAV ============ -->
<header class="topnav">
    <div class="nav-left">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab" title="<?= __('home') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12l9-9 9 9M5 10v10h14V10"/>
                </svg>
            </a>
            <a href="library.php" class="nav-tab" title="<?= __('library') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
            </a>
            <a href="leaderboard.php" class="nav-tab active" title="<?= __('leaderboard') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"/>
                </svg>
            </a>
            <a href="profile.php" class="nav-tab" title="<?= __('profile') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                </svg>
            </a>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="admin.php" class="nav-tab" title="<?= __('admin') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/>
                </svg>
            </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="nav-right">
        <button class="icon-btn" aria-label="<?= __('settings') ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
        </button>
        
        <!-- UPDATED USER AVATAR -->
        <div class="user-avatar">
            <?php render_nav_avatar($user['profile_picture'] ?? ''); ?>
        </div>

        <a href="logout.php" class="icon-btn" aria-label="<?= __('sign_out') ?>">
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
            <a href="dashboard.php" class="back-link-small" title="<?= __('back_to_home') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <?= __('back') ?>
            </a>
            <h1><?= __('leaderboard') ?></h1>
        </div>
        <?php if ($user_rank !== null): ?>
        <span class="user-greeting">
            <?= __('your_rank') ?>: <strong>#<?= $user_rank ?></strong>
        </span>
        <?php endif; ?>
    </div>

    <div class="leaderboard-tabs">
        <button class="lb-tab active" data-panel="global"><?= __('global_rankings') ?></button>
        <button class="lb-tab" data-panel="games"><?= __('by_game') ?></button>
    </div>

    <!-- PANEL: GLOBAL -->
    <div class="lb-panel active" data-panel="global">
        <?php if (empty($leaderboard)): ?>
            <div class="empty-state"><?= __('no_players') ?></div>
        <?php else: ?>
            <div class="rank-list">
                <?php foreach ($leaderboard as $i => $row): ?>
                    <?php
                        $rank = $i + 1;
                        $row_class = '';
                        if ($rank === 1) $row_class = 'top-1';
                        elseif ($rank === 2) $row_class = 'top-2';
                        elseif ($rank === 3) $row_class = 'top-3';
                        if ($row['id'] == $_SESSION['user_id']) $row_class .= ' you';
                        
                        // Use DiceBear avatar if set, otherwise initials
                        $row_avatar = get_avatar_url($row['profile_picture'] ?? '');
                    ?>
                    <div class="rank-row <?= $row_class ?>">
                        <div class="rank-number"><?= $rank ?></div>
                        <div class="rank-avatar">
                            <?php if ($row_avatar): ?>
                                <img src="<?= htmlspecialchars($row_avatar) ?>" alt="<?= htmlspecialchars($row['username']) ?>">
                            <?php else: ?>
                                <?= strtoupper(substr($row['username'], 0, 2)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="rank-info">
                            <div class="rank-name">
                                <?= htmlspecialchars($row['username']) ?>
                                <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                    <span style="color:#d13639; font-size:11px;">(<?= __('you') ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="rank-meta">
                                <span><?= __('level') ?> <?= $row['level'] ?></span>
                                <span>·</span>
                                <span><?= number_format($row['xp']) ?> XP</span>
                                <span>·</span>
                                <span><?= $row['games_played'] ?> <?= __('games_played') ?></span>
                            </div>
                        </div>
                        <div class="rank-score">
                            <div class="rank-score-value"><?= number_format($row['high_score']) ?></div>
                            <div class="rank-score-label"><?= __('high_score') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- PANEL: BY GAME -->
    <div class="lb-panel" data-panel="games">
        <?php if (empty($game_leaders)): ?>
            <div class="empty-state">
                No games played yet. Start playing to see stats here.
            </div>
        <?php else: ?>
            <div class="game-leaders-grid">
                <?php foreach ($game_leaders as $g): ?>
                    <div class="game-leader-card">
                        <div class="game-leader-name">
                            <?= htmlspecialchars(strtoupper(str_replace('-', ' ', $g['game_id']))) ?>
                        </div>
                        <div class="game-leader-stats">
                            <div>
                                <div class="game-leader-stat-label"><?= __('high_score') ?></div>
                                <div class="game-leader-stat-value"><?= number_format($g['high_score']) ?></div>
                            </div>
                            <div>
                                <div class="game-leader-stat-label"><?= __('plays') ?></div>
                                <div class="game-leader-stat-value"><?= number_format($g['plays']) ?></div>
                            </div>
                        </div>
                        <div style="margin-top:12px;font-size:11px;color:rgba(255,255,255,0.4);">
                            <?= $g['players'] ?> <?= $g['players'] == 1 ? 'player' : 'players' ?>
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
document.querySelectorAll('.lb-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.lb-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.querySelector(`.lb-panel[data-panel="${tab.dataset.panel}"]`).classList.add('active');
    });
});
</script>
</body>
</html>