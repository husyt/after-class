// ============================================
// CORESYNC - PS5 Dashboard Logic
// Static background on dashboard
// Videos play on game.php (separate page)
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const tiles     = document.querySelectorAll('.game-tile');
    const playBtn   = document.getElementById('playBtn');
    const actionBar = document.getElementById('actionBar');
    const games     = window.GAMES || [];

    let currentIndex = 0;
    let autoRotateTimer = null;

    // ========================================
    // SELECT A GAME
    // Only updates the highlighted tile and the
    // Play button link. Never touches the background.
    // ========================================
    function selectGame(index, userInitiated = false) {
        if (index < 0 || index >= tiles.length) return;
        currentIndex = index;

        // Highlight the active tile
        tiles.forEach((t, i) => t.classList.toggle('active', i === index));

        const game = games[index];
        if (!game) return;

        // Update Play button to point to the correct game
        if (playBtn) {
            playBtn.href = `game.php?id=${encodeURIComponent(game.id)}`;
        }

        // Show the action bar
        if (actionBar) {
            actionBar.classList.add('visible');
        }

        if (userInitiated) restartAutoRotate();
    }

    // ========================================
    // AUTO-ROTATE between tiles every 8 seconds
    // ========================================
    function restartAutoRotate() {
        if (autoRotateTimer) clearTimeout(autoRotateTimer);
        autoRotateTimer = setTimeout(() => {
            const next = (currentIndex + 1) % tiles.length;
            selectGame(next);
        }, 8000);
    }

    // ========================================
    // TILE INTERACTIONS
    // ========================================
    tiles.forEach((tile, i) => {
        tile.addEventListener('click', () => selectGame(i, true));
        tile.addEventListener('mouseenter', () => selectGame(i, true));
    });

    // ========================================
    // KEYBOARD NAVIGATION (← → Enter)
    // ========================================
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            selectGame((currentIndex + 1) % tiles.length, true);
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            selectGame((currentIndex - 1 + tiles.length) % tiles.length, true);
        } else if (e.key === 'Enter') {
            const game = games[currentIndex];
            if (game) window.location.href = `game.php?id=${encodeURIComponent(game.id)}`;
        }
    });

    // ========================================
    // INITIAL LOAD
    // ========================================
    if (tiles.length > 0) {
        selectGame(0);
        restartAutoRotate();
    }

        // ========================================
    // INFO MODAL
    // ========================================
    const infoModal  = document.getElementById('infoModal');
    const infoBtn    = document.getElementById('infoBtn');
    const infoClose  = document.getElementById('infoClose');
    const infoBackdrop = document.getElementById('infoBackdrop');

    function openInfoModal() {
        if (!infoModal) return;
        infoModal.classList.add('open');
        infoModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeInfoModal() {
        if (!infoModal) return;
        infoModal.classList.remove('open');
        infoModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (infoBtn) infoBtn.addEventListener('click', openInfoModal);
    if (infoClose) infoClose.addEventListener('click', closeInfoModal);
    if (infoBackdrop) infoBackdrop.addEventListener('click', closeInfoModal);

    // Escape key closes the modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && infoModal?.classList.contains('open')) {
            closeInfoModal();
        }  
    });

        // ========================================
    // MORE DROPDOWN MENU
    // ========================================
    const moreBtn  = document.getElementById('moreBtn');
    const moreMenu = document.getElementById('moreMenu');

    if (moreBtn && moreMenu) {
        function toggleMoreMenu(open) {
            const shouldOpen = typeof open === 'boolean' ? open : !moreMenu.classList.contains('open');
            moreMenu.classList.toggle('open', shouldOpen);
            moreBtn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        }

        moreBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMoreMenu();
        });

        // Close when clicking anywhere else
        document.addEventListener('click', (e) => {
            if (!moreMenu.contains(e.target) && e.target !== moreBtn) {
                toggleMoreMenu(false);
            }
        });

        // Escape key closes menu
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') toggleMoreMenu(false);
        });

        // Handle menu item clicks
        moreMenu.querySelectorAll('.more-item').forEach(item => {
            item.addEventListener('click', () => {
                const action = item.dataset.action;
                const game = games[currentIndex];
                handleMoreAction(action, game);
                toggleMoreMenu(false);
            });
        });
    }

    // ========================================
    // ACTION HANDLERS
    // ========================================
    function handleMoreAction(action, game) {
        if (!game) return;
        const gameName = game.title;

        switch (action) {
            case 'favorite':
                showToast(`Added "${gameName}" to favorites`, 'success');
                break;

            case 'details':
                // Open the Info modal if it exists
                const infoModal = document.getElementById('infoModal');
                if (infoModal) {
                    infoModal.classList.add('open');
                    infoModal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                } else {
                    showToast(`${gameName} — more details coming soon`, 'info');
                }
                break;

            case 'share':
                const shareUrl = `${window.location.origin}${window.location.pathname.replace('dashboard.php', 'game.php')}?id=${encodeURIComponent(game.id)}`;
                navigator.clipboard.writeText(shareUrl)
                    .then(() => showToast('Link copied to clipboard', 'success'))
                    .catch(() => showToast('Could not copy link', 'error'));
                break;

            case 'report':
                showToast(`Report submitted for "${gameName}"`, 'warning');
                break;

            case 'remove':
                showToast(`"${gameName}" removed from library`, 'error');
                break;
        }
    }

    // ========================================
    // TOAST SYSTEM
    // ========================================
    function showToast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const icons = {
            success: '✓',
            info: 'ℹ',
            warning: '⚠',
            error: '✕',
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || '•'}</div>
            <div>${message}</div>
        `;

        container.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('show'));
        });

        // Auto-remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }
});
