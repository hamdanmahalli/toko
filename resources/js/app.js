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
// Dialog kustom: pengganti confirm/alert/prompt bawaan browser supaya
// tampilannya seragam dengan aplikasi. Markup-nya ada di
// layouts/dialog.blade.php, di sini hanya logika buka/tutupnya.
//   TokoDialog.konfirmasi({...}) -> Promise<boolean>
//   TokoDialog.pesan({...})      -> Promise<void>
//   TokoDialog.tanyaTeks({...})  -> Promise<string|null>
(function () {
    'use strict';

    var kotak = document.getElementById('dialog');
    if (!kotak) return;

    var ikonEl = kotak.querySelector('[data-dialog-ikon]');
    var judulEl = kotak.querySelector('[data-dialog-judul]');
    var pesanEl = kotak.querySelector('[data-dialog-pesan]');
    var inputEl = kotak.querySelector('[data-dialog-input]');
    var galatEl = kotak.querySelector('[data-dialog-galat]');
    var batalEl = kotak.querySelector('[data-dialog-batal]');
    var okEl = kotak.querySelector('[data-dialog-ok]');
    var tutupEl = kotak.querySelector('[data-dialog-tutup]');

    var IKON = {
        netral: {
            kelas: 'bg-brand-50 text-brand-700',
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M12 3l9 16H3z"/>',
        },
        peringatan: {
            kelas: 'bg-merah-50 text-merah-600',
            svg: '<path stroke-linecap="round" d="M12 8v5m0 3.5v.01M10.3 3.9 2.5 17.5A2 2 0 0 0 4.2 20.5h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>',
        },
    };

    var GAYA_OK =
        'flex-1 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]';
    var GAYA_OK_BAHAYA =
        'flex-1 rounded-xl bg-merah-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-merah-700 active:scale-[.99]';

    var selesai = null;
    var fokusSebelum = null;
    var modeInput = false;
    var bolehBatal = true;

    function pasangIkon(jenis) {
        if (!jenis) {
            ikonEl.className = 'hidden h-9 w-9 shrink-0';
            ikonEl.innerHTML = '';
            return;
        }

        var pilihan = IKON[jenis] || IKON.netral;
        ikonEl.className = 'flex h-9 w-9 shrink-0 items-center justify-center rounded-full ' + pilihan.kelas;
        ikonEl.innerHTML = '<svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' + pilihan.svg + '</svg>';
    }

    function buka(opsi) {
        modeInput = opsi.mode === 'input';
        bolehBatal = !opsi.tanpaBatal;

        judulEl.textContent = opsi.judul || '';
        pesanEl.textContent = opsi.pesan || '';
        pesanEl.hidden = !opsi.pesan;
        pasangIkon(opsi.ikon || 'netral');

        if (modeInput) {
            inputEl.classList.remove('hidden');
            inputEl.value = opsi.nilai || '';
            inputEl.placeholder = opsi.placeholder || '';
        } else {
            inputEl.classList.add('hidden');
        }

        galatEl.classList.add('hidden');
        galatEl.textContent = '';

        batalEl.hidden = !bolehBatal;
        okEl.textContent = opsi.labelOk || (bolehBatal ? 'Lanjut' : 'Mengerti');
        okEl.className = opsi.bahaya ? GAYA_OK_BAHAYA : GAYA_OK;

        fokusSebelum = document.activeElement;
        kotak.classList.remove('hidden');

        if (modeInput) {
            inputEl.focus();
            inputEl.setSelectionRange(inputEl.value.length, inputEl.value.length);
        } else {
            okEl.focus();
        }
    }

    function tutup(hasil) {
        kotak.classList.add('hidden');
        modeInput = false;

        if (fokusSebelum && fokusSebelum.focus) {
            try { fokusSebelum.focus(); } catch (e) { /* tak masalah */ }
        }
        fokusSebelum = null;

        var lanjut = selesai;
        selesai = null;
        if (lanjut) lanjut(hasil);
    }

    function batal() {
        if (!bolehBatal) return;
        tutup(modeInput ? null : false);
    }

    okEl.addEventListener('click', function () {
        if (!modeInput) {
            tutup(true);
            return;
        }

        var nilai = inputEl.value.trim();
        if (!nilai && kotak.dataset.wajib) {
            galatEl.textContent = kotak.dataset.wajib;
            galatEl.classList.remove('hidden');
            inputEl.focus();
            return;
        }
        tutup(nilai);
    });

    batalEl.addEventListener('click', batal);
    tutupEl.addEventListener('click', batal);

    inputEl.addEventListener('keydown', function (kejadian) {
        if (kejadian.key === 'Enter' && !kejadian.shiftKey) {
            kejadian.preventDefault();
            okEl.click();
        }
    });

    document.addEventListener('keydown', function (kejadian) {
        if (kejadian.key === 'Escape' && !kotak.classList.contains('hidden')) {
            kejadian.preventDefault();
            batal();
        }
    });

    window.TokoDialog = {
        konfirmasi: function (opsi) {
            return new Promise(function (selesaiKan) {
                selesai = selesaiKan;
                buka(opsi || {});
            });
        },
        pesan: function (opsi) {
            return new Promise(function (selesaiKan) {
                selesai = selesaiKan;
                buka(Object.assign({ tanpaBatal: true, ikon: 'peringatan' }, opsi || {}));
            });
        },
        tanyaTeks: function (opsi) {
            opsi = opsi || {};

            if (opsi.wajib) {
                kotak.dataset.wajib = opsi.pesanWajib || 'Wajib diisi.';
            } else {
                delete kotak.dataset.wajib;
            }

            return new Promise(function (selesaiKan) {
                selesai = selesaiKan;
                buka(Object.assign({ mode: 'input' }, opsi));
            });
        },
    };
})();

