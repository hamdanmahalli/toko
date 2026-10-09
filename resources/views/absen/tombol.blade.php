@php
    $sudahMasuk = $hariIni?->jam_masuk !== null;
    $sudahPulang = $hariIni?->jam_pulang !== null;
    $pulangSiap = $sudahMasuk && ! $sudahPulang;
@endphp

<div id="absen-tombol"
     data-toko-lat="{{ $employee->shop->latitude }}"
     data-toko-lng="{{ $employee->shop->longitude }}"
     data-radius="{{ $employee->shop->radius_meter }}"
     class="fixed inset-x-0 bottom-24 z-10 px-4 safe-bottom sm:bottom-6">
    <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
        <p id="absen-status" hidden
           class="max-w-md rounded-full bg-white/95 px-3.5 py-1.5 text-center text-xs text-slate-600 ring-1 ring-brand-100 backdrop-blur">
        </p>

        <div class="grid w-full grid-cols-2 gap-3">
            <button type="button" data-arah="masuk" @disabled($sudahMasuk)
                    class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl text-sm font-semibold shadow-lg transition active:scale-[.98]
                    {{ $sudahMasuk ? 'cursor-not-allowed bg-slate-100 text-slate-400 shadow-none ring-1 ring-slate-200' : 'kedip bg-brand-600 text-white shadow-brand-900/25 hover:bg-brand-700' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h11m0 0l-4-4m4 4l-4 4M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/>
                </svg>
                Datang
            </button>

            <button type="button" data-arah="pulang" @disabled(! $pulangSiap)
                    class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl text-sm font-semibold shadow-lg transition active:scale-[.98]
                    {{ $pulangSiap ? 'kedip bg-merah-600 text-white shadow-brand-900/25 hover:bg-merah-700' : 'cursor-not-allowed bg-slate-100 text-slate-400 shadow-none ring-1 ring-slate-200' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4m0 0l4-4m-4 4l4 4M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/>
                </svg>
                Pulang
            </button>
        </div>
    </div>
</div>

@push('kaki')
<script>
(() => {
    const akar = document.getElementById('absen-tombol');
    if (!akar) return;

    const status = document.getElementById('absen-status');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const url = @json(route('absen.catat'));

    // Batas keras hanya untuk weedakan lokasi yang sama sekali tidak berguna.
    // Keputusan akhir tetap di server.
    const batas = @json((int) \App\Models\Setting::ambilInt('geofence.akurasi_maks_meter', 500));

    let sedang = false;

    let sembunyi = null;

    function tulis(pesan, galat = false) {
        status.textContent = pesan;
        status.className = 'max-w-md rounded-full bg-white/95 px-3.5 py-1.5 text-center text-xs ring-1 ring-brand-100 backdrop-blur '
            + (galat ? 'text-rose-600' : 'text-slate-600');
        status.hidden = false;

        if (sembunyi !== null) clearTimeout(sembunyi);
        sembunyi = setTimeout(() => { status.hidden = true; }, 6500);
    }

    /**
     * Kumpulkan beberapa pembacaan lalu ambil yang paling akurat.
     * Di dalam toko akurasi sering 50-300 m pada bacaan pertama,
     * tetapi membaik setelah beberapa detik.
     */
    function posisi(salkan) {
        // Di dalam APK, pakai GPS native (lebih akurat dan tidak bergantung
        // pada kebijakan lokasi WebView). Bentuk hasil disamakan dengan
        // Position browser supaya pemanggil tidak perlu diubah.
        if (window.TokoNative && window.TokoNative.aktif) {
            if (typeof salkan === 'function') salkan('Mengambil lokasi GPS...');
            return window.TokoNative.posisi({ sampel: 4 }).then(function (p) {
                return { coords: { latitude: p.latitude, longitude: p.longitude, accuracy: p.accuracy } };
            });
        }

        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Browser ini tidak mendukung pengambilan lokasi.'));
                return;
            }

            const opsi = { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 };

            let terbaik = null;
            let selesai = false;
            let penguat = null;

            const tutup = (hasil) => {
                if (selesai) return;
                selesai = true;
                if (penguat !== null) clearInterval(penguat);
                if (hasil) resolve(hasil);
            };

            const simpan = (p) => {
                const akurasi = p.coords.accuracy ?? Number.MAX_SAFE_INTEGER;

                if (!terbaik || akurasi < (terbaik.coords.accuracy ?? Number.MAX_SAFE_INTEGER)) {
                    terbaik = p;
                }

                // Akurasi sudah bagus sekali, tidak perlu menunggu sampel berikutnya.
                if (akurasi <= 25) tutup(p);
            };

            const gagal = (e) => {
                if (terbaik) {
                    tutup(terbaik);
                    return;
                }

                if (e.code === e.PERMISSION_DENIED) {
                    tutup(null);
                    reject(new Error('Izin lokasi ditolak. Aktifkan lokasi untuk situs ini lalu coba lagi.'));
                } else if (!selesai && typeof salkan === 'function') {
                    salkan('Mencari sinyal GPS yang lebih baik...');
                }
            };

            navigator.geolocation.getCurrentPosition(simpan, gagal, opsi);

            // Bacaan tambahan selama 8 detik pertama.
            penguat = setInterval(() => {
                navigator.geolocation.getCurrentPosition(simpan, () => {}, opsi);
            }, 1500);

            setTimeout(() => tutup(terbaik), 8000);
        });
    }

    async function kirim(arah) {
        if (sedang) return;
        sedang = true;
        tulis('Mengambil lokasi...');

        try {
            const p = await posisi((pesan) => tulis(pesan));
            const k = p.coords;
            const akurasi = k.accuracy ?? null;

            if (akurasi != null && akurasi > batas) {
                tulis('Sinyal GPS terlalu lemah (' + Math.round(akurasi) + ' m). Dekati jendela atau area terbuka, lalu coba lagi.', true);
                return;
            }

            if (akurasi != null && akurasi > 60) {
                tulis('Akurasi ' + Math.round(akurasi) + ' m, mengabsen...');
            } else {
                tulis('Mengirim ke server...');
            }

            const balasan = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    arah: arah,
                    latitude: k.latitude,
                    longitude: k.longitude,
                    accuracy: k.accuracy,
                    metode: 'qr',
                }),
            });

            const data = await balasan.json().catch(() => ({}));

            if (!balasan.ok) {
                tulis(data.pesan || 'Absen gagal disimpan.', true);
                return;
            }

            tulis(data.pesan || 'Tersimpan.', false);
            setTimeout(() => window.location.reload(), 900);

        } catch (galat) {
            tulis(galat.message || 'Terjadi kesalahan.', true);
        } finally {
            sedang = false;
        }
    }

    akar.querySelectorAll('button[data-arah]').forEach((tombol) => {
        tombol.addEventListener('click', () => kirim(tombol.dataset.arah));
    });
})();
</script>
@endpush
