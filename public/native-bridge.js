/*
 * native-bridge.js — jembatan antara aplikasi web Absensi Toko MM dan shell
 * Android (Capacitor).
 *
 * Saat halaman dibuka di browser biasa, objek `window.TokoNative` tetap ada
 * tetapi `aktif = false`, sehingga semua pemanggil bisa fallback ke API web
 * (geolocation browser, service worker, dsb). Saat dibuka di dalam APK,
 * `aktif = true` dan fungsi memakai plugin native:
 *   - Geolocation        (@capacitor/geolocation)
 *   - BarcodeScanner     (@capacitor-mlkit/barcode-scanning)
 *   - PushNotifications  (@capacitor/push-notifications)  -> FCM
 *   - BluetoothLe        (@capacitor-community/bluetooth-le) -> printer thermal
 *
 * Plugin diakses lewat proxy global `window.Capacitor.Plugins.<Nama>` yang
 * disuntikkan Capacitor, jadi aplikasi Laravel tidak perlu membundel
 * @capacitor/core.
 */
(function () {
    'use strict';

    const Cap = window.Capacitor;
    const aktif = !!(Cap && typeof Cap.isNativePlatform === 'function' && Cap.isNativePlatform());
    const platform = (Cap && typeof Cap.getPlatform === 'function' && Cap.getPlatform()) || 'web';

    const plugin = (nama) => (Cap && Cap.Plugins && Cap.Plugins[nama]) ? Cap.Plugins[nama] : null;

    function tokenCsrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : null;
    }

    function kepalaJson() {
        const h = { 'Accept': 'application/json' };
        const csrf = tokenCsrf();
        if (csrf) {
            h['Content-Type'] = 'application/json';
            h['X-CSRF-TOKEN'] = csrf;
            h['X-Requested-With'] = 'XMLHttpRequest';
        }
        return h;
    }

    // ---------------------------------------------------------------- lokasi
    async function izinLokasi() {
        const Geo = plugin('Geolocation');
        if (!Geo) throw new Error('Plugin lokasi tidak tersedia.');
        let perm = await Geo.checkPermissions();
        if (perm.location !== 'granted' && perm.coarseLocation !== 'granted') {
            perm = await Geo.requestPermissions();
        }
        if (perm.location !== 'granted' && perm.coarseLocation !== 'granted') {
            throw new Error('Izin lokasi ditolak. Aktifkan lokasi untuk aplikasi ini lalu coba lagi.');
        }
    }

    /**
     * Kumpulkan beberapa pembacaan GPS lalu kembalikan yang paling akurat.
     * Mengembalikan Promise dengan { latitude, longitude, accuracy }.
     */
    async function posisi(opsi = {}) {
        const Geo = plugin('Geolocation');
        if (!Geo) throw new Error('Plugin lokasi tidak tersedia.');

        await izinLokasi();

        const pengaturan = {
            enableHighAccuracy: true,
            timeout: opsi.timeout || 15000,
            maximumAge: 0,
        };

        const jumlahSampel = opsi.sampel || 3;
        let terbaik = null;

        for (let i = 0; i < jumlahSampel; i++) {
            try {
                const p = await Geo.getCurrentPosition(pengaturan);
                const akurasi = p.coords.accuracy == null ? Number.MAX_SAFE_INTEGER : p.coords.accuracy;
                if (!terbaik || akurasi < terbaik.coords.accuracy) {
                    terbaik = { coords: p.coords };
                }
                if (akurasi <= 20) break;
            } catch (e) {
                if (terbaik) break;
                if (i === jumlahSampel - 1) {
                    throw new Error(e && e.message ? e.message : 'Gagal mengambil lokasi.');
                }
            }
        }

        if (!terbaik) throw new Error('Gagal mengambil lokasi.');

        return {
            latitude: terbaik.coords.latitude,
            longitude: terbaik.coords.longitude,
            accuracy: terbaik.coords.accuracy,
            native: true,
        };
    }

    // ------------------------------------------------------------- scan kartu
    async function scanKartu() {
        const Scanner = plugin('BarcodeScanner');
        if (!Scanner) throw new Error('Pemindai kamera tidak tersedia.');

        const didukung = await Scanner.isSupported();
        if (!didukung.supported) {
            throw new Error('Perangkat ini tidak mendukung pemindaian kamera.');
        }

        // Modul ML Kit kadang perlu diunduh lebih dulu di perangkat baru.
        if (didukung.android && typeof Scanner.installGoogleBarcodeScannerModule === 'function') {
            const belumSiap = didukung.googleBarcodeScannerModuleInstallState !== undefined
                && didukung.googleBarcodeScannerModuleInstallState !== 4
                && didukung.googleBarcodeScannerModuleInstallState !== 3;
            if (belumSiap) {
                try { await Scanner.installGoogleBarcodeScannerModule(); } catch (e) { /* lanjut saja */ }
            }
        }

        try {
            const { camera } = await Scanner.checkPermissions();
            if (camera !== 'granted') {
                await Scanner.requestPermissions();
            }
        } catch (e) { /* biarkan plugin meminta izin sendiri */ }

        const hasil = await Scanner.scan({
            formats: [
                'QR_CODE', 'CODE_128', 'CODE_39', 'CODE_93', 'CODABAR',
                'EAN_13', 'EAN_8', 'UPC_A', 'UPC_E', 'ITF',
                'DATA_MATRIX', 'PDF_417', 'AZTEC',
            ],
            lensFacing: 'back',
            prompt: 'Arahkan kamera ke kartu / QR',
        });

        const barcode = hasil && hasil.barcodes && hasil.barcodes[0];
        return barcode ? barcode.rawValue : null;
    }

    function tutupScan() {
        const Scanner = plugin('BarcodeScanner');
        if (Scanner && typeof Scanner.stopScan === 'function') {
            try { Scanner.stopScan(); } catch (e) { /* diabaikan */ }
        }
    }

    // --------------------------------------------------------------- notifikasi
    async function daftarPush() {
        const Push = plugin('PushNotifications');
        if (!Push) return false;

        let perm = await Push.checkPermissions();
        if (perm.receive !== 'granted') {
            perm = await Push.requestPermissions();
        }
        if (perm.receive !== 'granted') return false;

        Push.addListener('registration', async (token) => {
            let deviceId = null;
            try { deviceId = window.localStorage.getItem('tokom_dev'); } catch (e) { /* diabaikan */ }
            try {
                await fetch('/perangkat/push', {
                    method: 'POST',
                    headers: kepalaJson(),
                    body: JSON.stringify({ token: token.value, platform: platform, device_id: deviceId }),
                });
            } catch (e) { /* diabaikan; akan dicoba lagi saat halaman berikut */ }
        });

        Push.addListener('registrationError', () => { /* token gagal; fitur lain tetap jalan */ });

        Push.addListener('pushNotificationReceived', (n) => {
            window.dispatchEvent(new CustomEvent('toko:push', { detail: n }));
        });

        Push.addListener('pushNotificationActionPerformed', (aksi) => {
            const data = aksi && aksi.notification && aksi.notification.data;
            if (data && data.url) {
                window.location.href = data.url;
            }
        });

        await Push.register();
        return true;
    }

    // --------------------------------------------------------- printer thermal
    const BLE = () => plugin('BluetoothLe');

    function bytesKeBase64(bytes) {
        let s = '';
        for (let i = 0; i < bytes.length; i++) s += String.fromCharCode(bytes[i]);
        return btoa(s);
    }

    const ESC = 0x1B;
    const GS = 0x1D;

    function encodeEscPos(lines) {
        const out = [];
        const push = (...b) => out.push(...b);
        const teks = (s) => {
            // Ganti karakter di luar ASCII agar printer tidak mencetak sampah.
            for (const ch of String(s)) {
                const kode = ch.charCodeAt(0);
                push(kode <= 0x7E && kode >= 0x20 ? kode : (kode === 0x0A ? 0x0A : 0x3F));
            }
        };

        push(ESC, 0x40); // init

        for (const baris of lines) {
            const l = typeof baris === 'string' ? { teks: baris } : baris;

            if (l.garis) {
                push(ESC, 0x61, 0x00);
                teks('-'.repeat(l.panjang || 32) + '\n');
                continue;
            }

            push(ESC, 0x61, l.tengah ? 0x01 : (l.kanan ? 0x02 : 0x00));
            push(ESC, 0x45, l.tebal ? 0x01 : 0x00);
            if (l.besar) push(GS, 0x21, 0x11); else push(GS, 0x21, 0x00);
            teks((l.teks || '') + '\n');
            push(ESC, 0x45, 0x00);
            push(GS, 0x21, 0x00);
        }

        push(0x0A, 0x0A, 0x0A, 0x0A);
        push(GS, 0x56, 0x42, 0x00); // potong sebagian
        return new Uint8Array(out);
    }

    function simpan(cacheKey, id) {
        try { localStorage.setItem(cacheKey, id); } catch (e) { /* diabaikan */ }
    }
    function muat(cacheKey) {
        try { return localStorage.getItem(cacheKey); } catch (e) { return null; }
    }

    async function sambungPrinter() {
        const Ble = BLE();
        if (!Ble) throw new Error('Bluetooth tidak tersedia.');

        await Ble.initialize({ androidNeverForLocation: true });

        const perangkat = await Ble.requestDevice({
            services: [],
            optionalServices: ['0000ff00-0000-1000-8000-00805f9b34fb', 'e7810a71-73ae-499d-8c15-faa9aef0c3f2', '49535343-fe7d-4ae5-8fa9-9fafd205e455'],
        });

        await Ble.connect({ deviceId: perangkat.deviceId });
        simpan('toko-printer-id', perangkat.deviceId);
        return perangkat.deviceId;
    }

    function cariKarakteristik(services) {
        const prioritas = [
            '0000ff02-0000-1000-8000-00805f9b34fb',
            'bef8d6c9-9c21-4c9e-b632-bd58c1009f9f',
            '0000ff01-0000-1000-8000-00805f9b34fb',
        ];
        let kandidat = [];

        for (const s of services.services) {
            for (const c of s.characteristics) {
                if (c.properties && (c.properties.write || c.properties.writeWithoutResponse)) {
                    kandidat.push({ service: s.uuid, characteristic: c.uuid, tanpaJawaban: !!c.properties.writeWithoutResponse });
                }
            }
        }

        if (!kandidat.length) return null;
        for (const p of prioritas) {
            const cocok = kandidat.find((k) => k.characteristic.toLowerCase() === p);
            if (cocok) return cocok;
        }
        return kandidat[0];
    }

    async function cetak(lines) {
        const Ble = BLE();
        if (!Ble) throw new Error('Bluetooth tidak tersedia.');

        await Ble.initialize({ androidNeverForLocation: true });

        let deviceId = muat('toko-printer-id');
        let tersambung = false;
        if (deviceId) {
            try {
                const status = await Ble.isConnected({ deviceId });
                tersambung = !!status.connected;
                if (!tersambung) { await Ble.connect({ deviceId }); tersambung = true; }
            } catch (e) { deviceId = null; }
        }
        if (!deviceId) deviceId = await sambungPrinter();

        const services = await Ble.getServices({ deviceId });
        const target = cariKarakteristik(services);
        if (!target) throw new Error('Printer tidak punya karakteristik tulis.');

        const data = encodeEscPos(lines);
        const ukuran = 180;
        for (let i = 0; i < data.length; i += ukuran) {
            const potong = data.slice(i, i + ukuran);
            await Ble.write({
                deviceId,
                service: target.service,
                characteristic: target.characteristic,
                value: bytesKeBase64(potong),
                type: target.tanpaJawaban ? 'noResponse' : 'withResponse',
            });
            await new Promise((r) => setTimeout(r, 30));
        }

        return true;
    }

    window.TokoNative = {
        aktif,
        platform,
        posisi,
        scanKartu,
        tutupScan,
        daftarPush,
        sambungPrinter,
        cetakPrinter: cetak,
        plugin,
    };

    // Daftarkan token FCM otomatis di SETIAP halaman yang memuat jembatan ini
    // (beranda karyawan maupun dasbor admin), asalkan dibuka di dalam APK dan
    // pengguna sudah login. Sebelumnya hanya layout.app yang memanggil ini,
    // sehingga akun pemilik/supervisor (layout admin) tidak pernah terdaftar.
    if (aktif) {
        const pasangPush = () => {
            const meta = document.querySelector('meta[name="toko-native-auth"]');
            if (!meta || meta.content !== '1') return;
            daftarPush().catch(() => {});
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', pasangPush);
        } else {
            pasangPush();
        }
    }
})();
