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

// Same catalog
$games = [
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
    [
        'id'          => 'after-class',
        'title'       => 'AFTER CLASS',
        'subtitle'    => 'A pixel art school adventure',
        'type'        => 'video',
        'thumbnail'   => '/after-class/assets/games/afterclass-thumb.jpg',
        'accent'      => '#f97316',
        'genre'       => 'Casual',
        'release'     => '2026-02-01',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library | CoreSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/dashboard.css">
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
            <a href="library.php" class="nav-tab active" title="Library">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
            </a>
            <a href="profile.php" class="nav-tab" title="Profile">
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
        <h1>Library</h1>
        <span class="user-greeting"><?= count($games) ?> games available</span>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label>Genre</label>
            <select class="filter-select" id="genreFilter">
                <option value="all">All Genres</option>
                <option value="Adventure">Adventure</option>
                <option value="Casual">Casual</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Sort</label>
            <select class="filter-select" id="sortSelect">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="az">A → Z</option>
                <option value="za">Z → A</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Search</label>
            <input type="text" class="filter-input" id="searchInput" placeholder="Search games...">
        </div>
    </div>

    <!-- Game grid -->
    <div class="library-grid" id="libraryGrid">
        <?php foreach ($games as $g): ?>
            <a href="game.php?id=<?= urlencode($g['id']) ?>"
               class="library-card"
               data-genre="<?= htmlspecialchars($g['genre']) ?>"
               data-title="<?= htmlspecialchars($g['title']) ?>"
               data-release="<?= htmlspecialchars($g['release']) ?>">
                <div class="library-thumb" style="background-image:url('<?= htmlspecialchars($g['thumbnail']) ?>');">
                    <div class="library-overlay"></div>
                    <div class="library-badge" style="background: <?= htmlspecialchars($g['accent']) ?>;">
                        <?= htmlspecialchars($g['genre']) ?>
                    </div>
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
        <p>No games match your filters.</p>
    </div>

</main>

<!-- ============ STATIC BACKGROUND ============ -->
<div class="bg-layer" id="bgLayer"></div>

<script>
    // ========================================
    // FILTER & SORT LOGIC
    // ========================================
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

        // Sort visible cards
        visible.sort((a, b) => {
            if (sort === 'az') return a.dataset.title.localeCompare(b.dataset.title);
            if (sort === 'za') return b.dataset.title.localeCompare(a.dataset.title);
            if (sort === 'newest') return new Date(b.dataset.release) - new Date(a.dataset.release);
            if (sort === 'oldest') return new Date(a.dataset.release) - new Date(b.dataset.release);
            return 0;
        });

        // Re-append in sorted order
        visible.forEach(card => grid.appendChild(card));

        empty.style.display = visible.length === 0 ? 'block' : 'none';
    }

    genreFilter.addEventListener('change', applyFilters);
    sortSelect.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', applyFilters);
</script>
<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>
<script src="js/settings.js"></script>
<div class="bg-layer" id="bgLayer">
    <!-- Background music -->
<audio id="bgMusic" loop preload="auto">
    <source src="/after-class/assets/audio/theme.mp3" type="audio/mpeg">
</audio>
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>
</body>
</html>