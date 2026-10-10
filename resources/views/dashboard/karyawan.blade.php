@extends('layouts.app')

@section('judul', 'Beranda')

@php
    use App\Enums\AbsenMasukStatus;

    $sudahMasuk = $hariIni?->jam_masuk !== null;

    // Sesi terakhir sudah tutup belum tentu berarti hari ini selesai: pada
    // shift interval masih ada sesi berikutnya yang harus diabsen.
    $sudahPulang = $semuaSesiTutup;

    $terlambat = $hariIni?->status_masuk === AbsenMasukStatus::Terlambat;

    $bolehCatat = auth()->user()->can('absen.catat');
    $bolehRiwayat = Route::has('absen.riwayat') && auth()->user()->can('absen.lihat');
    $bolehPengajuan = Route::has('pengajuan.index') && auth()->user()->can('pengajuan.lihat');
    $bolehKas = Route::has('kas.index') && auth()->user()->can('kas.lihat');

    // Judul kartu status mengikuti keadaan absensi hari ini. Terlambat tetap
    // ditandai lewat lencana, bukan mengganti judulnya.
    if ($sudahPulang) {
        $statusJudul = 'Absensi hari ini lengkap';
        $statusKeterangan = trim(($hariIni?->durasiKerjaLabel() ?? '').' kerja tercatat');
    } elseif ($sudahMasuk) {
        $statusJudul = 'Sudah absen masuk';
        $statusKeterangan = 'Masuk pukul '.$hariIni->jam_masuk->format('H:i');
    } else {
        $statusJudul = 'Belum absen masuk';
        $statusKeterangan = $slot ? 'Jadwal masuk '.$slot->jam_masuk->format('H:i') : 'Selamat bekerja';
    }

    $foto = $employee->fotoUrl();

    // Pintasan menu, disaring sesuai hak akses akun.
    $menuCepat = array_values(array_filter([
        ['route' => 'absen.qr', 'label' => 'Kartu QR', 'tampil' => auth()->user()->can('absen.lihat'),
            'ikon' => 'M6 5h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm4 4.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-2.5 7a3 3 0 0 1 5 0M15 8h2M15 11h2M15 14h2'],
        ['route' => 'absen.riwayat', 'label' => 'Riwayat', 'tampil' => auth()->user()->can('absen.lihat'),
            'ikon' => 'M4 6h16M4 12h16M4 18h10'],
        ['route' => 'pengajuan.index', 'label' => 'Pengajuan', 'tampil' => $bolehPengajuan,
            'ikon' => 'M8 3v3m8-3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z'],
        ['route' => 'kas.index', 'label' => 'Kas', 'tampil' => $bolehKas,
            'ikon' => 'M3 7h18v10H3V7Zm0 3h18M7 13.5h3'],
        ['route' => 'profil.index', 'label' => 'Profil', 'tampil' => true,
            'ikon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 0c-3.3 0-6 1.8-6 4v1h12v-1c0-2.2-2.7-4-6-4Z'],
    ], fn ($m) => $m['tampil']));
@endphp

