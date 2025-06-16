const CACHE_NAME = "ici-v1";
const urlsToCache = [
  "/participantesici/public_html/login",
  "/participantesici/public_html/assets/css/tailwind.css",
  "/participantesici/public_html/assets/css/styles.css",
  "/participantesici/public_html/assets/js/global-scripts.js",
  "/participantesici/public_html/assets/images/logo.png",
  "/participantesici/public_html/assets/images/favicon.ico",
  "/participantesici/public_html/manifest.json",
  "/participantesici/public_html/assets/videos/fogueira.mp4"
];

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener("fetch", event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});

self.addEventListener("activate", event => {
  event.waitUntil(
    caches.keys().then(keyList => {
      return Promise.all(
        keyList.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});



