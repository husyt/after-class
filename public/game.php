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

$stats = [
    'level'        => (int)($user['level'] ?? 1),
    'xp'           => (int)($user['xp'] ?? 0),
    'high_score'   => (int)($user['high_score'] ?? 0),
    'games_played' => (int)($user['games_played'] ?? 0),
];

$stats['xp_current'] = $stats['xp'] % 1000;
$stats['xp_next']    = 1000;
$stats['xp_percent'] = ($stats['xp_current'] / $stats['xp_next']) * 100;

// ==================================================
// GAME CATALOG
// ==================================================
$games = [
    [
        'id'          => 'lex-obscura',
        'title'       => 'LEX OBSCURA',
        'subtitle'    => 'A dark fantasy adventure',
        'description' => 'Each service would own its own contract and fallback behavior: authentication could use short-lived tokens and local validation so existing sessions survive a middleware outage; matchmaking could degrade to a simpler in-region pool or cached rules; payments could queue entitlement changes and reconcile later; cloud saves could write to a local or regional buffer and sync when the middleware recovers; leaderboards and analytics could accept events into durable local logs and replay them later.',
        'type'        => 'video',
        'video'       => '/after-class/assets/games/lexobscura-bg.mp4',
        'reels'       => [],
        'accent'      => '#7c3aed',
    ],
    [
        'id'          => 'after-class',
        'title'       => 'AFTER CLASS',
        'subtitle'    => 'A pixel art school adventure',
        'description' => 'Each service would own its own contract and fallback behavior: authentication could use short-lived tokens and local validation so existing sessions survive a middleware outage; matchmaking could degrade to a simpler in-region pool or cached rules; payments could queue entitlement changes and reconcile later; cloud saves could write to a local or regional buffer and sync when the middleware recovers; leaderboards and analytics could accept events into durable local logs and replay them later.',
        'type'        => 'video',
        'video'       => '/after-class/assets/games/afterclass-bg.mp4',
        'reels'       => [],
        'accent'      => '#f97316',
    ],
];

// Find the requested game
$id = $_GET['id'] ?? '';
$game = null;
foreach ($games as $g) {
    if ($g['id'] === $id) { $game = $g; break; }
}