@section('konten')
<div class="masuk pb-4">

    {{-- Baris profil. --}}
    <section class="-mx-4 -mt-4 rounded-b-[28px] bg-gradient-to-b from-brand-600 to-brand-700 px-4 pb-12 pt-6 text-white sm:px-6">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('profil.index') }}" class="flex min-w-0 items-center gap-3">
                <span class="h-12 w-12 shrink-0 overflow-hidden rounded-full bg-white ring-2 ring-white/30">
                    @if ($foto)
                        <img src="{{ $foto }}" alt="{{ $employee->nama }}" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-sm font-bold text-brand-700">
                            {{ $employee->initials() }}
                        </span>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-[15px] font-bold text-white">{{ $employee->nama }}</span>
                    <span class="block truncate text-xs text-white/70">
                        {{ $employee->position?->nama ?? 'Karyawan' }} · {{ $employee->shop->nama }}
                    </span>
                </span>
            </a>

            {{-- Titik merah muncul hanya bila ada pengajuan menunggu. --}}
            @if ($bolehPengajuan)
                <a href="{{ route('pengajuan.index') }}" aria-label="Notifikasi"
                   class="relative grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/15 ring-1 ring-white/20 backdrop-blur transition hover:bg-white/25 active:scale-95">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17H9m6 0a3 3 0 0 1-6 0m6 0h2.5a1.5 1.5 0 0 0 1.1-2.5l-.9-1A3 3 0 0 1 17 11.5V11a5 5 0 0 0-10 0v.5a3 3 0 0 1-.7 2l-.9 1A1.5 1.5 0 0 0 6.5 17H9"/>
                    </svg>
                    @if ($pending > 0)
                        <span class="absolute right-1.5 top-1.5 h-2.5 w-2.5 rounded-full bg-merah-400 ring-2 ring-brand-600"></span>
                    @endif
                </a>
            @else
                <span aria-hidden="true"
                      class="relative grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/15 ring-1 ring-white/20 backdrop-blur">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17H9m6 0a3 3 0 0 1-6 0m6 0h2.5a1.5 1.5 0 0 0 1.1-2.5l-.9-1A3 3 0 0 1 17 11.5V11a5 5 0 0 0-10 0v.5a3 3 0 0 1-.7 2l-.9 1A1.5 1.5 0 0 0 6.5 17H9"/>
                    </svg>
                </span>
            @endif
        </div>
    </section>

    {{-- Kartu status absensi. --}}
    <div class="relative -mt-8 rounded-[24px] bg-white p-5 shadow-lg shadow-brand-900/10 ring-1 ring-black/5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Absensi hari ini</p>
                <p class="mt-1 text-2xl font-bold leading-tight text-slate-900 sm:text-3xl">
                    {{ $statusJudul }}
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ $statusKeterangan }}</p>

                @if ($terlambat && ! $sudahPulang)
                    <span class="mt-2 inline-block rounded-full bg-merah-50 px-2 py-0.5 text-[11px] font-semibold text-merah-700 ring-1 ring-merah-100">
                        Terlambat
                    </span>
                @endif

                <p id="absen-status" hidden class="mt-2 text-xs leading-snug text-slate-500"></p>
            </div>

            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600 ring-1 ring-brand-100 sm:h-16 sm:w-16">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5H8"/>
                </svg>
            </span>
        </div>

        @if ($sudahPulang)
            @if ($bolehRiwayat)
                <a href="{{ route('absen.riwayat') }}"
                   class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-700 ring-1 ring-brand-100 transition hover:bg-brand-100 active:scale-[.99]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/>
                    </svg>
                    Lihat riwayat
                </a>
            @endif
        @elseif ($bolehCatat)
            {{-- Mengetuk tombol ini menjalankan absen langsung: lokasi diambil
                 lalu dikirim ke server. Keputusan akhir tetap di server. --}}
            <button type="button" data-absen-arah="{{ $sudahMasuk ? 'pulang' : 'masuk' }}"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-sm transition active:scale-[.99]
                    {{ $sudahMasuk ? 'bg-merah-600 shadow-merah-900/20 hover:bg-merah-700' : 'bg-brand-600 shadow-brand-900/20 hover:bg-brand-700' }}">
                @if ($sudahMasuk)
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4m0 0l4-4m-4 4l4 4M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/>
                    </svg>
                    Absen pulang
                @else
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h11m0 0l-4-4m4 4l-4 4M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/>
                    </svg>
                    Absen masuk
                @endif
            </button>
        @endif
    </div>

    {{-- Akses cepat. --}}
    @if ($menuCepat !== [])
        <section class="mt-4 rounded-[24px] bg-white p-3 shadow-sm ring-1 ring-black/5" style="animation-delay:.1s">
            <div class="grid gap-1" style="grid-template-columns: repeat({{ count($menuCepat) }}, minmax(0, 1fr));">
                @foreach ($menuCepat as $m)
                    <a href="{{ route($m['route']) }}"
                       class="flex flex-col items-center gap-2 rounded-2xl px-1 py-2.5 transition hover:bg-brand-50 active:scale-[.97]">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-brand-50 text-brand-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['ikon'] }}"/>
                            </svg>
                        </span>
                        <span class="text-[11px] font-medium text-slate-600">{{ $m['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Jadwal dan catatan hari ini. --}}
    <section class="mt-4 rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-black/5" style="animation-delay:.15s">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="font-display text-[15px] text-slate-900">Hari ini</h2>
            @if ($pending > 0)
                <span class="rounded-full bg-merah-50 px-2 py-0.5 text-[11px] font-medium text-merah-700 ring-1 ring-merah-100">
                    {{ $pending }} pengajuan menunggu
                </span>
            @endif
        </div>

        @if ($slot)
            <div class="grid grid-cols-3 gap-2">
                <div class="rounded-2xl bg-brand-50/60 px-3 py-2.5 text-center">
                    <p class="text-[11px] text-slate-500">Masuk</p>
                    <p class="tabular mt-0.5 text-base font-semibold text-slate-900">{{ $slot->jam_masuk?->format('H:i') ?? '-' }}</p>
                </div>
                <div class="rounded-2xl bg-brand-50/60 px-3 py-2.5 text-center">
                    <p class="text-[11px] text-slate-500">Batas telat</p>
                    <p class="tabular mt-0.5 text-base font-semibold text-slate-900">{{ $slot->batas_telat?->format('H:i') ?? '-' }}</p>
                </div>
                <div class="rounded-2xl bg-brand-50/60 px-3 py-2.5 text-center">
                    <p class="text-[11px] text-slate-500">Pulang</p>
                    <p class="tabular mt-0.5 text-base font-semibold text-slate-900">{{ $slot->jam_pulang?->format('H:i') ?? '-' }}</p>
                </div>
            </div>

            @unless ($pakaiTemplate)
                <p class="mt-2 rounded-2xl bg-merah-50 px-3 py-2 text-[11px] text-merah-700 ring-1 ring-merah-100">
                    Belum ada template shift. Memakai jam bawaan toko.
                </p>
            @endunless
        @endif

        @if ($sesiHariIni->isEmpty())
            <p class="mt-3 rounded-2xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                Belum ada catatan hari ini.
            </p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($sesiHariIni as $sesi)
                    <li class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-2.5">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-slate-700">
                                {{ $sesi->shift_label_masuk ?? 'Sesi '.$sesi->sesi }}
                            </span>
                            <span class="tabular block text-[11px] text-slate-500">
                                {{ $sesi->jam_masuk?->format('H:i') ?? '-' }} - {{ $sesi->jam_pulang?->format('H:i') ?? 'belum pulang' }}
                            </span>
                        </span>
                        @if ($sesi->status_masuk)
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $sesi->status_masuk->badgeClass() }}">
                                {{ $sesi->status_masuk->label() }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Ringkasan bulan berjalan. --}}
    <section class="mt-4 rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-black/5" style="animation-delay:.2s">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="font-display text-[15px] text-slate-900">Bulan ini</h2>
            <span class="text-[11px] font-medium text-slate-400">{{ $statistik['bulan'] }}</span>
        </div>

        <div class="grid grid-cols-3 divide-x divide-slate-100 text-center">
            <div class="px-1">
                <p class="text-xl font-bold text-brand-700">{{ $statistik['hari'] }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Hari hadir</p>
            </div>
            <div class="px-1">
                <p class="text-xl font-bold text-brand-700">{{ $statistik['jam'] }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Jam kerja</p>
            </div>
            <div class="px-1">
                <p class="text-xl font-bold {{ $statistik['terlambat'] > 0 ? 'text-merah-600' : 'text-brand-700' }}">{{ $statistik['terlambat'] }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Kali terlambat</p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('kaki')
<script>
// Absen karyawan dijalankan dari kartu status: lokasi GPS dikumpulkan lalu
// dikirim ke server. Batas akurasi dan keputusan akhir tetap di server.
(() => {
    const tombol = document.querySelectorAll('[data-absen-arah]');
    if (tombol.length === 0) return;

    const status = document.getElementById('absen-status');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const url = @json(route('absen.catat'));

    // Batas keras hanya untuk buangan lokasi yang sama sekali tidak berguna.
    const batas = @json((int) \App\Models\Setting::ambilInt('geofence.akurasi_maks_meter', 500));

    let sedang = false;
    let sembunyi = null;

    function tulis(pesan, galat = false) {
        status.textContent = pesan;
        status.className = 'mt-2 text-xs leading-snug ' + (galat ? 'text-merah-600' : 'text-slate-500');
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

    tombol.forEach((t) => {
        t.addEventListener('click', () => kirim(t.dataset.absenArah));
    });
})();
</script>
@endpush
