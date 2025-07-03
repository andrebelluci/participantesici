const CACHE_NAME = "ici-v2.1";
const STATIC_CACHE = "ici-static-v2.1";
const DYNAMIC_CACHE = "ici-dynamic-v2.1";

// URLs estáticas para cache imediato
const staticUrlsToCache = [
  "/login",
  "/home",
  "/assets/css/tailwind.css",
  "/assets/js/global-scripts.js",
  "/assets/images/logo.png",
  "/assets/images/favicon.ico",
  "/assets/images/no-image.png",
  "/manifest.json",
  "/assets/videos/fogueira.mp4",
  // Fontes importantes
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css",
  "https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"
];

// URLs que devem ser sempre buscadas da rede (dados dinâmicos)
const networkFirst = [
  "/api/",
  "/participantes",
  "/rituais"
];

// Instalar Service Worker
self.addEventListener("install", event => {
  console.log("Service Worker: Instalando...");
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log("Service Worker: Cachando arquivos estáticos");
        return cache.addAll(staticUrlsToCache);
      })
      .then(() => {
        console.log("Service Worker: Forçando ativação");
        return self.skipWaiting(); // Força ativação imediata
      })
  );
});

// Ativar Service Worker
self.addEventListener("activate", event => {
  console.log("Service Worker: Ativando...");
  event.waitUntil(
    caches.keys().then(keyList => {
      return Promise.all(
        keyList.map(key => {
          // Remove caches antigos
          if (key !== STATIC_CACHE && key !== DYNAMIC_CACHE) {
            console.log("Service Worker: Removendo cache antigo:", key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => {
      console.log("Service Worker: Assumindo controle");
      return self.clients.claim(); // Assume controle imediatamente
    })
  );
});

// Interceptar requisições
self.addEventListener("fetch", event => {
  const request = event.request;
  const url = new URL(request.url);

  // Ignora requisições não HTTP
  if (!request.url.startsWith('http')) {
    return;
  }

  // Estratégia Network First para APIs e dados dinâmicos
  if (networkFirst.some(pattern => request.url.includes(pattern))) {
    event.respondWith(
      fetch(request)
        .then(response => {
          // Salva no cache dinâmico se a resposta for válida
          if (response.status === 200) {
            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then(cache => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Se falhar, tenta buscar no cache
          return caches.match(request);
        })
    );
    return;
  }

  // Estratégia Cache First para recursos estáticos
  event.respondWith(
    caches.match(request)
      .then(response => {
        if (response) {
          return response; // Retorna do cache
        }

        // Se não estiver no cache, busca da rede
        return fetch(request)
          .then(response => {
            // Só cacheia se for uma resposta válida
            if (response.status === 200 && response.type === 'basic') {
              const responseClone = response.clone();
              caches.open(DYNAMIC_CACHE).then(cache => {
                cache.put(request, responseClone);
              });
            }
            return response;
          })
          .catch(() => {
            // Se falhar completamente, retorna página offline (opcional)
            if (request.destination === 'document') {
              return caches.match('/login');
            }
          });
      })
  );
});

// Limpar caches antigos periodicamente
self.addEventListener("message", event => {
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.keys().then(keyList => {
      keyList.forEach(key => {
        if (key.includes('dynamic')) {
          caches.delete(key);
        }
      });
    });
  }
});