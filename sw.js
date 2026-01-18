const CACHE_NAME = 'portal-cms-v1';

// Instaliraj - cache osnovne resurse
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

// Aktiviraj - očisti stare cacheove
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

// Fetch - network first, fallback to cache
self.addEventListener('fetch', (event) => {
  // Samo GET requestove cachiramo
  if (event.request.method !== 'GET') return;

  // Ignoriraj API pozive i POST forme
  if (event.request.url.includes('/api/')) return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Spremi kopiju u cache
        if (response.status === 200) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      })
      .catch(() => {
        // Offline - probaj iz cachea
        return caches.match(event.request);
      })
  );
});
