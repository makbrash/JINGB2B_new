// Nome e versione della cache statica - incrementa il numero di versione
const CACHE_NAME = 'jingb2b-static-v6';
// File da cachare all'install del SW (risorse statiche) - aggiungi esplicitamente tutti i file necessari
const STATIC_ASSETS = [
  '/',
  '/index.php', 
  '/assets/js/main.js',  // verifica che il percorso sia corretto
  '/assets/css/style.css',  // verifica che il percorso sia corretto
  '/manifest.json',
  '/assets/img/no_image.jpg',
  'https://code.jquery.com/jquery-3.6.0.min.js',
  /*'https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;700&display=swap'*/
];

// Evento di installazione
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Caching static assets');
      return cache.addAll(STATIC_ASSETS);
    })
    .then(() => self.skipWaiting())
  );
});

// Evento di activate (rimuove vecchie cache)
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keyList => {
      return Promise.all(
        keyList.map(key => {
          if (key !== CACHE_NAME) {
            console.log('Removing old cache', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Evento fetch: strategia cache-first con fallback
self.addEventListener('fetch', event => {
  const requestUrl = new URL(event.request.url);
  const url = event.request.url;
  
    // Se la richiesta è per un'estensione Chrome, esci subito
  if (url.startsWith('chrome-extension://')) {
    return; // Non facciamo nulla, non mettiamo in cache
  }
  
    // Se la richiesta è verso fonts.gstatic.com o fonts.googleapis.com
  if (requestUrl.origin === 'https://fonts.gstatic.com' || requestUrl.origin === 'https://fonts.googleapis.com') {
    /*event.respondWith(
      caches.open('google-fonts').then(cache => {
        return cache.match(event.request).then(cachedResponse => {
          if (cachedResponse) {
            // Se è già in cache, restituisco quella (cache-first)
            return cachedResponse;
          }
          // Altrimenti faccio la fetch in rete e metto in cache la risposta "opaque"
          return fetch(event.request).then(networkResponse => {
            // Se la fetch va a buon fine, salvo in cache
            if (networkResponse && networkResponse.ok) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          }).catch(err => {
            // In caso di errore rete, potresti tornare un fallback o niente
            console.warn("Impossibile scaricare Google Font:", requestUrl.href, err);
            return new Response('', { status: 408, statusText: 'Font fetch error' });
          });
        });
      })
    );*/
    return; // Non eseguire altre logiche per questo request
  }
  
  
  
  // 1) Gestione immagini prodotti (in /public/catalogo/)
  if (requestUrl.pathname.startsWith('/public/catalogo/')) {
    event.respondWith(
      caches.match(event.request).then(response => {
        if (response) {
          // Cache-first
          return response;
        }
        // Non in cache? Vai in rete e metti in cache
        return fetch(event.request).then(networkResponse => {
          // Verifica che la risposta sia valida
          if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
            return networkResponse;
          }
          
          // Crea una risposta con content-type corretto
          const headers = new Headers(networkResponse.headers);
          // Forza il content-type corretto per le immagini
          if (requestUrl.pathname.endsWith('.jpg') || requestUrl.pathname.endsWith('.jpeg')) {
            headers.set('Content-Type', 'image/jpeg');
          } else if (requestUrl.pathname.endsWith('.png')) {
            headers.set('Content-Type', 'image/png');
          }
          
          // Crea una nuova risposta con header corretti
          const correctedResponse = new Response(networkResponse.body, {
            status: networkResponse.status,
            statusText: networkResponse.statusText,
            headers: headers
          });
          
          return caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, correctedResponse.clone());
            return correctedResponse;
          });
        }).catch(error => {
          console.error('Fetch failed:', error);
          // Offline + non in cache => fallback
          return caches.match('/assets/img/no_image.jpg');
        });
      })
    );
    return;
  }
  
  // 2) Gestione delle risorse statiche - strategie diverse per JS e CSS
  if (requestUrl.pathname.endsWith('.js') || requestUrl.pathname.endsWith('.css')) {
    event.respondWith(
      caches.match(event.request).then(response => {
        if (response) {
          return response; // Cache first
        }
        
        return fetch(event.request).then(networkResponse => {
          // Verifica che la risposta sia valida
          if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
            return networkResponse;
          }
          
          // Clona la risposta e la mette in cache
          let responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            console.log('Caching new resource:', requestUrl.pathname);
            cache.put(event.request, responseToCache);
          });
          
          return networkResponse;
        });
      })
    );
    return;
  }
  
  // 3) Gestione delle API
  if (requestUrl.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        // Se offline => fornisci un JSON di fallback
        return new Response(JSON.stringify({
          success: false,
          error: 'Nessuna connessione, impossibile contattare le API'
        }), {
          headers: { 'Content-Type': 'application/json' }
        });
      })
    );
    return;
  }
  
  // 4) Default: cache-first per le altre risorse
  event.respondWith(
    caches.match(event.request).then(response => {
      // Se in cache => ok, altrimenti fetch rete
      return response || fetch(event.request).then(networkResponse => {
        // Verifica se è una risposta valida e cacheable
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
          // Clona la risposta per poterla usare e mettere in cache
          let responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      }).catch(() => {
        // Se completamente offline e non in cache => fallback
        return caches.match('/index.php');
      });
    })
  );
});