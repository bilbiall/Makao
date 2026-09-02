// Minimal service worker - enough to satisfy PWA installability criteria, not a full
// offline strategy. Cache-first for static assets (versioned by Vite's own filename
// hashing, so stale-cache is a non-issue), network-first for everything else (app
// pages are session/role-specific and must always be fresh).
const STATIC_CACHE = 'makao-static-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== STATIC_CACHE).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') {
        return;
    }

    const isStaticAsset = request.destination === 'style'
        || request.destination === 'script'
        || request.destination === 'font'
        || request.destination === 'image';

    if (isStaticAsset) {
        event.respondWith(
            caches.open(STATIC_CACHE).then((cache) =>
                cache.match(request).then((cached) => {
                    const fetchPromise = fetch(request).then((response) => {
                        cache.put(request, response.clone());
                        return response;
                    });
                    return cached || fetchPromise;
                })
            )
        );
        return;
    }

    // Network-first for everything else; no offline fallback page for v1.
    event.respondWith(fetch(request).catch(() => caches.match(request)));
});
