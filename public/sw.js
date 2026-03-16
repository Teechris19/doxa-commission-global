const CACHE_VERSION = 'v1';
const APP_SHELL_CACHE = `doxa-shell-${CACHE_VERSION}`;
const RUNTIME_CACHE = `doxa-runtime-${CACHE_VERSION}`;

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
                    .filter((key) => ![APP_SHELL_CACHE, RUNTIME_CACHE].includes(key))
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

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstPage(request));
        return;
    }

    if (isMediaRequest(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    if (isStaticAsset(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    event.respondWith(networkFirst(request));
});

function isStaticAsset(pathname) {
    return pathname.startsWith('/build/') || pathname.endsWith('.css') || pathname.endsWith('.js');
}

function isMediaRequest(pathname) {
    return pathname.includes('/storage/sermons/')
        || pathname.endsWith('.mp3')
        || pathname.endsWith('.m4a')
        || pathname.endsWith('.ogg')
        || pathname.endsWith('.mp4');
}

async function networkFirstPage(request) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(RUNTIME_CACHE);
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
    try {
        const response = await fetch(request);
        const cache = await caches.open(RUNTIME_CACHE);
        cache.put(request, response.clone());
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        return cached || new Response(null, { status: 504, statusText: 'Gateway Timeout' });
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);

    const networkPromise = fetch(request)
        .then((response) => {
            cache.put(request, response.clone());
            return response;
        })
        .catch(() => null);

    return cached || networkPromise || new Response(null, { status: 504, statusText: 'Gateway Timeout' });
}
