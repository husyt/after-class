
<?php

require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// FETCH CURRENT USER
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// TRANSLATION SYSTEM
require_once __DIR__ . '/../includes/i18n.php';

// SELECT GAME
$game_id = $_GET['id'] ?? '';

$allowed_games = [
    'lex-obscura' => [
        'title' => 'LEX OBSCURA',
        'src' => '/after-class/assets/game/index.html',
    ],

    'after-class' => [
        'title' => 'AFTER CLASS',
        'src' => '/after-class/public/godot_game/index.html',
    ],
];

if (!is_string($game_id) || !isset($allowed_games[$game_id])) {
    header('Location: dashboard.php');
    exit;
}

$game = $allowed_games[$game_id];

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <title><?= htmlspecialchars($game['title'], ENT_QUOTES, 'UTF-8') ?> | EqualPath</title>

    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="/after-class/public/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="EqualPath">

    <link rel="apple-touch-icon" href="/after-class/assets/icons/icon-192.png">
    <link rel="icon" type="image/png" href="/after-class/assets/icons/icon-192.png">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #000;
        }

        /* FULLSCREEN GAME CONTAINER */
        .game-container {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            background: #000;
        }

        /* FULLSCREEN GAME IFRAME */
        .game-frame {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: #000;
        }

        /* FLOATING BACK TO DASHBOARD BUTTON */
        .back-btn {
            position: fixed;
            top: calc(16px + env(safe-area-inset-top, 0px));
            right: calc(16px + env(safe-area-inset-right, 0px));
            z-index: 100;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            padding: 11px 16px;
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 999px;

            background: rgba(15,15,20,0.82);
            color: #fff;

            font: 600 13px/1.2 Arial, sans-serif;
            text-decoration: none;
            white-space: nowrap;

            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            transition: background 0.2s ease;
        }

        .back-btn:hover,
        .back-btn:focus-visible {
            background: rgba(45,45,55,0.95);
        }

        .back-btn:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 3px;
        }

        .back-btn svg {
            flex: none;
        }

        /* SCORE NOTIFICATION */
        .score-toast {
            position: fixed;
            right: calc(16px + env(safe-area-inset-right, 0px));
            bottom: calc(20px + env(safe-area-inset-bottom, 0px));
            z-index: 200;

            max-width: min(360px, calc(100vw - 32px));
            padding: 14px 20px;

            border-radius: 10px;
            background: #3498db;
            color: #fff;

            font: 700 14px/1.4 Arial, sans-serif;
            box-shadow: 0 10px 32px rgba(0,0,0,0.3);

            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;

            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .score-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 600px) {
            .back-btn {
                top: calc(10px + env(safe-area-inset-top, 0px));
                right: calc(10px + env(safe-area-inset-right, 0px));
                padding: 10px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

    <!-- FULLSCREEN GAME -->
    <main class="game-container">
        <iframe
            id="gameFrame"
            class="game-frame"
            src="<?= htmlspecialchars($game['src'], ENT_QUOTES, 'UTF-8') ?>"
            title="<?= htmlspecialchars($game['title'], ENT_QUOTES, 'UTF-8') ?>"
            allow="autoplay; fullscreen"
            allowfullscreen
        ></iframe>
    </main>

    <!-- ONE DASHBOARD BUTTON -->
    <a
        class="back-btn"
        href="dashboard.php"
        aria-label="Back to Dashboard"
    >
        <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        Back to Dashboard
    </a>

    <!-- SCORE TOAST -->
    <div
        id="scoreToast"
        class="score-toast"
        role="status"
        aria-live="polite"
    ></div>

    <!-- GODOT COMMUNICATION -->
    <script>

        const GAME_ID =
            <?= json_encode($game_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        const toast =
            document.getElementById('scoreToast');

        const gameFrame =
            document.getElementById('gameFrame');

        let scoreIsSaving = false;

        // RECEIVE MESSAGE FROM GODOT
        window.addEventListener(
            'message',
            async function (event) {

                if (
                    event.origin !== window.location.origin ||
                    event.source !== gameFrame.contentWindow
                ) {
                    return;
                }

                const data = event.data;

                if (!data || typeof data !== 'object') {
                    return;
                }

                // GODOT READY
                if (data.type === 'gameReady') {
                    console.log('Godot game ready.');
                    return;
                }

                // GAME OVER
                if (data.type !== 'gameOver' || scoreIsSaving) {
                    return;
                }

                const payload = {
                    game_id: GAME_ID,
                    score: Number(data.score) || 0,
                    duration: Number(data.duration) || 0,
                    level: Number(data.level) || 1,
                    completed: Number(data.completed) || 0
                };

                await saveScoreWithRetry(payload);
            }
        );

        // SAVE SCORE
        async function saveScoreWithRetry(payload) {

            const MAX_RETRIES = 3;
            scoreIsSaving = true;

            for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {

                showToast(
                    attempt === 1
                        ? 'Saving score...'
                        : `Retrying save... (${attempt}/${MAX_RETRIES})`,
                    '#3498db'
                );

                try {

                    const response = await fetch(
                        'save_score.php',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        }
                    );

                    if (
                        response.status === 503 &&
                        attempt < MAX_RETRIES
                    ) {
                        await sleep(1000 * attempt);
                        continue;
                    }

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(
                            result.error || 'Unable to save score.'
                        );
                    }

                    const earnedXP = result.xp_earned ?? 0;

                    showToast(
                        `✓ Score saved: ${payload.score} (+${earnedXP} XP)`,
                        '#2ecc71',
                        '#07130b'
                    );

                    setTimeout(function () {
                        window.location.href = 'dashboard.php';
                    }, 2000);

                    return;

                } catch (error) {

                    console.error(
                        `Attempt ${attempt} failed:`,
                        error
                    );

                    if (attempt === MAX_RETRIES) {

                        scoreIsSaving = false;

                        showToast(
                            '⚠ Could not save score.',
                            '#d13639'
                        );

                        setTimeout(function () {

                            if (confirm('We could not save your score. Retry?')) {
                                saveScoreWithRetry(payload);
                            }

                        }, 1200);

                        return;
                    }

                    await sleep(1000 * attempt);
                }
            }
        }

        // DISPLAY SCORE NOTIFICATION
        function showToast(
            message,
            backgroundColor,
            textColor = 'white'
        ) {
            toast.textContent = message;
            toast.style.background = backgroundColor;
            toast.style.color = textColor;
            toast.classList.add('show');
        }

        // DELAY HELPER
        function sleep(ms) {
            return new Promise(function (resolve) {
                setTimeout(resolve, ms);
            });
        }

    </script>

    <!-- PWA -->
    <script src="js/pwa.js?v=<?= time() ?>"></script>

</body>
</html>
