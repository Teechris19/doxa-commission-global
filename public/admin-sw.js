const CACHE_NAME = 'doxa-admin-v2';
const OFFLINE_CACHE = 'doxa-admin-offline-v1';
const DYNAMIC_CACHE = 'doxa-admin-dynamic-v1';

// Pages and assets to cache on install (offline shell)
const STATIC_ASSETS = [
    '/admin/dashboard',
    '/admin/login',
    '/build/manifest.json',
];

// Install - cache admin shell
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// Activate - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((k) => k !== CACHE_NAME && k !== DYNAMIC_CACHE)
                    .map((k) => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

// Fetch - strategy depends on request type
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Skip non-GET, Livewire uploads, and external resources
    if (request.method !== 'GET' ||
        request.url.includes('/livewire/upload-file') ||
        request.url.includes('/cdn.') ||
        request.url.includes('/fonts.')) {
        return;
    }

    // HTML navigation: network first, fallback to cached version or offline page
    if (request.mode === 'navigate' ||
        (request.url.endsWith('.html') && request.url.includes('/admin/'))) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) =>
                        cached || caches.match('/admin/dashboard')
                    )
                )
        );
        return;
    }

    // Static assets (CSS, JS, images, fonts): cache first
    if (['style', 'script', 'image', 'font'].includes(request.destination)) {
        event.respondWith(
            caches.match(request).then((cached) =>
                cached ??
                fetch(request).then((response) => {
                    if (response.ok) {
                        caches.open(CACHE_NAME).then((cache) =>
                            cache.put(request, response.clone())
                        );
                    }
                    return response;
                }).catch(() => cached)
            )
        );
        return;
    }

    // API / Livewire requests: network first, fallback to cache
    if (request.url.includes('/livewire/') ||
        request.url.includes('/api/')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        caches.open(DYNAMIC_CACHE).then((cache) =>
                            cache.put(request, response.clone())
                        );
                    }
                    return response;
                })
                .catch(() => caches.match(request))
        );
        return;
    }

    // Default: stale-while-revalidate
    event.respondWith(
        caches.match(request).then((cached) => {
            const fetchPromise = fetch(request).then((response) => {
                if (response.ok) {
                    caches.open(DYNAMIC_CACHE).then((cache) =>
                        cache.put(request, response.clone())
                    );
                }
                return response;
            }).catch(() => cached);

            return cached || fetchPromise;
        })
    );
});

// Push notification handler
self.addEventListener('push', (event) => {
    let data = { title: 'Doxa Admin', body: 'New notification', icon: '/Img/doxa.PNG', badge: '/Img/doxa.PNG' };

    if (event.data) {
        try {
            const parsed = event.data.json();
            data = { ...data, ...parsed };
        } catch {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/Img/doxa.PNG',
        badge: data.badge || '/Img/doxa.PNG',
        image: data.image || null,
        data: data.data || {},
        actions: data.actions || [],
        tag: data.tag || 'default',
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/admin/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes('/admin') && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(urlToOpen);
        })
    );
});

// Handle messages from client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(DYNAMIC_CACHE).then((cache) => cache.addAll(event.data.urls))
        );
    }
});