if (!$game) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($game['title'] ?? '') ?> | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <style>
        .bg-video {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 0;
        }

        .reel-layer {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-size: cover;
            background-position: center;
            transition: opacity 1s ease-in-out;
            opacity: 0;
        }
        .reel-layer.active { opacity: 1; }

        .dark-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: linear-gradient(
                180deg,
                rgba(10,10,15,0.4) 0%,
                rgba(10,10,15,0.3) 40%,
                rgba(10,10,15,0.9) 100%
            );
            pointer-events: none;
        }

        .game-page {
            position: relative;
            z-index: 5;
            padding: 40px 64px;
            height: calc(100vh - 72px);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .back-btn {
            position: absolute;
            top: 24px;
            left: 32px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 999px;
            color: white;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.15); }

        .game-title {
            font-size: 72px;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1;
            margin-bottom: 8px;
            text-shadow: 0 4px 30px rgba(0,0,0,0.7);
        }

        .game-subtitle {
            font-size: 14px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 32px;
        }

        .game-desc {
            max-width: 640px;
            padding: 24px 28px;
            background: rgba(20, 20, 30, 0.65);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.65;
            color: rgba(255,255,255,0.85);
            margin-bottom: 32px;
        }

        .play-btn {
            align-self: flex-end;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 48px;
            background: var(--play-green);
            color: #0a1a10;
            border: none;
            border-radius: 999px;
            font-family: inherit;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 12px 40px rgba(46, 204, 113, 0.4);
            transition: all 0.25s ease;
        }
        .play-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 50px rgba(46, 204, 113, 0.6);
        }
        .play-btn svg { width: 22px; height: 22px; }

        /* ============================================
           STAT CARDS ON GAME PAGE
           ============================================ */
        .game-stats {
            position: absolute;
            top: 100px;
            right: 32px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 6;
            max-width: 240px;
        }

        .game-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(10, 10, 15, 0.65);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .game-stat:hover {
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateX(-2px);
        }

        .game-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: white;
        }

        .game-stat-icon.level {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            box-shadow: 0 6px 18px rgba(124, 58, 237, 0.4);
        }

        .game-stat-icon.xp {
            background: linear-gradient(135deg, #d13639, #f97316);
            box-shadow: 0 6px 18px rgba(209, 54, 57, 0.4);
        }

        .game-stat-icon.score {
            background: linear-gradient(135deg, #ffd700, #f59e0b);
            color: #1a0f00;
            box-shadow: 0 6px 18px rgba(255, 215, 0, 0.4);
        }

        .game-stat-icon.games {
            background: linear-gradient(135deg, #2ecc71, #059669);
            box-shadow: 0 6px 18px rgba(46, 204, 113, 0.4);
        }

        .game-stat-body {
            flex: 1;
            min-width: 0;
        }

        .game-stat-label {
            font-size: 9px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }

        .game-stat-value {
            font-size: 18px;
            font-weight: 800;
            color: white;
            line-height: 1;
        }

        @media (max-width: 900px) {
            .game-page { padding: 24px; }
            .game-title { font-size: 44px; }
            .play-btn { padding: 14px 32px; font-size: 16px; }
            .game-stats { display: none; }
        }
    </style>
</head>
<body data-bg="<?= htmlspecialchars($user['preferred_background'] ?? 'bg-home') ?>">

<?php if ($game['type'] === 'video' && !empty($game['video'])): ?>
    <!-- VIDEO BACKGROUND -->
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="<?= htmlspecialchars($game['video']) ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
<?php elseif (!empty($game['reels']) && count($game['reels']) >= 2): ?>
    <!-- ROTATING REEL BACKGROUND -->
    <div class="reel-layer active" id="reel1" style="background-image:url('<?= htmlspecialchars($game['reels'][0]) ?>');"></div>
    <div class="reel-layer" id="reel2" style="background-image:url('<?= htmlspecialchars($game['reels'][1]) ?>');"></div>
<?php endif; ?>

<div class="dark-overlay"></div>

<!-- Top nav -->
<header class="topnav">
    <div class="nav-left">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
    </div>
    <div class="nav-right">
        <a href="leaderboard.php" class="icon-btn" aria-label="<?= __('leaderboard') ?>" title="<?= __('leaderboard') ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"/>
            </svg>
        </a>
        <a href="dashboard.php" class="icon-btn" aria-label="<?= __('home') ?>" title="<?= __('home') ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12l9-9 9 9M5 10v10h14V10"/>
            </svg>
        </a>
        
        <!-- UPDATED USER AVATAR -->
        <div class="user-avatar">
            <?php render_nav_avatar($user['profile_picture'] ?? ''); ?>
        </div>

        <a href="logout.php" class="icon-btn" aria-label="<?= __('sign_out') ?>" title="<?= __('sign_out') ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <path d="M16 17l5-5-5-5M21 12H9"/>
            </svg>
        </a>
    </div>
</header>

<main class="game-page">

    <!-- Stat Cards -->
    <div class="game-stats">
        <div class="game-stat">
            <div class="game-stat-icon level">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3 7h7l-5.5 4 2 7-6.5-4.5L5.5 20l2-7L2 9h7z"/>
                </svg>
            </div>
            <div class="game-stat-body">
                <div class="game-stat-label"><?= __('level') ?></div>
                <div class="game-stat-value"><?= $stats['level'] ?></div>
            </div>
        </div>

        <div class="game-stat">
            <div class="game-stat-icon xp">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
            </div>
            <div class="game-stat-body">
                <div class="game-stat-label"><?= __('total_xp') ?></div>
                <div class="game-stat-value"><?= number_format($stats['xp']) ?></div>
            </div>
        </div>

        <div class="game-stat">
            <div class="game-stat-icon score">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"/>
                </svg>
            </div>
            <div class="game-stat-body">
                <div class="game-stat-label"><?= __('high_score') ?></div>
                <div class="game-stat-value"><?= number_format($stats['high_score']) ?></div>
            </div>
        </div>

        <div class="game-stat">
            <div class="game-stat-icon games">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="12" rx="4"/>
                    <path d="M6 12h4M8 10v4M15 11h.01M17 13h.01"/>
                </svg>
            </div>
            <div class="game-stat-body">
                <div class="game-stat-label"><?= __('games_played') ?></div>
                <div class="game-stat-value"><?= number_format($stats['games_played']) ?></div>
            </div>
        </div>
    </div>

    <a href="dashboard.php" class="back-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        <?= __('back_to_home') ?>
    </a>

    <h1 class="game-title"><?= htmlspecialchars($game['title'] ?? '') ?></h1>
    <p class="game-subtitle"><?= htmlspecialchars($game['subtitle'] ?? '') ?></p>

    <div class="game-desc">
        <?= htmlspecialchars($game['description'] ?? '') ?>
    </div>

    <a href="play.php?id=<?= urlencode($game['id']) ?>" class="play-btn">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
        <?= __('play') ?>
    </a>
</main>

<?php if ($game['type'] === 'reels' && !empty($game['reels']) && count($game['reels']) >= 2): ?>
<script>
    const r1 = document.getElementById('reel1');
    const r2 = document.getElementById('reel2');
    let current = 1;
    setInterval(() => {
        if (current === 1) {
            r1.classList.remove('active');
            r2.classList.add('active');
            current = 2;
        } else {
            r2.classList.remove('active');
            r1.classList.add('active');
            current = 1;
        }
    }, 5000);
</script>
<?php endif; ?>

</body>
</html>