@extends('layouts.presensi', ['judul' => 'Presensi Karyawan'])

@section('konten')
    {{-- Arah absensi berikutnya ikut ditampilkan supaya operator tahu kartu
         yang dipindai akan dicatat sebagai masuk atau pulang. Nilainya berasal
         dari sesi karyawan yang masih terbuka, ditentukan server. --}}
    @php($arah = session('arah'))

    <div class="flex items-start justify-between gap-3 rounded-2xl bg-brand-600 px-4 py-3 text-white shadow-sm">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-100">Scan untuk</p>
            <p class="font-display text-2xl leading-tight">
                {{ $arah === 'pulang' ? 'Absen Pulang' : 'Absen Masuk' }}
            </p>
        </div>
        <div class="shrink-0 text-right">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-100">Toko</p>
            <p class="text-[13px] font-medium leading-tight">{{ $shop->nama }}</p>
        </div>
    </div>

    @include('layouts.pesan')

    <form method="POST" action="{{ route('presensi.proses', $shop->kode) }}" class="card space-y-4 p-5">
        @csrf

        <div>
            <label for="token" class="mb-1.5 block text-[13px] font-medium text-slate-600">Pindai kartu</label>

            {{-- Input ini yang dibaca scanner USB (keyboard wedge): karakter
                 tiba cepat lalu Enter ditekan otomatis. autocomplete dan
                 spellcheck dimatikan supaya tidak ada koreksi otomatis yang
                 merusak kode. --}}
            <input id="token" name="token" type="text" required autofocus autocomplete="off"
                   autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="none"
                   value="{{ old('token') }}"
                   placeholder="Pindai kartu atau ketik kode"
                   class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3 text-base outline-none transition
                          placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">

            <p class="mt-2 text-[12px] leading-relaxed text-slate-500">
                Tahan kartu di depan scanner sampai isinya muncul, lalu tekan Enter. Kode juga
                bisa diketik manual kalau barcode rusak.
            </p>
        </div>

        {{-- Hanya tampil di dalam APK; memindai kartu pakai kamera HP. --}}
        <button type="button" id="scan-kamera" hidden
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-brand-200 bg-white px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M3 12h18"/>
            </svg>
            Scan dengan kamera
        </button>

        <button type="submit"
                class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
            Catat Absensi
        </button>
    </form>

    <div class="flex flex-wrap items-center justify-between gap-2 text-[12px] text-slate-400">
        <p>
            @if ($menitSisa > 0)
                Perangkat presensi mengunci sendiri dalam {{ $menitSisa }} menit tanpa dipakai.
            @else
                Perangkat presensi terbuka sampai dipakai atau dikunci manual.
            @endif
        </p>

        {{-- Dipisah dari form pindai supaya tidak ikut terkirim sebagai POST. --}}
        <form method="POST" action="{{ route('presensi.keluar', $shop->kode) }}">
            @csrf
            <button type="submit" class="underline decoration-slate-300 underline-offset-2 hover:text-slate-600">
                Kunci perangkat
            </button>
        </form>
    </div>

    <p class="text-center text-[12px] text-slate-400">
        <a href="{{ route('panduan') }}"
           class="underline decoration-slate-300 underline-offset-2 hover:text-slate-600">
            Butuh bantuan? Baca panduan
        </a>
    </p>
@endsection

@push('scripts')
    <script>
        // Fokus dikembalikan ke input setelah halaman selesai dimuat ulang.
        // Tanpa ini, setelah satu pemindaian operator harus klik dulu sebelum
        // memindai kartu berikutnya.
        window.addEventListener('load', function () {
            const input = document.getElementById('token');
            if (input) {
                input.focus();
            }

            // Di dalam APK, tampilkan pemindai kamera sebagai alternatif
            // scanner USB. Hasilnya masuk ke input yang sama lalu dikirim.
            const tombol = document.getElementById('scan-kamera');
            if (tombol && window.TokoNative && window.TokoNative.aktif) {
                tombol.hidden = false;
                tombol.addEventListener('click', function () {
                    tombol.disabled = true;
                    window.TokoNative.scanKartu().then(function (nilai) {
                        if (nilai) {
                            const inp = document.getElementById('token');
                            inp.value = nilai;
                            inp.form.submit();
                        }
                    }).catch(function (e) {
                        window.TokoDialog.pesan({
                            judul: 'Pemindaian gagal',
                            pesan: e && e.message ? e.message : 'Pemindaian gagal.',
                        });
                    }).finally(function () {
                        tombol.disabled = false;
                    });
                });
            }
        });
    </script>
@endpush