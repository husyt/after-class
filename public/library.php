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

// ============================================
// FETCH USER FAVORITES
// ============================================
$stmt = $pdo->prepare("SELECT game_id FROM user_favorites WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$favorites = array_column($stmt->fetchAll(), 'game_id');

// ============================================
// GAME CATALOG
// ============================================
$games = [
    [
        'id'          => 'after-class',
        'title'       => 'AFTER CLASS',
        'subtitle'    => 'An inclusive education adventure (EqualPath)',
        'type'        => 'video',
        'thumbnail'   => '/after-class/assets/games/afterclass-thumb.jpg',
        'accent'      => '#f97316',
        'genre'       => 'Educational',
        'release'     => '2026-02-01',
    ],
    [
        'id'          => 'lex-obscura',
        'title'       => 'LEX OBSCURA',
        'subtitle'    => 'A dark fantasy adventure',
        'type'        => 'video',
        'thumbnail'   => '/after-class/assets/games/lexobscura-thumb.jpg',
        'accent'      => '#7c3aed',
        'genre'       => 'Adventure',
        'release'     => '2026-01-15',
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('library') ?> | EqualPath</title>
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
            <a href="library.php" class="nav-tab active" title="<?= __('library') ?>">
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

<!-- ============ MAIN ============ -->
<main class="dashboard">

    <div class="section-head">
        <h1><?= __('library') ?></h1>
        <span class="user-greeting"><?= count($games) ?> <?= __('games_available') ?></span>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label><?= __('filter_genre') ?></label>
            <select class="filter-select" id="genreFilter">
                <option value="all"><?= __('all_genres') ?></option>
                <option value="Educational">Educational</option>
                <option value="Adventure">Adventure</option>
                <option value="Casual">Casual</option>
            </select>
        </div>
        <div class="filter-group">
            <label><?= __('filter_sort') ?></label>
            <select class="filter-select" id="sortSelect">
                <option value="newest"><?= __('newest_first') ?></option>
                <option value="oldest"><?= __('oldest_first') ?></option>
                <option value="az"><?= __('sort_az') ?></option>
                <option value="za"><?= __('sort_za') ?></option>
                <option value="favorites"><?= __('favorites_first') ?></option>
            </select>
        </div>
        <div class="filter-group">
            <label><?= __('filter_search') ?></label>
            <input type="text" class="filter-input" id="searchInput" placeholder="<?= __('search_placeholder') ?>">
        </div>
    </div>

    <!-- Game grid -->
    <div class="library-grid" id="libraryGrid">
        <?php foreach ($games as $g): ?>
            <?php $is_fav = in_array($g['id'], $favorites, true); ?>
            <a href="game.php?id=<?= urlencode($g['id']) ?>"
               class="library-card"
               data-id="<?= htmlspecialchars($g['id']) ?>"
               data-genre="<?= htmlspecialchars($g['genre']) ?>"
               data-title="<?= htmlspecialchars($g['title']) ?>"
               data-release="<?= htmlspecialchars($g['release']) ?>"
               data-favorited="<?= $is_fav ? '1' : '0' ?>">
                <div class="library-thumb" style="background-image:url('<?= htmlspecialchars($g['thumbnail']) ?>');">
                    <div class="library-overlay"></div>

                    <!-- Genre badge (top-left) -->
                    <div class="library-badge" style="background: <?= htmlspecialchars($g['accent']) ?>;">
                        <?= htmlspecialchars($g['genre']) ?>
                    </div>

                    <!-- Heart button (top-right) -->
                    <button class="library-heart <?= $is_fav ? 'active' : '' ?>" 
                            type="button"
                            data-game-id="<?= htmlspecialchars($g['id']) ?>"
                            aria-label="Toggle favorite"
                            title="<?= $is_fav ? __('remove_favorites') : __('add_favorites') ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                        </svg>
                    </button>
                </div>
                <div class="library-info">
                    <h3><?= htmlspecialchars($g['title']) ?></h3>
                    <p><?= htmlspecialchars($g['subtitle']) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <div class="library-empty" id="libraryEmpty" style="display:none;">
        <p><?= __('no_match') ?></p>
    </div>

</main>

<!-- ============ BACKGROUND ============ -->
<div class="bg-layer" id="bgLayer">
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>

<!-- Background music -->
<audio id="bgMusic" loop preload="auto">
    <source src="/after-class/assets/audio/theme.mp3" type="audio/mpeg">
</audio>

<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<!-- Favorites data for JS -->
<script>
    window.FAVORITES = <?= json_encode($favorites, JSON_UNESCAPED_SLASHES) ?>;
    window.TRANSLATIONS = {
        add_favorites:    '<?= __('add_favorites') ?>',
        remove_favorites: '<?= __('remove_favorites') ?>'
    };
</script>

<script src="js/settings.js?v=<?= time() ?>"></script>
<script>
// ========================================
// LIBRARY PAGE — FILTER, SORT, FAVORITES
// ========================================
document.addEventListener('DOMContentLoaded', () => {

    // -------- Filter & Sort --------
    const cards = document.querySelectorAll('.library-card');
    const genreFilter = document.getElementById('genreFilter');
    const sortSelect = document.getElementById('sortSelect');
    const searchInput = document.getElementById('searchInput');
    const grid = document.getElementById('libraryGrid');
    const empty = document.getElementById('libraryEmpty');

    function applyFilters() {
        const genre = genreFilter.value;
        const search = searchInput.value.toLowerCase().trim();
        const sort = sortSelect.value;

        let visible = [];

        cards.forEach(card => {
            const cardGenre = card.dataset.genre;
            const cardTitle = card.dataset.title.toLowerCase();
            const matchesGenre = (genre === 'all' || cardGenre === genre);
            const matchesSearch = (search === '' || cardTitle.includes(search));
            const show = matchesGenre && matchesSearch;
            card.style.display = show ? '' : 'none';
            if (show) visible.push(card);
        });

        // Sort
        visible.sort((a, b) => {
            if (sort === 'az') return a.dataset.title.localeCompare(b.dataset.title);
            if (sort === 'za') return b.dataset.title.localeCompare(a.dataset.title);
            if (sort === 'newest') return new Date(b.dataset.release) - new Date(a.dataset.release);
            if (sort === 'oldest') return new Date(a.dataset.release) - new Date(b.dataset.release);
            if (sort === 'favorites') {
                const aFav = a.dataset.favorited === '1';
                const bFav = b.dataset.favorited === '1';
                if (aFav === bFav) return 0;
                return aFav ? -1 : 1;
            }
            return 0;
        });

        // Re-append in sorted order
        visible.forEach(card => grid.appendChild(card));

        empty.style.display = visible.length === 0 ? 'block' : 'none';
    }

    genreFilter.addEventListener('change', applyFilters);
    sortSelect.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', applyFilters);

    // -------- Favorites --------
    const favorites = new Set(window.FAVORITES || []);
    const T = window.TRANSLATIONS || {};

    document.querySelectorAll('.library-heart').forEach(heart => {
        const gameId = heart.dataset.gameId;

        heart.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            try {
                const res = await fetch('toggle_favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ game_id: gameId })
                });
                const result = await res.json();

                if (!result.success) throw new Error(result.error || 'Failed');

                const card = heart.closest('.library-card');

                if (result.favorited) {
                    favorites.add(gameId);
                    heart.classList.add('active');
                    heart.title = T.remove_favorites || 'Remove from favorites';
                    if (card) card.dataset.favorited = '1';
                } else {
                    favorites.delete(gameId);
                    heart.classList.remove('active');
                    heart.title = T.add_favorites || 'Add to favorites';
                    if (card) card.dataset.favorited = '0';
                }

                // Pulse animation
                heart.classList.add('pulse');
                setTimeout(() => heart.classList.remove('pulse'), 400);

            } catch (err) {
                console.error('Toggle favorite failed:', err);
            }
        });
    });
});


</script>
<script src="js/music.js?v=<?= time() . rand() ?>"></script>
<script src="js/pwa.js?v=<?= time() . rand() ?>"></script>
</body>
</html>