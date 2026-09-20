// ============================================
// CORESYNC 
// Complete version with Report Modal support
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const tiles     = document.querySelectorAll('.game-tile');
    const playBtn   = document.getElementById('playBtn');
    const actionBar = document.getElementById('actionBar');
    const games     = window.GAMES || [];

    let currentIndex = 0;
    let autoRotateTimer = null;
    let currentGameId = null;

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

        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('show'));
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }

    // ========================================
    // SELECT A GAME
    // ========================================
    function selectGame(index, userInitiated = false) {
        if (index < 0 || index >= tiles.length) return;
        currentIndex = index;

        tiles.forEach((t, i) => t.classList.toggle('active', i === index));

        const game = games[index];
        if (!game) return;

        if (playBtn) {
            playBtn.href = `game.php?id=${encodeURIComponent(game.id)}`;
        }

        if (actionBar) {
            actionBar.classList.add('visible');
        }

        if (userInitiated) restartAutoRotate();
    }

    // ========================================
    // AUTO-ROTATE
    // ========================================
    function restartAutoRotate() {
        if (autoRotateTimer) clearTimeout(autoRotateTimer);
        autoRotateTimer = setTimeout(() => {
            const next = (currentIndex + 1) % tiles.length;
            selectGame(next);
        }, 8000);
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

    // ========================================
    // REPORT ISSUE MODAL
    // ========================================
    const reportModal = document.getElementById('reportModal');
    const reportBackdrop = document.getElementById('reportBackdrop');
    const reportClose = document.getElementById('reportClose');
    const reportCancel = document.getElementById('reportCancel');
    const reportForm = document.getElementById('reportForm');
    const reportSubject = document.getElementById('reportSubject');
    const reportDescription = document.getElementById('reportDescription');
    const reportSeverity = document.getElementById('reportSeverity');
    const reportSubjectError = document.getElementById('reportSubjectError');
    const reportDescriptionError = document.getElementById('reportDescriptionError');
    const reportSubmit = document.getElementById('reportSubmit');

    function openReportModal(gameTitle) {
        if (!reportModal) {
            console.error('Report modal element not found');
            showToast('Report feature unavailable', 'error');
            return;
        }

        currentGameId = games[currentIndex]?.id || null;

        reportForm.reset();
        reportSubjectError.textContent = '';
        reportDescriptionError.textContent = '';
        reportSubject.classList.remove('error');
        reportDescription.classList.remove('error');

        // Prefill subject with game name
        reportSubject.value = gameTitle + ' — ';

        reportModal.classList.add('open');
        reportModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            reportSubject.focus();
            reportSubject.setSelectionRange(reportSubject.value.length, reportSubject.value.length);
        }, 100);
    }

    function closeReportModal() {
        if (!reportModal) return;
        reportModal.classList.remove('open');
        reportModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (reportClose) reportClose.addEventListener('click', closeReportModal);
    if (reportCancel) reportCancel.addEventListener('click', closeReportModal);
    if (reportBackdrop) reportBackdrop.addEventListener('click', closeReportModal);

    if (reportForm) {
        reportForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const subject = reportSubject.value.trim();
            const description = reportDescription.value.trim();
            const severity = reportSeverity.value;

            let valid = true;

            if (subject.length < 5) {
                reportSubjectError.textContent = 'Subject must be at least 5 characters.';
                reportSubject.classList.add('error');
                valid = false;
            }

            if (description.length < 10) {
                reportDescriptionError.textContent = 'Description must be at least 10 characters.';
                reportDescription.classList.add('error');
                valid = false;
            }

            if (!valid) return;

            const originalHTML = reportSubmit.innerHTML;
            reportSubmit.disabled = true;
            reportSubmit.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
                Sending...
            `;

            try {
                const res = await fetch('submit_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subject: subject,
                        description: description,
                        severity: severity,
                        game_id: currentGameId
                    })
                });

                const result = await res.json();

                if (result.success) {
                    closeReportModal();
                    showToast('✓ Report sent to admin. Thank you!', 'success');
                } else {
                    throw new Error(result.error || 'Failed to submit');
                }
            } catch (err) {
                console.error('Report failed:', err);
                showToast('⚠ Could not send report. Please try again.', 'error');
            } finally {
                reportSubmit.disabled = false;
                reportSubmit.innerHTML = originalHTML;
            }
        });
    }

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

        document.addEventListener('click', (e) => {
            if (!moreMenu.contains(e.target) && e.target !== moreBtn) {
                toggleMoreMenu(false);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') toggleMoreMenu(false);
        });

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
                openInfoModal();
                break;

            case 'share':
                const shareUrl = `${window.location.origin}${window.location.pathname.replace('dashboard.php', 'game.php')}?id=${encodeURIComponent(game.id)}`;
                navigator.clipboard.writeText(shareUrl)
                    .then(() => showToast('Link copied to clipboard', 'success'))
                    .catch(() => showToast('Could not copy link', 'error'));
                break;

            case 'report':
                openReportModal(gameName);
                break;

            case 'remove':
                showToast(`"${gameName}" removed from library`, 'error');
                break;

            default:
                console.warn('Unknown action:', action);
        }
    }

    // ========================================
    // TILE INTERACTIONS
    // ========================================
    tiles.forEach((tile, i) => {
        tile.addEventListener('click', () => selectGame(i, true));
        tile.addEventListener('mouseenter', () => selectGame(i, true));
    });

    // ========================================
    // KEYBOARD NAVIGATION
    // ========================================
    document.addEventListener('keydown', (e) => {
        if (e.target.matches('input, textarea, select')) return;

        if (e.key === 'Escape') {
            if (reportModal?.classList.contains('open')) {
                closeReportModal();
                return;
            }
            if (infoModal?.classList.contains('open')) {
                closeInfoModal();
                return;
            }
        }

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

    console.log('✓ dashboard.js loaded —', tiles.length, 'tiles,', games.length, 'games');
});