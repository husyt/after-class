<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// ============================================
// FETCH CURRENT USER FIRST (critical for i18n)
// ============================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// ============================================
// LOAD TRANSLATION SYSTEM (needs $user)
// ============================================
require_once __DIR__ . '/../includes/i18n.php';

// ============================================
// LOAD AVATAR HELPER
// ============================================
require_once __DIR__ . '/../includes/avatar.php';

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
// GAME STATISTICS
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
$lastlog = $user['last_login'] ? date('M j, Y · g:i A', strtotime($user['last_login'])) : __('never');

// XP progress
$xp_current = $xp % 1000;
$xp_percent = ($xp_current / 1000) * 100;

// Format playtime
$total_seconds = (int)$game_stats['total_seconds'];
$hours   = floor($total_seconds / 3600);
$minutes = floor(($total_seconds % 3600) / 60);
$playtime = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";

// ============================================
// AVATAR SYSTEM (DiceBear CDN)
// ============================================
// NOTE: $avatar_seeds is needed for the picker grid below.
// The get_avatar_url() function is provided by includes/avatar.php
$avatar_seeds = ['Felix', 'Aneka', 'Leo', 'Mia', 'Kai', 'Zara', 'Ravi', 'Nora'];
$current_avatar_url = get_avatar_url($user['profile_picture'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('profile') ?> | CoreSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
</head>
<body data-bg="<?= htmlspecialchars($user['preferred_background'] ?? 'bg-home') ?>">
    
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
            <a href="leaderboard.php" class="nav-tab" title="<?= __('leaderboard') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"/>
                </svg>
            </a>
            <a href="profile.php" class="nav-tab active" title="<?= __('profile') ?>">
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
<main class="dashboard profile-page">

    <div class="section-head">
        <div class="section-head-left">
            <a href="dashboard.php" class="back-link-small" title="<?= __('back_to_home') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <?= __('back') ?>
            </a>
            <h1><?= __('profile') ?></h1>
        </div>
        <span class="user-greeting"><?= __('member_since') ?> <?= $joined ?></span>
    </div>

    <?php if ($profile_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($profile_error) ?></div>
    <?php endif; ?>
    <?php if ($profile_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($profile_success) ?></div>
    <?php endif; ?>

    <!-- Profile header -->
    <div class="profile-header">
        <div class="profile-avatar" id="profileAvatar" style="cursor: pointer;" title="Click to change avatar">
            <?php if ($current_avatar_url): ?>
                <img src="<?= htmlspecialchars($current_avatar_url) ?>" 
                     alt="Profile picture"
                     onerror="this.style.display='none';this.parentElement.innerHTML='<svg viewBox=\'0 0 24 24\' fill=\'currentColor\' style=\'width:36px;height:36px;\'><circle cx=\'12\' cy=\'8\' r=\'4\'/><path d=\'M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2\'/></svg>'">
            <?php else: ?>
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                </svg>
            <?php endif; ?>
            <div class="avatar-edit-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
        </div>
        <div class="profile-meta">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <div class="profile-tags">
                <span class="tag role"><?= htmlspecialchars($user['role']) ?></span>
                <span class="tag level"><?= __('level') ?> <?= $level ?></span>
            </div>
            <p class="profile-last-seen"><?= __('last_login') ?>: <?= $lastlog ?></p>
        </div>
    </div>

    <!-- ============================================
         PROFILE CONTENT — 2-COLUMN GRID
         ============================================ -->
    <div class="profile-content">

        <!-- LEFT COLUMN -->
        <div class="profile-column">

            <!-- Display Name & Pronouns -->
            <div class="profile-info-cards">
                <div class="info-card">
                    <div class="info-card-left">
                        <div class="info-card-label"><?= __('display_name') ?></div>
                        <div class="info-card-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
                    </div>
                    <button class="info-card-edit" type="button" data-field="username">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <?= __('edit') ?>
                    </button>
                </div>

                <div class="info-card">
                    <div class="info-card-left">
                        <div class="info-card-label"><?= __('pronouns') ?></div>
                        <div class="info-card-value <?= empty($user['pronouns']) ? 'empty-value' : '' ?>">
                            <?= htmlspecialchars($user['pronouns'] ?? __('not_set')) ?>
                        </div>
                    </div>
                    <button class="info-card-edit" type="button" data-field="pronouns">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <?= __('edit') ?>
                    </button>
                </div>
            </div>

            <!-- Account Details -->
            <div class="profile-section">
                <h3><?= __('account_details') ?></h3>
                <div class="account-details-grid">
                    <div class="account-detail">
                        <div class="account-detail-label"><?= __('username') ?></div>
                        <div class="account-detail-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
                    </div>
                    <div class="account-detail">
                        <div class="account-detail-label"><?= __('email') ?></div>
                        <div class="account-detail-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
                    </div>
                    <div class="account-detail">
                        <div class="account-detail-label"><?= __('role') ?></div>
                        <div class="account-detail-value">
                            <span class="tag role"><?= htmlspecialchars($user['role'] ?? 'user') ?></span>
                        </div>
                    </div>
                    <div class="account-detail">
                        <div class="account-detail-label"><?= __('member_since') ?></div>
                        <div class="account-detail-value"><?= $joined ?></div>
                    </div>
                    <div class="account-detail">
                        <div class="account-detail-label"><?= __('last_login') ?></div>
                        <div class="account-detail-value"><?= $lastlog ?></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="profile-column">

            <!-- Game Statistics -->
            <div class="profile-section">
                <h3><?= __('game_statistics') ?></h3>

                <div class="game-stats-grid">
                    <div class="game-stat-card">
                        <div class="game-stat-icon purple">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3 7h7l-5.5 4 2 7-6.5-4.5L5.5 20l2-7L2 9h7z"/>
                            </svg>
                        </div>
                        <div class="game-stat-info">
                            <div class="game-stat-label"><?= __('level') ?></div>
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
                            <div class="game-stat-label"><?= __('total_xp') ?></div>
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
                            <div class="game-stat-label"><?= __('high_score') ?></div>
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
                            <div class="game-stat-label"><?= __('games_played') ?></div>
                            <div class="game-stat-value"><?= number_format($game_stats['total_sessions']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- XP Progress -->
                <div class="xp-progress-wrap">
                    <div class="xp-progress-header">
                        <span><?= __('level') ?> <?= $level ?></span>
                        <span><?= $xp_current ?> / 1000 XP</span>
                    </div>
                    <div class="xp-progress-track">
                        <div class="xp-progress-fill" style="width: <?= $xp_percent ?>%"></div>
                    </div>
                </div>

                <!-- Per-Game Breakdown -->
                <h3 style="margin-top: 20px;"><?= __('per_game_breakdown') ?></h3>
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
                                        <div class="per-game-stat-label"><?= __('plays') ?></div>
                                        <div class="per-game-stat-value"><?= $g['plays'] ?></div>
                                    </div>
                                    <div class="per-game-stat">
                                        <div class="per-game-stat-label"><?= __('best') ?></div>
                                        <div class="per-game-stat-value"><?= number_format($g['high_score']) ?></div>
                                    </div>
                                    <div class="per-game-stat">
                                        <div class="per-game-stat-label"><?= __('avg') ?></div>
                                        <div class="per-game-stat-value"><?= number_format($g['avg_score'], 0) ?></div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="profile-section">
                <h3><?= __('recent_activity') ?></h3>
                <?php if (empty($activities)): ?>
                    <p class="empty-text"><?= __('no_activity') ?></p>
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

        </div>

    </div>

</main>

<!-- ============ AVATAR PICKER MODAL ============ -->
<div class="avatar-modal" id="avatarModal" aria-hidden="true">
    <div class="avatar-modal-backdrop" id="avatarBackdrop"></div>
    <div class="avatar-modal-panel" role="dialog">
        <button class="avatar-modal-close" id="avatarClose" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <h2>Choose Your Avatar</h2>
        <p class="avatar-modal-sub">Pick a character that represents you.</p>

        <div class="avatar-grid" id="avatarGrid">
            <!-- Default / Reset -->
            <button type="button" class="avatar-option <?= empty($user['profile_picture']) ? 'active' : '' ?>"
                    data-avatar=""
                    title="Default">
                <div class="avatar-preview avatar-default">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                    </svg>
                </div>
                <span class="avatar-label">Default</span>
            </button>

            <!-- 8 DiceBear Avatars -->
            <?php for ($i = 1; $i <= 8; $i++): 
                $avatar = "avatar-$i.png";
                $seed = $avatar_seeds[$i - 1];
                $dicebear_url = "https://api.dicebear.com/7.x/adventurer/svg?seed={$seed}&size=200&backgroundColor=7c3aed,d13639,f97316,2ecc71";
                $is_active = ($user['profile_picture'] ?? '') === $avatar;
            ?>
                <button type="button" class="avatar-option <?= $is_active ? 'active' : '' ?>"
                        data-avatar="<?= $avatar ?>"
                        title="Avatar <?= $i ?>">
                    <div class="avatar-preview">
                        <img src="<?= $dicebear_url ?>" 
                             alt="Avatar <?= $i ?>"
                             loading="lazy">
                    </div>
                    <span class="avatar-label">#<?= $i ?></span>
                </button>
            <?php endfor; ?>
        </div>

        <div class="avatar-modal-actions">
            <button type="button" class="action-btn secondary" id="avatarCancel">Cancel</button>
        </div>
    </div>
</div>

<!-- ============ EDIT PROFILE MODAL ============ -->
<div class="edit-modal" id="editModal" aria-hidden="true">
    <div class="edit-modal-backdrop" id="editBackdrop"></div>
    <div class="edit-modal-panel" role="dialog">
        <button class="edit-modal-close" id="editClose" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <h2 id="editTitle"><?= __('edit') ?></h2>
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

<script src="js/settings.js?v=<?= time() ?>"></script>
<script>
// ============================================
// PROFILE PAGE SCRIPTS
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // ========================================
    // EDIT PROFILE MODAL
    // ========================================
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

    // ========================================
    // AVATAR PICKER MODAL
    // ========================================
    const avatarModal = document.getElementById('avatarModal');
    const avatarBackdrop = document.getElementById('avatarBackdrop');
    const avatarClose = document.getElementById('avatarClose');
    const avatarCancel = document.getElementById('avatarCancel');
    const profileAvatar = document.getElementById('profileAvatar');

    function openAvatarModal() {
        avatarModal.classList.add('open');
        avatarModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeAvatarModal() {
        avatarModal.classList.remove('open');
        avatarModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (profileAvatar) profileAvatar.addEventListener('click', openAvatarModal);
    if (avatarClose) avatarClose.addEventListener('click', closeAvatarModal);
    if (avatarCancel) avatarCancel.addEventListener('click', closeAvatarModal);
    if (avatarBackdrop) avatarBackdrop.addEventListener('click', closeAvatarModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && avatarModal.classList.contains('open')) closeAvatarModal();
    });

    // Handle avatar selection
    document.querySelectorAll('.avatar-option').forEach(btn => {
        btn.addEventListener('click', async () => {
            const avatar = btn.dataset.avatar;

            try {
                const res = await fetch('update_preference.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        type: 'profile_picture', 
                        value: avatar 
                    })
                });
                const result = await res.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to update avatar: ' + (result.error || 'Unknown error'));
                }
            } catch (err) {
                console.error('Avatar update failed:', err);
                alert('Network error. Please try again.');
            }
        });
    });
});
</script>
</body>
</html>