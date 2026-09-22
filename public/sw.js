// public/sw.js
// EqualPath Service Worker — offline cache + install support

const CACHE_VERSION = 'equalpath-v1.0.0';
const RUNTIME_CACHE = 'equalpath-runtime-v1';

// Files that are cached on first install (app shell)
const PRECACHE_URLS = [
    '/after-class/public/',
    '/after-class/public/index.php',
    '/after-class/public/dashboard.php',
    '/after-class/public/library.php',
    '/after-class/public/leaderboard.php',
    '/after-class/public/profile.php',
    '/after-class/public/css/style.css',
    '/after-class/public/css/dashboard.css',
    '/after-class/public/css/settings.css',
    '/after-class/public/js/script.js',
    '/after-class/public/js/dashboard.js',
    '/after-class/public/js/settings.js',
    '/after-class/public/js/qr-login.js',
    '/after-class/assets/icons/icon-192.png',
    '/after-class/assets/icons/icon-512.png'
];

// ============================================
// INSTALL — cache the app shell
// ============================================
self.addEventListener('install', (event) => {
    console.log('📦 Service Worker installing...');

    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => {
                return Promise.all(
                    PRECACHE_URLS.map(url =>
                        cache.add(url).catch(err =>
                            console.warn('⚠ Failed to cache:', url, err.message)
                        )
                    )
                );
            })
            .then(() => self.skipWaiting())
    );
});

// ============================================
// ACTIVATE — clean up old caches
// ============================================
self.addEventListener('activate', (event) => {
    console.log('✅ Service Worker activated');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter(name => name !== CACHE_VERSION && name !== RUNTIME_CACHE)
                        .map(name => {
                            console.log('🗑 Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// ============================================
// FETCH — network-first for dynamic, cache-first for static
// ============================================
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip cross-origin requests (CDN, etc.)
    if (url.origin !== location.origin) return;

    // Skip API/PHP POST handlers — they should always hit the network
    const dynamicPaths = [
        'qr_check.php',
        'qr_generate.php',
        'qr_verify.php',
        'save_score.php',
        'update_preference.php',
        'authenticate.php',
        'resolve_report.php',
        'submit_report.php',
        'toggle_favorite.php'
    ];
    if (dynamicPaths.some(p => url.pathname.endsWith(p))) {
        return; // Let the network handle it
    }

    // For HTML pages and static assets — network-first, fall back to cache
    event.respondWith(
        fetch(request)
            .then((response) => {
                // Cache a copy of successful responses (static assets)
                if (response.ok && (
                    url.pathname.endsWith('.css') ||
                    url.pathname.endsWith('.js') ||
                    url.pathname.endsWith('.png') ||
                    url.pathname.endsWith('.jpg') ||
                    url.pathname.endsWith('.svg') ||
                    url.pathname.endsWith('.woff2')
                )) {
                    const clone = response.clone();
                    caches.open(RUNTIME_CACHE).then(cache => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => {
                // Offline → try cache
                return caches.match(request).then(cached => {
                    if (cached) return cached;

                    // If the request is for a page, fall back to the cached home
                    if (request.headers.get('accept')?.includes('text/html')) {
                        return caches.match('/after-class/public/dashboard.php')
                            .then(page => page || new Response(
                                '<h1>You are offline</h1><p>Reconnect to continue.</p>',
                                { headers: { 'Content-Type': 'text/html' } }
                            ));
                    }
                    return new Response('Offline', { status: 503 });
                });
            })
    );
});

// ============================================
// MESSAGE HANDLER — for "skipWaiting" from the page
// ============================================
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});