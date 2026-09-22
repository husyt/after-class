// public/js/music.js
// Persistent background music that continues across page navigations

(function () {
    'use strict';

    const STORAGE_KEY = 'bgMusicState';

    function initMusic() {
        const bgMusic = document.getElementById('bgMusic');

        if (!bgMusic) {
            console.warn('🎵 No #bgMusic element found on this page');
            return;
        }

        // ============================================
        // RESTORE STATE FROM PREVIOUS PAGE
        // ============================================
        let saved = null;
        try {
            saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null');
        } catch (e) {
            saved = null;
        }

        // Set volume (from saved state or default 70%)
        const volumePref = localStorage.getItem('prefVolume') || 70;
        bgMusic.volume = volumePref / 100;

        // Restore playback position
        if (saved && typeof saved.currentTime === 'number') {
            // Only restore if saved less than 30 seconds ago (in case user was away)
            const ageMs = Date.now() - (saved.savedAt || 0);
            if (ageMs < 30000) {
                bgMusic.currentTime = saved.currentTime;
                console.log('🎵 Resuming music at', saved.currentTime.toFixed(1), 's');
            }
        }

        // ============================================
        // START PLAYING (respecting autoplay policy)
        // ============================================
        const wasPlaying = saved ? saved.playing : true;

        if (wasPlaying) {
            const playPromise = bgMusic.play();
            if (playPromise !== undefined) {
                playPromise.catch(err => {
                    console.log('🎵 Autoplay blocked — will start on first click', err.message);
                    // Try again on first user interaction
                    const resume = () => {
                        bgMusic.play().catch(() => {});
                        document.removeEventListener('click', resume);
                        document.removeEventListener('keydown', resume);
                        document.removeEventListener('touchstart', resume);
                    };
                    document.addEventListener('click', resume, { once: true });
                    document.addEventListener('keydown', resume, { once: true });
                    document.addEventListener('touchstart', resume, { once: true });
                });
            }
        }

        // ============================================
        // SAVE STATE BEFORE LEAVING THE PAGE
        // ============================================
        function saveState() {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                    currentTime: bgMusic.currentTime,
                    playing:     !bgMusic.paused,
                    savedAt:     Date.now()
                }));
            } catch (e) {
                // sessionStorage might be full or disabled
            }
        }

        // Save on every navigation event
        window.addEventListener('beforeunload', saveState);
        window.addEventListener('pagehide', saveState);

        // Also save periodically (every 1 second) so we always have a recent position
        setInterval(() => {
            if (!bgMusic.paused) saveState();
        }, 1000);

        console.log('🎵 Music system initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMusic);
    } else {
        initMusic();
    }
})();