// ============================================
// CORESYNC - Settings Drawer
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const overlay  = document.getElementById('settingsOverlay');
    const backdrop = document.getElementById('settingsBackdrop');
    const closeBtn = document.getElementById('settingsClose');
    const gearBtn  = document.getElementById('settingsBtn')
                  || document.querySelector('button[aria-label="Settings"]');

    if (!overlay || !gearBtn) return;

    // ========================================
    // OPEN / CLOSE DRAWER
    // ========================================
    function openSettings() {
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeSettings() {
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    gearBtn.addEventListener('click', openSettings);
    if (closeBtn) closeBtn.addEventListener('click', closeSettings);
    if (backdrop) backdrop.addEventListener('click', closeSettings);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeSettings();
    });

    // ========================================
    // LOAD SAVED PREFERENCES
    // ========================================
    const toggleIds = [
        'prefMotion',
        'prefAutoplay',
        'prefMute',
        'prefMusic',
        'prefEmail',
        'prefReminders'
    ];

    toggleIds.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;

        const saved = localStorage.getItem(id);
        if (saved !== null) el.checked = (saved === 'true');

        el.addEventListener('change', () => {
            localStorage.setItem(id, el.checked);
            applyAllPreferences();
        });
    });

    // Volume slider
    const vol = document.getElementById('prefVolume');
    const volVal = document.getElementById('volumeValue');
    if (vol && volVal) {
        const saved = localStorage.getItem('prefVolume');
        if (saved !== null) {
            vol.value = saved;
            volVal.textContent = saved + '%';
        }
        vol.addEventListener('input', () => {
            volVal.textContent = vol.value + '%';
            localStorage.setItem('prefVolume', vol.value);
            applyAllPreferences();
        });
    }

    // ========================================
    // APPLY ALL PREFERENCES
    // ========================================
    function applyAllPreferences() {
        applyReduceMotion();
        applyVideoPrefs();
        applyMusicPrefs();
    }

    // ========================================
    // REDUCE MOTION
    // ========================================
    function applyReduceMotion() {
        const motion = document.getElementById('prefMotion');
        if (!motion) return;

        if (motion.checked) {
            document.documentElement.classList.add('reduce-motion');
            document.querySelectorAll('.bg-layer video').forEach(v => v.pause());
        } else {
            document.documentElement.classList.remove('reduce-motion');
            document.querySelectorAll('.bg-layer video').forEach(v => {
                v.play().catch(() => {});
            });
        }
    }

    // ========================================
    // VIDEO PREFERENCES
    // ========================================
    function applyVideoPrefs() {
        const mute     = document.getElementById('prefMute');
        const autoplay = document.getElementById('prefAutoplay');
        const volume   = document.getElementById('prefVolume');

        document.querySelectorAll('video').forEach(v => {
            if (mute) v.muted = mute.checked;
            if (volume) v.volume = Math.max(0, Math.min(1, volume.value / 100));
            if (autoplay) {
                if (autoplay.checked) v.play().catch(() => {});
                else v.pause();
            }
        });
    }

    // ========================================
    // MUSIC PREFERENCES
    // ========================================
    function applyMusicPrefs() {
        const music = document.getElementById('bgMusic');
        if (!music) return;

        const mute         = document.getElementById('prefMute');
        const autoplay     = document.getElementById('prefAutoplay');
        const volume       = document.getElementById('prefVolume');
        const musicToggle  = document.getElementById('prefMusic');

        // Dedicated music toggle — if off, pause and exit
        if (musicToggle && !musicToggle.checked) {
            music.pause();
            return;
        }

        // Mute (inherits from global mute)
        if (mute) music.muted = mute.checked;

        // Volume (music plays at 60% of master volume for background feel)
        if (volume) {
            music.volume = Math.max(0, Math.min(1, (volume.value / 100) * 0.6));
        }

        // Play / pause
        if (autoplay && autoplay.checked) {
            music.play().catch(() => {
                // Autoplay blocked — wait for first user interaction
                const unlock = () => {
                    music.play().catch(() => {});
                    document.removeEventListener('click', unlock);
                    document.removeEventListener('keydown', unlock);
                };
                document.addEventListener('click', unlock, { once: true });
                document.addEventListener('keydown', unlock, { once: true });
            });
        } else {
            music.pause();
        }
    }

    // ========================================
    // APPLY ON LOAD (with retries for late-loading media)
    // ========================================
    applyAllPreferences();
    setTimeout(applyAllPreferences, 500);
    setTimeout(applyAllPreferences, 1500);
});