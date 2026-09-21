<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($game['title']) ?> | EqualPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
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
<body>

<div class="game-bar">
    <div class="game-title"><?= htmlspecialchars($game['title']) ?></div>
    <a href="game.php?id=<?= urlencode($game_id) ?>" class="back-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back
    </a>
</div>

<iframe
    id="gameFrame"
    class="game-frame"
    src="<?= htmlspecialchars($game['src']) ?>"
    allow="autoplay; fullscreen"
    allowfullscreen>
</iframe>

<div class="score-toast" id="scoreToast"></div>

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

</body>
</html>