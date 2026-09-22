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

$game_id = $_GET['id'] ?? '';

$allowed_games = [
    'lex-obscura' => [
        'title' => 'LEX OBSCURA',
        'src'   => '/after-class/assets/game/index.html',
    ],
    'after-class' => [
        'title' => 'AFTER CLASS',
        'src'   => '/after-class/assets/game/index.html',
    ],
];

if (!isset($allowed_games[$game_id])) {
    header('Location: dashboard.php');
    exit;
}

$game = $allowed_games[$game_id];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($game['title']) ?> | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/settings.css?v=<?= time() ?>">
    <link rel="manifest" href="/after-class/public/manifest.json">
<meta name="theme-color" content="#d13639">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="EqualPath">
<link rel="apple-touch-icon" href="/after-class/assets/icons/icon-192.png">
<link rel="icon" type="image/png" href="/after-class/assets/icons/icon-192.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #0a0a0f;
            color: white;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Reuse the standard topnav from dashboard.css, but reduce its height for the game */
        .topnav { position: relative; z-index: 10; }

        .game-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
            background: rgba(10, 10, 15, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            z-index: 10;
        }

        .game-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.08);
            border-radius: 999px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.15); }

        .game-frame {
            flex: 1;
            width: 100%;
            border: none;
            background: #000;
        }

        .score-toast {
            position: fixed;
            top: 80px;
            right: 24px;
            padding: 16px 24px;
            background: #3498db;
            color: white;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 12px 40px rgba(52,152,219,0.4);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 999;
            max-width: 340px;
        }
        .score-toast.show { transform: translateX(0); }
    </style>
</head>
<body data-bg="<?= htmlspecialchars($user['preferred_background'] ?? 'bg-home') ?>">

<!-- ============ TOP NAV (consistent with other pages) ============ -->
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

<!-- ============ GAME BAR (game title + back link) ============ -->
<div class="game-bar">
    <div class="game-title"><?= htmlspecialchars($game['title']) ?></div>
    <a href="game.php?id=<?= urlencode($game_id) ?>" class="back-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        <?= __('back') ?>
    </a>
</div>

<!-- ============ GAME IFRAME ============ -->
<iframe
    id="gameFrame"
    class="game-frame"
    src="<?= htmlspecialchars($game['src']) ?>"
    allow="autoplay; fullscreen"
    allowfullscreen>
</iframe>

<div class="score-toast" id="scoreToast"></div>

<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<script src="js/settings.js?v=<?= time() ?>"></script>
<script>
const GAME_ID = <?= json_encode($game_id) ?>;
const toast = document.getElementById('scoreToast');

// ========================================
// LISTEN FOR GAME OVER FROM GODOT
// ========================================
window.addEventListener('message', async (event) => {
    const data = event.data;
    if (!data || typeof data !== 'object') return;
    if (data.type !== 'gameOver') return;

    console.log('Game over received:', data);

    // Save score with retry
    saveScoreWithRetry({
        game_id: GAME_ID,
        score: data.score || 0,
        duration: data.duration || 0,
        level: data.level || 1,
        completed: data.completed || 0
    });
});

// ========================================
// SAVE SCORE WITH CLIENT-SIDE RETRY
// ========================================
async function saveScoreWithRetry(payload) {
    const MAX_RETRIES = 3;
    let attempt = 0;

    while (attempt < MAX_RETRIES) {
        attempt++;

        // Show toast
        showToast(
            attempt === 1 
                ? 'Saving score...' 
                : `Retrying save... (${attempt}/${MAX_RETRIES})`,
            '#3498db'
        );

        try {
            const res = await fetch('save_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            // Server says retry (503) — wait, then loop
            if (res.status === 503 && attempt < MAX_RETRIES) {
                await sleep(1000 * attempt);
                continue;
            }

            const result = await res.json();

            if (result.success) {
                showToast(
                    `✓ Score saved: ${payload.score} (+${result.xp_earned} XP)`,
                    '#2ecc71',
                    '#0a1a10'
                );

                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
                return;
            }

            throw new Error(result.error || 'Unknown error');

        } catch (err) {
            console.error(`Attempt ${attempt} failed:`, err);

            if (attempt >= MAX_RETRIES) {
                // All retries failed
                showToast('⚠ Could not save score. Please try again.', '#d13639');

                setTimeout(() => {
                    if (confirm("We couldn't save your score. Would you like to retry?")) {
                        saveScoreWithRetry(payload);
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                }, 1500);
                return;
            }

            // Wait before next attempt
            await sleep(1000 * attempt);
        }
    }
}

// ========================================
// HELPERS
// ========================================
function showToast(message, bgColor, textColor = 'white') {
    toast.textContent = message;
    toast.style.background = bgColor;
    toast.style.color = textColor;
    toast.classList.add('show');
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}
</script>
<script src="js/music.js?v=<?= time() . rand() ?>"></script>
<script src="js/pwa.js?v=<?= time() . rand() ?>"></script>

</body>
</html>