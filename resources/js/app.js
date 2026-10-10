//
// Identitas perangkat (`tokom_dev`): kunci acak yang bisa dibaca di halaman
// mana pun, disalin ke cookie agar tetap bertahan selama 400 hari walau
// localStorage sesekali dibersihkan, dan dicerminkan ke IndexedDB sebagai
// cadangan untuk instalasi PWA.
//
(function () {
    'use strict';

    var KEY = 'tokom_dev';
    var COOKIE_MS = 400 * 86400000;

    function bacaCookie() {
        for (var i = 0; i < document.cookie.split(';').length; i++) {
            var satu = document.cookie.split(';')[i].trim();
            if (satu.indexOf(KEY + '=') === 0) {
                return decodeURIComponent(satu.slice(KEY.length + 1));
            }
        }
        return null;
    }

    function tulisCookie(nilai) {
        document.cookie = KEY + '=' + encodeURIComponent(nilai) +
            '; expires=' + new Date(Date.now() + COOKIE_MS).toUTCString() +
            '; path=/; SameSite=Lax';
    }

    function bukaDb() {
        if (!window.indexedDB) return null;
        var buka = window.indexedDB.open(KEY, 1);
        buka.onupgradeneeded = function () {
            buka.result.createObjectStore('kunci');
        };
        return buka;
    }

    function bacaDb() {
        return new Promise(function (resolve) {
            var buka = bukaDb();
            if (!buka) return resolve(null);
            buka.onsuccess = function () {
                var tx = buka.result.transaction('kunci', 'readonly');
                var get = tx.objectStore('kunci').get('id');
                get.onsuccess = function () { resolve(get.result || null); };
                get.onerror = function () { resolve(null); };
            };
            buka.onerror = function () { resolve(null); };
        });
    }

    function tulisDb(nilai) {
        var buka = bukaDb();
        if (!buka) return;
        buka.onsuccess = function () {
            var tx = buka.result.transaction('kunci', 'readwrite');
            tx.objectStore('kunci').put(nilai, 'id');
        };
    }

    function buatBaru() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'd' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
    }

    function pasang(id) {
        try { window.localStorage.setItem(KEY, id); } catch (e) { /* penyimpanan terkunci */ }
        if (bacaCookie() !== id) tulisCookie(id);
        tulisDb(id);
    }

    function identitas() {
        var id = null;
        try { id = window.localStorage.getItem(KEY); } catch (e) { id = null; }
        if (!id) id = bacaCookie();

        if (id) {
            // Kuatkan cadangan bila satu sumber hilang.
            pasang(id);
        } else {
            id = bacaDb().then(function (dariDb) {
                if (dariDb) pasang(dariDb);
                return dariDb;
            });
        }

        return Promise.resolve(id);
    }

    identitas().then(function (id) {
        if (!id) {
            id = buatBaru();
            pasang(id);
        }
        var kolom = document.querySelector('input[name="device_id"]');
        if (kolom && !kolom.value) kolom.value = id;
    });
})();

//
// Umpan balik tombol proses: begitu form dikirim, tombol yang memicunya
// berubah menjadi spinner + label "Proses" agar tidak terklik dua kali dan
// pengguna tahu ada yang sedang berjalan. Pengiriman GET (mis. filter)
// dibiarkan apa adanya, dan form yang sudah dibatalkan (konfirmasi /
// preventDefault) tidak ikut berubah.
(function () {
    'use strict';

    function tombolProses(tombol) {
        tombol.disabled = true;
        tombol.classList.add('opacity-80', 'cursor-wait');
        tombol.innerHTML =
            '<span class="inline-flex items-center gap-2">' +
            '<svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
            '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/>' +
            '<path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>' +
            '</svg><span>Proses</span></span>';
    }

    document.addEventListener('submit', function (kejadian) {
        if (kejadian.defaultPrevented) return;

        var form = kejadian.target;
        if (!form || form.nodeName !== 'FORM') return;
        if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') return;

        var tombol = kejadian.submitter ||
            form.querySelector('button[type="submit"], button:not([type])');

        if (tombol && !tombol.disabled) {
            tombolProses(tombol);
        }
    });
})();