//
// Intersep pengiriman form yang butuh konfirmasi: alihkan popup bawaan ke
// dialog kustom. Form ber-atribut data-konfirmasi dikirim ulang hanya setelah
// pengguna menyetujui, sehingga umpan balik "Proses" di bawah tetap jalan.
(function () {
    'use strict';

    document.addEventListener('submit', function (kejadian) {
        var form = kejadian.target;
        if (!form || form.nodeName !== 'FORM') return;

        var pesan = form.getAttribute('data-konfirmasi');
        if (!pesan || form.dataset.dikonfirmasi === '1') return;

        kejadian.preventDefault();

        window.TokoDialog.konfirmasi({
            judul: form.getAttribute('data-konfirmasi-judul') || 'Konfirmasi',
            pesan: pesan,
            labelOk: form.getAttribute('data-konfirmasi-tombol') || 'Lanjut',
            bahaya: form.hasAttribute('data-konfirmasi-bahaya'),
        }).then(function (setuju) {
            if (!setuju) return;

            form.dataset.dikonfirmasi = '1';

            var tombol = kejadian.submitter;
            if (tombol && tombol.type === 'submit' && tombol.form === form) {
                form.requestSubmit(tombol);
            } else {
                form.requestSubmit();
            }
        });
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

//
// Keamanan akun: "simpan nama user" dan "login dengan biometrik". Keduanya
// preferensi perangkat, jadi disimpan di localStorage, bukan di server.
// Biometrik di sini adalah passkey sederhana: kredensial perangkat hanya
// membuka kunci token yang disegel server dan terikat pada device_id ini.
(function () {
    'use strict';

    var KUNCI_BIO = 'tokom_bio';
    var KUNCI_INGAT = 'tokom_ingat';
    var KUNCI_LOGIN = 'tokom_login';

    function baca(kunci) {
        try { return window.localStorage.getItem(kunci); } catch (e) { return null; }
    }

    function tulis(kunci, nilai) {
        try {
            if (nilai === null) {
                window.localStorage.removeItem(kunci);
            } else {
                window.localStorage.setItem(kunci, nilai);
            }
        } catch (e) { /* penyimpanan terkunci */ }
    }

    function bacaJson(kunci) {
        try { return JSON.parse(baca(kunci)); } catch (e) { return null; }
    }

    function deviceId() {
        return baca('tokom_dev') || '';
    }

    function tokenCsrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function keB64url(buf) {
        var bytes = new Uint8Array(buf);
        var str = '';
        for (var i = 0; i < bytes.length; i++) str += String.fromCharCode(bytes[i]);
        return window.btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function dariB64url(s) {
        s = s.replace(/-/g, '+').replace(/_/g, '/');
        while (s.length % 4) s += '=';
        var str = window.atob(s);
        var bytes = new Uint8Array(str.length);
        for (var i = 0; i < str.length; i++) bytes[i] = str.charCodeAt(i);
        return bytes;
    }

    function tantanganAcak() {
        var t = new Uint8Array(32);
        window.crypto.getRandomValues(t);
        return t;
    }

    // --- Halaman login: isi otomatis username + tombol biometrik ---
    var formLogin = document.querySelector('form[action$="/masuk"]');
    if (formLogin) {
        var inputLogin = formLogin.querySelector('input[name="login"]');

        if (inputLogin && baca(KUNCI_INGAT) === '1') {
            var tersimpan = inputLogin.value || baca(KUNCI_LOGIN) || '';
            if (tersimpan) {
                inputLogin.value = tersimpan;
                var barisLogin = document.getElementById('baris-login');
                if (barisLogin) barisLogin.style.display = 'none';
            }
        }

        formLogin.addEventListener('submit', function () {
            if (baca(KUNCI_INGAT) === '1' && inputLogin && inputLogin.value) {
                tulis(KUNCI_LOGIN, inputLogin.value);
            }
        });

        var bioLogin = bacaJson(KUNCI_BIO);
        var tombolBio = document.getElementById('bio-masuk');
        if (bioLogin && bioLogin.credentialId && bioLogin.token && tombolBio) {
            tombolBio.hidden = false;
            tombolBio.addEventListener('click', function () {
                masukBiometrik(bioLogin, tombolBio);
            });
        }
    }

    function masukBiometrik(bio, tombol) {
        var form = document.getElementById('bio-masuk-form');
        if (!form || form.dataset.sibuk) return;
        if (!navigator.credentials || !window.PublicKeyCredential) return;

        form.dataset.sibuk = '1';
        tombol.disabled = true;

        navigator.credentials.get({
            publicKey: {
                challenge: tantanganAcak(),
                allowCredentials: [{ id: dariB64url(bio.credentialId), type: 'public-key' }],
                userVerification: 'required',
                timeout: 60000,
            },
        }).then(function () {
            form.querySelector('input[name="token"]').value = bio.token;
            form.querySelector('input[name="device_id"]').value = deviceId();
            form.submit();
        }).catch(function () {
            tombol.disabled = false;
            delete form.dataset.sibuk;
        });
    }

    // --- Halaman profil: dua toggle keamanan akun ---
    var toggleIngat = document.getElementById('ingat-toggle');
    if (toggleIngat) {
        toggleIngat.checked = baca(KUNCI_INGAT) === '1';
        toggleIngat.addEventListener('change', function () {
            if (toggleIngat.checked) {
                tulis(KUNCI_INGAT, '1');
            } else {
                tulis(KUNCI_INGAT, null);
                tulis(KUNCI_LOGIN, null);
            }
        });
    }

    var toggleBio = document.getElementById('bio-toggle');
    if (toggleBio) {
        var bioTersimpan = bacaJson(KUNCI_BIO);
        toggleBio.checked = !!(bioTersimpan && bioTersimpan.credentialId);

        var dukungan = window.PublicKeyCredential &&
            window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable;

        if (dukungan) {
            window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
                .then(function (ada) {
                    if (ada) return;
                    toggleBio.disabled = true;
                    var ket = document.getElementById('bio-ket');
                    if (ket) ket.textContent = 'Perangkat ini tidak menyediakan sidik jari atau wajah.';
                })
                .catch(function () { /* biarkan aktif */ });
        } else {
            toggleBio.disabled = true;
            var ketUnsupported = document.getElementById('bio-ket');
            if (ketUnsupported) ketUnsupported.textContent = 'Perangkat ini tidak mendukung login biometrik.';
        }

        toggleBio.addEventListener('change', function () {
            if (toggleBio.checked) {
                daftarBiometrik(toggleBio);
            } else {
                tulis(KUNCI_BIO, null);
            }
        });
    }

    function daftarBiometrik(toggle) {
        if (!navigator.credentials || !window.PublicKeyCredential) {
            toggle.checked = false;
            return;
        }

        var userId = new Uint8Array(16);
        window.crypto.getRandomValues(userId);

        navigator.credentials.create({
            publicKey: {
                challenge: tantanganAcak(),
                rp: { name: document.title || 'Toko MM' },
                user: {
                    id: userId,
                    name: toggle.dataset.user || 'karyawan',
                    displayName: 'Karyawan',
                },
                pubKeyCredParams: [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
                authenticatorSelection: {
                    authenticatorAttachment: 'platform',
                    userVerification: 'required',
                },
                attestation: 'none',
                timeout: 60000,
            },
        }).then(function (kredensial) {
            return window.fetch(toggle.dataset.tokenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': tokenCsrf(),
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ device_id: deviceId() }),
            }).then(function (respons) {
                if (!respons.ok) throw new Error('gagal');
                return respons.json();
            }).then(function (hasil) {
                tulis(KUNCI_BIO, JSON.stringify({
                    credentialId: keB64url(kredensial.rawId),
                    token: hasil.token,
                    username: toggle.dataset.user || '',
                }));
            });
        }).catch(function () {
            toggle.checked = false;
            tulis(KUNCI_BIO, null);
        });
    }
})();

//
// Unduh kartu pegawai sebagai PNG. html2canvas dimuat saat dibutuhkan saja
// (dynamic import) supaya tidak memberatkan halaman lain.
document.addEventListener('click', function (event) {
    var tombol = event.target.closest('[data-unduh-kartu]');
    if (!tombol) return;

    var kartu = document.querySelector(tombol.getAttribute('data-unduh-kartu'));
    if (!kartu) return;

    event.preventDefault();

    var namaFile = tombol.getAttribute('data-label') || 'kartu';
    var teksAsli = tombol.innerHTML;
    tombol.disabled = true;
    tombol.innerHTML = 'Menyiapkan...';

    import('html2canvas-pro')
        .then(function (modul) {
            var html2canvas = modul.default || modul;

            return html2canvas(kartu, {
                backgroundColor: null,
                scale: 3,
                useCORS: true,
                logging: false,
            });
        })
        .then(function (canvas) {
            var tautan = document.createElement('a');
            tautan.download = 'kartu-' + namaFile + '.png';
            tautan.href = canvas.toDataURL('image/png');
            document.body.appendChild(tautan);
            tautan.click();
            tautan.remove();
        })
        .catch(function (galat) {
            console.error(galat);
            window.TokoDialog.pesan({
                judul: 'Unduh gagal',
                pesan: 'Gagal mengunduh kartu. Coba lagi ya.',
            });
        })
        .finally(function () {
            tombol.disabled = false;
            tombol.innerHTML = teksAsli;
        });
});
