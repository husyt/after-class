<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_error'] = 'Please log in to continue.';
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// ============================================
// FETCH CURRENT USER FIRST (Critical for i18n)
// ============================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// ============================================
// LOAD TRANSLATION SYSTEM (needs $user)
// ============================================
require_once __DIR__ . '/../includes/i18n.php';

// ============================================
// LOAD AVATAR HELPER (needs $user)
// ============================================
require_once __DIR__ . '/../includes/avatar.php';

// ============================================
// LOAD USER FAVORITES
// ============================================
$stmt = $pdo->prepare("SELECT game_id FROM user_favorites WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$favorites = array_column($stmt->fetchAll(), 'game_id');

// ==================================================
// GAME CATALOG
// ==================================================
$games = [
    [
        'id'          => 'after-class',
        'title'       => 'AFTER CLASS',
        'subtitle'    => 'An inclusive education adventure',
        'featured'    => true,
        'type'        => 'video',
        'video'       => '/after-class/assets/games/afterclass-bg.mp4',
        'reels'       => [],
        'thumbnail'   => '/after-class/assets/games/afterclass-thumb.jpg',
        'accent'      => '#f97316',
    ],
    [
        'id'          => 'lex-obscura',
        'title'       => 'LEX OBSCURA',
        'subtitle'    => 'A dark fantasy adventure',
        'featured'    => false,
        'type'        => 'video',
        'video'       => '/after-class/assets/games/lexobscura-bg.mp4',
        'reels'       => [],
        'thumbnail'   => '/after-class/assets/games/lexobscura-thumb.jpg',
        'accent'      => '#7c3aed',
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('home') ?> | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/settings.css?v=<?= time() ?>">
</head>
<body data-bg="<?= htmlspecialchars($user['preferred_background'] ?? 'bg-home') ?>">

<!-- BACKGROUND -->
<div class="bg-layer" id="bgLayer">
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>

<!-- TOP NAV -->
<header class="topnav">
    <div class="nav-left">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab active" title="<?= __('home') ?>">
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

        <!-- USER AVATAR (now uses shared helper) -->
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

<!-- MAIN -->
<main class="dashboard">

    <div class="section-head">
        <h1><?= __('home') ?></h1>
        <span class="user-greeting"><?= __('welcome_back') ?>, <?= htmlspecialchars($user['username']) ?></span>
    </div>

    <!-- Game Row -->
    <div class="game-row" id="gameRow">
        <?php foreach ($games as $i => $g): ?>
            <div class="game-tile <?= $i === 0 ? 'active' : '' ?>"
                 data-index="<?= $i ?>"
                 data-id="<?= htmlspecialchars($g['id']) ?>"
                 data-accent="<?= htmlspecialchars($g['accent']) ?>"
                 data-type="<?= htmlspecialchars($g['type']) ?>"
                 data-title="<?= htmlspecialchars($g['title']) ?>"
                 data-thumbnail="<?= htmlspecialchars($g['thumbnail']) ?>"
                 <?php if ($g['type'] === 'video'): ?>
                 data-video="<?= htmlspecialchars($g['video']) ?>"
                 <?php else: ?>
                 data-reel1="<?= htmlspecialchars($g['reels'][0] ?? '') ?>"
                 data-reel2="<?= htmlspecialchars($g['reels'][1] ?? '') ?>"
                 <?php endif; ?>>
                <div class="tile-thumb" style="background-image:url('<?= htmlspecialchars($g['thumbnail']) ?>');">
                    <div class="tile-overlay"></div>

                    <?php if (!empty($g['featured'])): ?>
                        <div class="tile-featured-badge">⭐ <?= strtoupper(__('featured')) ?></div>
                    <?php endif; ?>

                    <button class="tile-heart <?= in_array($g['id'], $favorites) ? 'active' : '' ?>"
                            type="button"
                            data-game-id="<?= htmlspecialchars($g['id']) ?>"
                            aria-label="Toggle favorite"
                            title="<?= in_array($g['id'], $favorites) ? __('remove_favorites') : __('add_favorites') ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                        </svg>
                    </button>

                    <div class="tile-label"><?= htmlspecialchars($g['title']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Action Bar -->
    <div class="action-bar" id="actionBar">
        <a href="#" class="action-btn primary" id="playBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
            </svg>
            <span><?= __('play') ?></span>
        </a>

        <button class="action-btn secondary" id="infoBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4M12 8h.01"/>
            </svg>
            <span><?= __('info') ?></span>
        </button>

        <div class="more-wrapper">
            <button class="action-btn secondary" id="moreBtn" aria-haspopup="true" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"/>
                    <circle cx="19" cy="12" r="1"/>
                    <circle cx="5" cy="12" r="1"/>
                </svg>
                <span><?= __('more') ?></span>
            </button>

            <div class="more-menu" id="moreMenu" role="menu">
                <button class="more-item" data-action="favorite" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                    </svg>
                    <span><?= __('add_favorites') ?></span>
                </button>

                <button class="more-item" data-action="details" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                    <span><?= __('game_details') ?></span>
                </button>

                <button class="more-item" data-action="share" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"/>
                        <circle cx="6" cy="12" r="3"/>
                        <circle cx="18" cy="19" r="3"/>
                        <path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98"/>
                    </svg>
                    <span><?= __('share') ?></span>
                </button>

                <button class="more-item" data-action="report" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/>
                    </svg>
                    <span><?= __('report_issue') ?></span>
                </button>

                <div class="more-divider"></div>

                <button class="more-item danger" data-action="remove" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    </svg>
                    <span><?= __('remove_library') ?></span>
                </button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

</main>

<!-- INFO MODAL -->
<div class="info-modal" id="infoModal" aria-hidden="true">
    <div class="info-modal-backdrop" id="infoBackdrop"></div>
    <div class="info-modal-panel" role="dialog" aria-labelledby="infoTitle">
        <button class="info-modal-close" id="infoClose" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <div class="info-header">
            <div class="info-logo">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h2 id="infoTitle">EqualPath</h2>
            <p class="info-tagline">Play. Learn. Level Up.</p>
        </div>

        <div class="info-body">
            <p class="info-desc">
                EqualPath is a game-integrated learning platform built for schools and organizations.
                It combines secure authentication, real-time game progress tracking, and detailed
                analytics into one unified system — so every session becomes measurable, meaningful,
                and fun.
            </p>

            <div class="info-stats">
                <div class="info-stat">
                    <div class="info-stat-value">v1.0.0</div>
                    <div class="info-stat-label"><?= __('version') ?></div>
                </div>
                <div class="info-stat">
                    <div class="info-stat-value">2</div>
                    <div class="info-stat-label">Games</div>
                </div>
                <div class="info-stat">
                    <div class="info-stat-value">2026</div>
                    <div class="info-stat-label">Released</div>
                </div>
            </div>

            <div class="info-features">
                <h3>Key Features</h3>
                <ul>
                    <li>Secure login with two-factor authentication</li>
                    <li>QR code sign-in for mobile devices</li>
                    <li>Integrated game progress and score tracking</li>
                    <li>Role-based access control for students and admins</li>
                    <li>Real-time leaderboards and achievement system</li>
                    <li>Detailed activity logs and analytics dashboard</li>
                </ul>
            </div>

            <div class="info-footer">
                <p>
                    <strong>EqualPath Technologies</strong><br>
                    Built with PHP, MySQL, and a lot of late nights.<br>
                    © 2026 EqualPath. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- REPORT ISSUE MODAL -->
<div class="report-modal" id="reportModal" aria-hidden="true">
    <div class="report-modal-backdrop" id="reportBackdrop"></div>
    <div class="report-modal-panel" role="dialog">
        <button class="report-modal-close" id="reportClose" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <div class="report-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/>
            </svg>
        </div>

        <h2><?= __('report_issue') ?></h2>
        <p class="report-sub">Tell us what went wrong and we'll look into it.</p>

        <form id="reportForm">
            <div class="form-group">
                <label for="reportSubject">Subject</label>
                <input type="text" id="reportSubject"
                       placeholder="e.g. Game won't load"
                       required maxlength="150">
                <span class="field-error" id="reportSubjectError"></span>
            </div>

            <div class="form-group">
                <label for="reportSeverity">Severity</label>
                <select id="reportSeverity" class="report-select">
                    <option value="low">Low — Minor issue</option>
                    <option value="medium" selected>Medium — Affects gameplay</option>
                    <option value="high">High — Blocks progress</option>
                    <option value="critical">Critical — System broken</option>
                </select>
            </div>

            <div class="form-group">
                <label for="reportDescription">Description</label>
                <textarea id="reportDescription"
                          placeholder="Describe the issue in detail..."
                          required maxlength="2000" rows="5"></textarea>
                <span class="field-error" id="reportDescriptionError"></span>
            </div>

            <div class="report-modal-actions">
                <button type="button" class="action-btn secondary" id="reportCancel">Cancel</button>
                <button type="submit" class="action-btn primary" id="reportSubmit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                    </svg>
                    Send Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Background music -->
<audio id="bgMusic" loop preload="auto">
    <source src="/after-class/assets/audio/theme.mp3" type="audio/mpeg">
</audio>

<!-- Settings drawer -->
<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<!-- Data for JS -->
<script>
    window.GAMES = <?= json_encode($games, JSON_UNESCAPED_SLASHES) ?>;
    window.FAVORITES = <?= json_encode($favorites, JSON_UNESCAPED_SLASHES) ?>;
    window.TRANSLATIONS = {
        add_favorites:    '<?= __('add_favorites') ?>',
        remove_favorites: '<?= __('remove_favorites') ?>',
        game_details:     '<?= __('game_details') ?>',
        share:            '<?= __('share') ?>',
        report_issue:     '<?= __('report_issue') ?>',
        remove_library:   '<?= __('remove_library') ?>',
        added_favorites:  '<?= __('added_favorites') ?>',
        removed_favorites:'<?= __('removed_favorites') ?>',
        link_copied:      '<?= __('link_copied') ?>'
    };
</script>

<script src="js/dashboard.js?v=<?= time() ?>"></script>
<script src="js/settings.js?v=<?= time() ?>"></script>
</body>
</html>