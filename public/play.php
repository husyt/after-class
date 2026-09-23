<?php

require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


// ============================================
// FETCH CURRENT USER
// ============================================

$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE id = ?"
);

$stmt->execute([
    $_SESSION['user_id']
]);

$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}


// ============================================
// TRANSLATION SYSTEM
// ============================================

require_once __DIR__ . '/../includes/i18n.php';


// ============================================
// AVATAR HELPER
// ============================================

require_once __DIR__ . '/../includes/avatar.php';


// ============================================
// SELECT GAME
// ============================================

$game_id = $_GET['id'] ?? '';

$allowed_games = [

    'lex-obscura' => [
        'title' => 'LEX OBSCURA',
        'src'   => '/after-class/assets/game/index.html',
    ],

    'after-class' => [
        'title' => 'AFTER CLASS',

        // GODOT WEB EXPORT
        'src'   => '/after-class/public/godot_game/index.html',
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($game['title']) ?> | EqualPath
    </title>


    <!-- GOOGLE FONT -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="css/dashboard.css?v=<?= time() ?>"
    >

    <link
        rel="stylesheet"
        href="css/settings.css?v=<?= time() ?>"
    >


    <!-- PWA -->

    <link
        rel="manifest"
        href="/after-class/public/manifest.json"
    >

    <meta
        name="theme-color"
        content="#d13639"
    >

    <meta
        name="mobile-web-app-capable"
        content="yes"
    >

    <meta
        name="apple-mobile-web-app-capable"
        content="yes"
    >

    <meta
        name="apple-mobile-web-app-status-bar-style"
        content="black-translucent"
    >

    <meta
        name="apple-mobile-web-app-title"
        content="EqualPath"
    >

    <link
        rel="apple-touch-icon"
        href="/after-class/assets/icons/icon-192.png"
    >

    <link
        rel="icon"
        type="image/png"
        href="/after-class/assets/icons/icon-192.png"
    >


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
        }

        body {
            background: #0a0a0f;
            color: white;
            font-family: 'Inter', sans-serif;

            height: 100vh;

            display: flex;
            flex-direction: column;

            overflow: hidden;
        }


        /* =====================================
           TOP NAV
        ===================================== */

        .topnav {
            position: relative;
            z-index: 100;
            flex-shrink: 0;
        }


        /* =====================================
           GAME BAR
        ===================================== */

        .game-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 10px 24px;

            background: rgba(10, 10, 15, 0.96);

            backdrop-filter: blur(20px);

            border-bottom: 1px solid rgba(255,255,255,0.06);

            flex-shrink: 0;
            z-index: 50;
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

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-1px);
        }


        /* =====================================
           GAME CONTAINER
        ===================================== */

        .game-container {
            position: relative;

            flex: 1;
            min-height: 0;

            width: 100%;

            background: #000;

            overflow: hidden;
        }


        /* =====================================
           GODOT IFRAME
        ===================================== */

        .game-frame {
            position: absolute;

            top: 0;
            left: 0;

            width: 100%;
            height: 100%;

            border: 0;

            display: block;

            background: #000;
        }


        /* =====================================
           SCORE TOAST
        ===================================== */

        .score-toast {
            position: fixed;

            top: 90px;
            right: 24px;

            padding: 16px 24px;

            background: #3498db;
            color: white;

            border-radius: 12px;

            font-weight: 700;
            font-size: 14px;

            box-shadow:
                0 12px 40px rgba(52,152,219,0.4);

            transform: translateX(130%);
            opacity: 0;

            transition:
                transform 0.3s ease,
                opacity 0.3s ease;

            z-index: 9999;

            max-width: 340px;
        }

        .score-toast.show {
            transform: translateX(0);
            opacity: 1;
        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 700px) {

            .game-bar {
                padding: 8px 12px;
            }

            .game-title {
                font-size: 14px;
            }

            .back-btn {
                padding: 7px 12px;
            }

            .score-toast {
                left: 12px;
                right: 12px;
                top: 75px;

                max-width: none;
            }
        }

    </style>

</head>


<body
    data-bg="<?= htmlspecialchars(
        $user['preferred_background']
        ?? 'bg-home'
    ) ?>"
>


<!-- ============================================
     TOP NAVIGATION
