// public/js/pwa.js
// Registers the Service Worker + handles the "Install app" prompt

(function () {
    'use strict';

    // ============================================
    // 1. REGISTER SERVICE WORKER
    // ============================================
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/after-class/public/sw.js', {
                scope: '/after-class/public/'
            })
            .then((registration) => {
                console.log('✅ Service Worker registered:', registration.scope);

                // Check for updates periodically
                setInterval(() => registration.update(), 60 * 60 * 1000); // every hour
            })
            .catch((err) => {
                console.warn('❌ Service Worker registration failed:', err);
            });
        });
    }

    // ============================================
    // 2. INSTALL PROMPT
    // ============================================
    let deferredPrompt = null;

    // Fires when the browser is ready to show the install prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Show our custom install button
        showInstallButton();
    });

    // Fires when the user actually installs (from our button or the browser UI)
    window.addEventListener('appinstalled', () => {
        console.log('🎉 EqualPath installed as an app!');
        hideInstallButton();
        deferredPrompt = null;

        // Optional: log to your backend
        fetch('/after-class/public/log_install.php', { method: 'POST' }).catch(() => {});
    });

    // ============================================
    // 3. CUSTOM INSTALL BUTTON
    // ============================================
    function showInstallButton() {
        // Don't show if already installed
        if (window.matchMedia('(display-mode: standalone)').matches) return;
        if (window.navigator.standalone === true) return;

        // Don't show if already exists
        if (document.getElementById('pwaInstallBtn')) return;

        const btn = document.createElement('button');
        btn.id = 'pwaInstallBtn';
        btn.className = 'pwa-install-btn';
        btn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <span>Install App</span>
        `;

        btn.addEventListener('click', async () => {
            if (!deferredPrompt) {
                // Fallback: show instructions
                alert(
                    'To install EqualPath:\n\n' +
                    '📱 On iPhone: Tap Share → "Add to Home Screen"\n' +
                    '💻 On Chrome: Click the ⋮ menu → "Install EqualPath"\n' +
                    '💻 On Edge: Click the install icon in the address bar'
                );
                return;
            }

            // Show the native install prompt
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;

            if (outcome === 'accepted') {
                console.log('✅ User accepted install');
            } else {
                console.log('❌ User dismissed install');
            }

            deferredPrompt = null;
            hideInstallButton();
        });

        // Append to the nav-right area, or fallback to body
        const navRight = document.querySelector('.nav-right');
        if (navRight) {
            navRight.insertBefore(btn, navRight.firstChild);
        } else {
            document.body.appendChild(btn);
        }
    }

    function hideInstallButton() {
        const btn = document.getElementById('pwaInstallBtn');
        if (btn) btn.remove();
    }

    // ============================================
    // 4. iOS DETECTION — Show instructions
    // ============================================
    // iOS Safari doesn't fire beforeinstallprompt, so we detect and show a hint
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isInStandaloneMode = window.matchMedia('(display-mode: standalone)').matches ||
                               window.navigator.standalone === true;

    if (isIOS && !isInStandaloneMode) {
        // Show iOS-specific instructions after 5 seconds
        setTimeout(() => {
            if (document.getElementById('pwaInstallBtn')) return;

            const btn = document.createElement('button');
            btn.id = 'pwaInstallBtn';
            btn.className = 'pwa-install-btn';
            btn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span>Install App</span>
            `;
            btn.addEventListener('click', () => {
                alert(
                    '📱 To install EqualPath on iPhone:\n\n' +
                    '1. Tap the Share button (□ with ↑ arrow)\n' +
                    '2. Scroll down and tap "Add to Home Screen"\n' +
                    '3. Tap "Add" in the top right\n\n' +
                    'The EqualPath icon will appear on your home screen.'
                );
            });

            const navRight = document.querySelector('.nav-right');
            if (navRight) navRight.insertBefore(btn, navRight.firstChild);
            else document.body.appendChild(btn);
        }, 5000);
    }

    console.log('📱 PWA support loaded');
})();