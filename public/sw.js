// Service worker aplikasi absensi Toko MM.
//
// Strategi: network-first untuk semua permintaan GET sesama origin.
// Halaman selalu diambil segar dari server kalau ada jaringan; cache
// dipakai hanya sebagai cadangan ketika offline. POST (absen, login, dan
// sejenisnya) tidak pernah disentuh supaya tidak menimbulkan duplikasi.
const VERSI = 'toko-mm-v4';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((kunci) => Promise.all(
                kunci.filter((k) => k !== VERSI).map((k) => caches.delete(k)),
            )),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    e.respondWith(
        fetch(req)
            .then((res) => {
                if (res && res.status === 200) {
                    const klon = res.clone();
                    caches.open(VERSI).then((c) => c.put(req, klon));
                }
                return res;
            })
            .catch(() => caches.match(req).then((tersimpan) => tersimpan ?? halamanOffline())),
    );
});

function halamanOffline() {
    return new Response(
        '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        + '<meta name="viewport" content="width=device-width, initial-scale=1">'
        + '<title>Offline - Absensi Toko</title></head>'
        + '<body style="margin:0;font-family:system-ui,Arial,sans-serif;background:#0F5342;color:#fff;'
        + 'display:grid;place-items:center;min-height:100vh;text-align:center">'
        + '<div><p style="font-size:40px;margin:0 0 8px">&#10004;</p>'
        + '<h1 style="margin:0 0 6px;font-size:18px">Absensi Toko</h1>'
        + '<p style="margin:0;color:#cfe3dd">Tidak ada koneksi internet.</p>'
        + '<p style="margin:0;color:#cfe3dd">Periksa jaringan lalu coba lagi.</p></div>'
        + '</body></html>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } },
    );
}