============================================ -->

<header class="topnav">

    <div class="nav-left">

        <!-- LOGO -->

        <div class="logo-mark">

            <svg
                viewBox="0 0 24 24"
                fill="currentColor"
            >

                <path
                    d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
                />

            </svg>

        </div>


        <!-- NAVIGATION -->

        <nav class="nav-tabs">

            <!-- HOME -->

            <a
                href="dashboard.php"
                class="nav-tab"
                title="<?= htmlspecialchars(__('home')) ?>"
            >

                <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <path
                        d="M3 12l9-9 9 9M5 10v10h14V10"
                    />

                </svg>

            </a>


            <!-- LIBRARY -->

            <a
                href="library.php"
                class="nav-tab"
                title="<?= htmlspecialchars(__('library')) ?>"
            >

                <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <rect
                        x="3"
                        y="3"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="14"
                        y="3"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="14"
                        y="14"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="3"
                        y="14"
                        width="7"
                        height="7"
                    />

                </svg>

            </a>


            <!-- LEADERBOARD -->

            <a
                href="leaderboard.php"
                class="nav-tab"
                title="<?= htmlspecialchars(__('leaderboard')) ?>"
            >

                <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <path
                        d="M6 9V2h12v7M6 9H2v3a4 4 0 004 4h1M18 9h4v3a4 4 0 01-4 4h-1M9 21h6M12 17v4"
                    />

                </svg>

            </a>


            <!-- PROFILE -->

            <a
                href="profile.php"
                class="nav-tab"
                title="<?= htmlspecialchars(__('profile')) ?>"
            >

                <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <circle
                        cx="12"
                        cy="8"
                        r="4"
                    />

                    <path
                        d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"
                    />

                </svg>

            </a>


            <!-- ADMIN -->

            <?php if (
                ($_SESSION['role'] ?? '')
                === 'admin'
            ): ?>

                <a
                    href="admin.php"
                    class="nav-tab"
                    title="<?= htmlspecialchars(__('admin')) ?>"
                >

                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <path
                            d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"
                        />

                    </svg>

                </a>

            <?php endif; ?>

        </nav>

    </div>


    <!-- RIGHT SIDE -->

    <div class="nav-right">

        <!-- SETTINGS -->

        <button
            class="icon-btn"
            id="settingsBtn"
            type="button"
            aria-label="<?= htmlspecialchars(__('settings')) ?>"
        >

            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >

                <circle
                    cx="12"
                    cy="12"
                    r="3"
                />

                <path
                    d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"
                />

            </svg>

        </button>


        <!-- AVATAR -->

        <div class="user-avatar">

            <?php
            render_nav_avatar(
                $user['profile_picture']
                ?? ''
            );
            ?>

        </div>


        <!-- LOGOUT -->

        <a
            href="logout.php"
            class="icon-btn"
            aria-label="<?= htmlspecialchars(__('sign_out')) ?>"
        >

            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >

                <path
                    d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"
                />

                <path
                    d="M16 17l5-5-5-5M21 12H9"
                />

            </svg>

        </a>

    </div>

</header>


<!-- ============================================
     GAME BAR
============================================ -->

<div class="game-bar">

    <div class="game-title">
        <?= htmlspecialchars($game['title']) ?>
    </div>

    <a
        href="game.php?id=<?= urlencode($game_id) ?>"
        class="back-btn"
    >

        <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >

            <path
                d="M19 12H5M12 19l-7-7 7-7"
            />

        </svg>

        <?= htmlspecialchars(__('back', 'Back')) ?>

    </a>

</div>


<!-- ============================================
     GODOT GAME
============================================ -->

<div class="game-container">

    <iframe
        id="gameFrame"
        class="game-frame"
        src="<?= htmlspecialchars($game['src']) ?>"
        title="<?= htmlspecialchars($game['title']) ?>"
        allow="autoplay; fullscreen"
        allowfullscreen
    ></iframe>

</div>


<!-- ============================================
     SCORE TOAST
============================================ -->

<div
    class="score-toast"
    id="scoreToast"
></div>


<!-- ============================================
     WEBSITE BACKGROUND MUSIC
============================================ -->

<audio
    id="bgMusic"
    loop
    preload="auto"
