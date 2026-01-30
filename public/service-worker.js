const CACHE_VERSION = 'v4';
const CACHE_NAME = `pterodactyl-assets-${CACHE_VERSION}`;
const OFFLINE_CACHE_NAME = `pterodactyl-offline-${CACHE_VERSION}`;

let pwaConfig = {
    enabled: false,
    offline_enabled: false,
    offline_page_url: null,
    cache_strategy: 'cache-first',
    cache_api_requests: false,
    precache_assets: true
};

const ASSET_PATTERNS = [
    /\/assets\/.*\.[a-f0-9]{8,}\.(js|css)$/i,
    /\/logo\/.*\.(png|jpg|jpeg|svg|webp|gif)$/i,
    /\/favicons\/.*\.(png|ico|svg)$/i,
    /\/themes\/.*\.(png|jpg|jpeg|svg|webp|gif)$/i,
];

const API_PATTERNS = [
    /\/api\/client/i,
];

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'PWA_CONFIG') {
        pwaConfig = { ...pwaConfig, ...event.data.config };
    }
});

self.addEventListener('install', (event) => {
    self.skipWaiting();

    if (pwaConfig.offline_enabled && pwaConfig.offline_page_url) {
        event.waitUntil(
            caches.open(OFFLINE_CACHE_NAME).then((cache) => {
                return cache.add(pwaConfig.offline_page_url).catch(() => {
                    console.log('[SW] Failed to cache offline page');
                });
            })
        );
    }
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => {
                        return (name.startsWith('pterodactyl-assets-') && name !== CACHE_NAME) ||
                            (name.startsWith('pterodactyl-offline-') && name !== OFFLINE_CACHE_NAME);
                    })
                    .map((name) => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET') {
        return;
    }

    const shouldCache = ASSET_PATTERNS.some(pattern => pattern.test(url.pathname));

    const isApiRequest = API_PATTERNS.some(pattern => pattern.test(url.pathname));

    if (isApiRequest && !pwaConfig.cache_api_requests) {
        return;
    }

    if (!shouldCache && !isApiRequest) {
        if (event.request.mode === 'navigate' && pwaConfig.offline_enabled && pwaConfig.offline_page_url) {
            event.respondWith(
                fetch(event.request).catch(() => {
                    return caches.match(pwaConfig.offline_page_url);
                })
            );
        }
        return;
    }

    const strategy = pwaConfig.cache_strategy || 'cache-first';

    if (strategy === 'network-first' || isApiRequest) {
        event.respondWith(
            fetch(event.request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return networkResponse;
            }).catch(() => {
                return caches.match(event.request);
            })
        );
    } else {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }

                return fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();

                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                    }

                    return networkResponse;
                });
            })
        );
    }
});
