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