>

    <source
        src="/after-class/assets/audio/theme.mp3"
        type="audio/mpeg"
    >

</audio>


<!-- ============================================
     SETTINGS PANEL
============================================ -->

<?php

require_once
    __DIR__
    . '/../includes/settings_panel.php';

?>


<!-- MUSIC -->

<script
    src="js/music.js?v=<?= time() ?>"
></script>


<!-- SETTINGS -->

<script
    src="js/settings.js?v=<?= time() ?>"
></script>


<!-- ============================================
     GODOT COMMUNICATION
============================================ -->

<script>

const GAME_ID =
    <?= json_encode($game_id) ?>;

const toast =
    document.getElementById('scoreToast');

const gameFrame =
    document.getElementById('gameFrame');

let scoreIsSaving = false;


// ============================================
// RECEIVE MESSAGE FROM GODOT
// ============================================

window.addEventListener(
    'message',
    async function (event) {

        if (
            event.origin !==
            window.location.origin
        ) {
            return;
        }

        const data = event.data;

        if (
            !data ||
            typeof data !== 'object'
        ) {
            return;
        }


        // GODOT READY

        if (data.type === 'gameReady') {

            console.log(
                'Godot game ready.'
            );

            return;
        }


        // GAME OVER

        if (data.type !== 'gameOver') {
            return;
        }

        if (scoreIsSaving) {
            return;
        }

        console.log(
            'Game over received:',
            data
        );

        const payload = {

            game_id:
                GAME_ID,

            score:
                Number(data.score) || 0,

            duration:
                Number(data.duration) || 0,

            level:
                Number(data.level) || 1,

            completed:
                Number(data.completed) || 0

        };

        await saveScoreWithRetry(
            payload
        );

    }
);


// ============================================
// SAVE SCORE
// ============================================

async function saveScoreWithRetry(
    payload
) {

    const MAX_RETRIES = 3;

    let attempt = 0;

    scoreIsSaving = true;


    while (
        attempt < MAX_RETRIES
    ) {

        attempt++;


        showToast(

            attempt === 1
                ? 'Saving score...'
                : `Retrying save... (${attempt}/${MAX_RETRIES})`,

            '#3498db'

        );


        try {

            const response =
                await fetch(
                    'save_score.php',
                    {

                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body:
                            JSON.stringify(
                                payload
                            )

                    }
                );


            if (
                response.status === 503 &&
                attempt < MAX_RETRIES
            ) {

                await sleep(
                    1000 * attempt
                );

                continue;
            }


            const result =
                await response.json();


            if (result.success) {

                const earnedXP =
                    result.xp_earned
                    ?? 0;


                showToast(

                    `✓ Score saved: ${payload.score} (+${earnedXP} XP)`,

                    '#2ecc71',

                    '#07130b'

                );


                setTimeout(
                    function () {

                        window.location.href =
                            'dashboard.php';

                    },
                    2000
                );

                return;
            }


            throw new Error(
                result.error
                || 'Unable to save score.'
            );

        }

        catch (error) {

            console.error(
                `Attempt ${attempt} failed:`,
                error
            );


            if (
                attempt >= MAX_RETRIES
            ) {

                scoreIsSaving = false;


                showToast(
                    '⚠ Could not save score.',
                    '#d13639'
                );


                setTimeout(
                    function () {

                        const retry =
                            confirm(
                                'We could not save your score. Retry?'
                            );


                        if (retry) {

                            saveScoreWithRetry(
                                payload
                            );

                        }

                    },
                    1200
                );

                return;
            }


            await sleep(
                1000 * attempt
            );

        }

    }


    scoreIsSaving = false;
}


// ============================================
// SHOW TOAST
// ============================================

function showToast(
    message,
    backgroundColor,
    textColor = 'white'
) {

    toast.textContent =
        message;

    toast.style.background =
        backgroundColor;

    toast.style.color =
        textColor;

    toast.classList.add(
        'show'
    );
}


// ============================================
// SLEEP
// ============================================

function sleep(ms) {

    return new Promise(
        function (resolve) {

            setTimeout(
                resolve,
                ms
            );

        }
    );
}

</script>


<!-- PWA -->

<script
    src="js/pwa.js?v=<?= time() ?>"
></script>


</body>
</html>