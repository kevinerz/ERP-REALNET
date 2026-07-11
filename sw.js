const CACHE_NAME = 'datarealsolution-cache-v2';

const STATIC_ASSETS = [
  '/manifest.json',
  '/css/bootstrap.min.css',
  '/js/bootstrap.bundle.min.js',
  '/images/logo.png', // sesuaikan path
  // tambahkan aset statis lain yang penting
];

// Helper function untuk menentukan apakah request harus bypass cache
function isBypassRequest(request) {
  const url = new URL(request.url);
  // Jangan cache halaman login, logout, form dinamis
  const noCachePaths = ['/login.php', '/logout.php', '/some_form.php'];
  if (noCachePaths.includes(url.pathname)) return true;

  // Bisa tambah kondisi lain, misal header khusus atau query param
  return false;
}

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  if (isBypassRequest(event.request)) {
    // Network only: selalu fetch langsung untuk halaman login/logout/form
    event.respondWith(fetch(event.request));
    return;
  }

  // Untuk request aset statis dan halaman lain, gunakan cache-first (stale-while-revalidate)
  event.respondWith(
    caches.match(event.request).then(cachedResponse => {
      const fetchPromise = fetch(event.request).then(networkResponse => {
        // Update cache kalau berhasil dan response status OK
        if (networkResponse && networkResponse.status === 200) {
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, networkResponse.clone());
          });
        }
        return networkResponse;
      }).catch(() => {
        // Jika fetch gagal, fallback ke cache (jika ada)
        return cachedResponse;
      });

      // Kalau ada cache, kembalikan cache dulu, sambil fetch update di belakang layar
      return cachedResponse || fetchPromise;
    })
  );
});
