const CACHE = 'lensai-v1';
const SHELL = ['./index.html', './app.js', './manifest.json'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Network-first for API calls, cache-first for app shell
self.addEventListener('fetch', e => {
  if (e.request.url.includes('/chat')) return; // never cache AI responses
  e.respondWith(
    caches.match(e.request).then(cached => cached ?? fetch(e.request))
  );
});
