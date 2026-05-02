const CACHE_VERSION = 'v2';
const APP_SHELL_CACHE = `doxa-shell-${CACHE_VERSION}`;
const RUNTIME_CACHE = `doxa-runtime-${CACHE_VERSION}`;
const STATIC_ASSETS_CACHE = `doxa-static-${CACHE_VERSION}`;

const APP_SHELL_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.webmanifest',
    '/favicon.ico',
    '/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(APP_SHELL_CACHE).then((cache) => cache.addAll(APP_SHELL_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => ![APP_SHELL_CACHE, RUNTIME_CACHE, STATIC_ASSETS_CACHE].includes(key))
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Skip livewire uploads and hot reloading
    if (url.pathname.includes('/livewire/upload-file') || url.pathname.includes('/hot')) {
        return;
    }

    // Navigation requests: Network-First with Offline Fallback
    if (request.mode === 'navigate') {
        event.respondWith(networkFirstPage(request));
        return;
    }

    // Static Assets: Stale-While-Revalidate
    if (isStaticAsset(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request, STATIC_ASSETS_CACHE));
        return;
    }

    // Media: Cache-First (with limit or just skip if too big, but here we try stale-while-revalidate)
    if (isMediaRequest(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request, RUNTIME_CACHE));
        return;
    }

    // Default: Network-First
    event.respondWith(networkFirst(request));
});

function isStaticAsset(pathname) {
    return pathname.startsWith('/build/') || 
           pathname.startsWith('/assets/') ||
           pathname.endsWith('.css') || 
           pathname.endsWith('.js') ||
           pathname.endsWith('.png') ||
           pathname.endsWith('.jpg') ||
           pathname.endsWith('.jpeg') ||
           pathname.endsWith('.svg') ||
           pathname.endsWith('.woff2');
}

function isMediaRequest(pathname) {
    return pathname.includes('/storage/sermons/') ||
           pathname.endsWith('.mp3') ||
           pathname.endsWith('.m4a') ||
           pathname.endsWith('.ogg') ||
           pathname.endsWith('.mp4');
}

async function networkFirstPage(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    try {
        const response = await fetch(request);
        cache.put(request, response.clone());
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        const offline = await caches.match('/offline.html');
        return offline || new Response('Offline', { status: 503, statusText: 'Offline' });
    }
}

async function networkFirst(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        return cached || new Response(null, { status: 504, statusText: 'Gateway Timeout' });
    }
}

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    const networkPromise = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    return cached || networkPromise;
}

// Push Notifications
self.addEventListener('push', (event) => {
    let data = { title: 'Doxa Church', body: 'New update available', icon: '/Img/doxa.PNG', badge: '/Img/doxa.PNG' };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/Img/doxa.PNG',
        badge: data.badge || '/Img/doxa.PNG',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/'
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url === event.notification.data.url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(event.notification.data.url);
            }
        })
    );
});
