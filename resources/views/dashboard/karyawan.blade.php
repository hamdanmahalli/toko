@extends('layouts.app')

@section('judul', 'Beranda')

@php
    use App\Enums\AbsenMasukStatus;

    $jam = now()->hour;

    $salam = match (true) {
        $jam < 11 => 'Selamat pagi',
        $jam < 15 => 'Selamat siang',
        $jam < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };

    $sudahMasuk = $hariIni?->jam_masuk !== null;

    // Sesi terakhir sudah tutup belum tentu berarti hari ini selesai: pada
    // shift interval masih ada sesi berikutnya yang harus diabsen.
    $sudahPulang = $semuaSesiTutup;

    // Status singkat untuk kartu utama.
    if ($sudahPulang) {
        $statusJudul = 'Absensi hari ini lengkap';
        $statusKeterangan = $hariIni->durasiKerjaLabel().' kerja tercatat';
        $statusWarna = 'bg-white/10 text-white ring-1 ring-white/25';
    } elseif ($hariIni?->status_masuk === AbsenMasukStatus::Terlambat) {
        $statusJudul = 'Terlambat hadir';
        $statusKeterangan = 'Masuk pukul '.$hariIni->jam_masuk->format('H:i');
        $statusWarna = 'bg-merah-600/60 text-white ring-1 ring-white/20';
    } elseif ($sudahMasuk) {
        $statusJudul = 'Sudah absen masuk';
        $statusKeterangan = 'Masuk pukul '.$hariIni->jam_masuk->format('H:i');
        $statusWarna = 'bg-white/10 text-white ring-1 ring-white/25';
    } elseif ($slot !== null) {
        $statusJudul = 'Belum absen masuk';
        $statusKeterangan = 'Batas telat '.$slot->batas_telat->format('H:i');
        $statusWarna = 'bg-white/10 text-white ring-1 ring-white/25';
    } else {
        $statusJudul = 'Belum absen masuk';
        $statusKeterangan = '';
        $statusWarna = 'bg-white/10 text-white ring-1 ring-white/25';
    }

    $tautan = array_values(array_filter([
        ['route' => 'absen.qr', 'label' => 'Kartu QR', 'show' => auth()->user()->can('absen.lihat')],
        ['route' => 'pengajuan.index', 'label' => 'Pengajuan', 'show' => Route::has('pengajuan.index') && auth()->user()->can('pengajuan.lihat')],
        ['route' => 'absen.riwayat', 'label' => 'Riwayat', 'show' => auth()->user()->can('absen.lihat')],
    ], fn ($t) => $t['show']));
@endphp

@section('konten')
<div class="masuk space-y-4 pb-4">
    <section class="overflow-hidden rounded-2xl border-t-4 border-merah-500 bg-brand-600 shadow-sm ring-1 ring-brand-700">
        <div class="px-5 pb-5 pt-4 text-white">
            <div class="flex items-center justify-between gap-3">
                <p class="text-[11px] uppercase tracking-wide text-brand-100/85">
                    {{ now()->translatedFormat('l, d F Y') }}
                </p>
                <div id="jam-hidup" data-server="{{ now()->format('H:i:s') }}" class="tabular shrink-0 text-sm font-semibold tracking-wide text-brand-50">
                    {{ now()->format('H:i:s') }}
                </div>
            </div>
            <h1 class="mt-2 break-words text-xl font-semibold leading-snug">{{ $salam }}, {{ $employee->nama }}</h1>
            <p class="mt-0.5 break-words text-xs text-brand-100/85">
                {{ $employee->position?->nama ?? 'Karyawan' }} · {{ $employee->shop->nama }}
            </p>

            @if ($statusKeterangan !== '')
                <div class="mt-4 flex items-center gap-3 rounded-2xl px-3 py-2.5 {{ $statusWarna }}">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/20">
                        @if ($sudahPulang)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        @elseif ($sudahMasuk)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold">{{ $statusJudul }}</p>
                        <p class="tabular truncate text-[11px] opacity-90">{{ $statusKeterangan }}</p>
                    </div>
                </div>
            @endif
        </div>

        @if ($slot)
            <dl class="grid grid-cols-3 gap-px bg-brand-800/40 text-center text-white">
                <div class="bg-brand-600 px-2 py-3">
                    <dt class="text-[11px] text-brand-100/85">Masuk</dt>
                    <dd class="tabular text-base font-semibold">{{ $slot->jam_masuk->format('H:i') }}</dd>
                </div>
                <div class="bg-brand-600 px-2 py-3">
                    <dt class="text-[11px] text-brand-100/85">Batas telat</dt>
                    <dd class="tabular text-base font-semibold">{{ $slot->batas_telat->format('H:i') }}</dd>
                </div>
                <div class="bg-brand-600 px-2 py-3">
                    <dt class="text-[11px] text-brand-100/85">Pulang</dt>
                    <dd class="tabular text-base font-semibold">{{ $slot->jam_pulang->format('H:i') }}</dd>
                </div>
            </dl>

            @unless ($pakaiTemplate)
                <p class="bg-brand-800/40 px-5 py-1.5 text-center text-[11px] text-brand-100/75">
                    Belum ada template shift
                </p>
            @endunless
        @endif

        @if ($tautan !== [])
            <div class="px-4 pb-1.5 pt-3">
                <div class="grid gap-2" style="grid-template-columns: repeat({{ count($tautan) }}, minmax(0, 1fr));">
                    @foreach ($tautan as $t)
                        <a href="{{ route($t['route']) }}"
                           class="rounded-xl bg-white/10 px-2 py-2.5 text-center text-xs font-medium text-white ring-1 ring-white/20 transition hover:bg-white/20 active:scale-[.98]">
                            {{ $t['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section class="grid grid-cols-3 gap-2.5">
        <div class="card masuk p-3.5" style="animation-delay:.05s">
            <p class="tabular text-xl font-semibold text-brand-700">{{ $statistik['hari'] }}</p>
            <p class="mt-0.5 text-[11px] leading-tight text-slate-500">Hari hadir</p>
            <p class="text-[10px] uppercase tracking-wide text-brand-400">{{ $statistik['bulan'] }}</p>
        </div>
        <div class="card masuk p-3.5" style="animation-delay:.1s">
            <p class="tabular text-xl font-semibold text-brand-700">
                {{ rtrim(rtrim(number_format($statistik['jam'], 1, ',', '.'), '0'), ',') }}
            </p>
            <p class="mt-0.5 text-[11px] leading-tight text-slate-500">Jam kerja bulan ini</p>
        </div>
        <div class="card masuk p-3.5" style="animation-delay:.15s">
            <p class="tabular text-xl font-semibold {{ $statistik['terlambat'] > 0 ? 'text-merah-600' : 'text-slate-900' }}">
                {{ $statistik['terlambat'] }}
            </p>
            <p class="mt-0.5 text-[11px] leading-tight text-slate-500">Kali terlambat</p>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card masuk p-5" style="animation-delay:.2s">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-[15px] text-slate-900">Absensi hari ini</h2>
                <span class="flex flex-wrap items-center gap-1.5">
                    @if ($hariIni && $sudahMasuk && ! $sudahPulang)
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700 ring-1 ring-brand-100">
                            Sudah absen masuk
                        </span>
                    @endif
                    @if ($pending > 0)
                        <span class="rounded-full bg-merah-50 px-2 py-0.5 text-[11px] font-medium text-merah-700 ring-1 ring-merah-100">
                            {{ $pending }} pengajuan menunggu
                        </span>
                    @endif
                </span>
            </div>

            @if ($hariIni === null)
                <p class="rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                    Belum ada catatan hari ini.
                </p>
            @else
                @if ($sesiHariIni->count() > 1)
                    {{-- Shift interval: tampilkan semua sesi supaya yang tadi
                         sudah selesai tidak terlihat seolah-olah belum absen. --}}
                    <div class="mb-3 space-y-1.5">
                        @foreach ($sesiHariIni as $sesi)
                            <div @class([
                                'flex flex-wrap items-center justify-between gap-2 rounded-xl px-3 py-2 text-xs',
                                'bg-brand-50 ring-1 ring-brand-200' => $sesi->is($hariIni),
                                'bg-slate-50' => ! $sesi->is($hariIni),
                            ])>
                                <span class="font-medium text-slate-700">
                                    {{ $sesi->shift_label_masuk ? $sesi->shift_label_masuk : 'Sesi '.$sesi->sesi }}
                                </span>
                                <span class="tabular text-slate-500">
                                    {{ $sesi->jam_masuk?->format('H:i') ?? '-' }}
                                    &ndash;
                                    {{ $sesi->jam_pulang?->format('H:i') ?? '-' }}
                                    @if ($sesi->durasiKerjaLabel())
                                        · {{ $sesi->durasiKerjaLabel() }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-brand-50/70 px-3 py-2.5">
                        <p class="text-[11px] text-slate-500">Masuk</p>
                        <p class="tabular mt-0.5 font-display text-xl text-slate-900">
                            {{ $hariIni->jam_masuk?->format('H:i') ?? '-' }}
                        </p>
                        @if ($hariIni->status_masuk)
                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-medium {{ $hariIni->status_masuk->badgeClass() }}">
                                {{ $hariIni->status_masuk->label() }}
                            </span>
                        @endif
                    </div>
                    <div class="rounded-xl bg-brand-50/70 px-3 py-2.5">
                        <p class="text-[11px] text-slate-500">Pulang</p>
                        <p class="tabular mt-0.5 font-display text-xl text-slate-900">
                            {{ $hariIni->jam_pulang?->format('H:i') ?? '-' }}
                        </p>
                        @if ($hariIni->status_pulang)
                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-medium {{ $hariIni->status_pulang->badgeClass() }}">
                                {{ $hariIni->status_pulang->label() }}
                            </span>
                        @else
                            <span class="mt-1 inline-block rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                Belum pulang
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        <section class="card masuk p-5" style="animation-delay:.25s">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="font-display text-[15px] text-slate-900">14 hari terakhir</h2>
                {{-- Disembunyikan kalau akun tidak boleh membuka halaman riwayat,
                     supaya tidak ada tautan yang pasti berakhir dengan 403. --}}
                @if (auth()->user()->can('absen.lihat'))
                    <a href="{{ route('absen.riwayat') }}" class="text-[11px] font-medium text-brand-600 hover:text-brand-700">
                        Semua riwayat
                    </a>
                @endif
            </div>

            @if ($riwayat->isEmpty())
                <p class="rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                    Belum ada riwayat.
                </p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($riwayat->take(6) as $a)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-slate-50 text-center">
                                    <span class="tabular text-sm font-semibold leading-none text-slate-700">
                                        {{ $a->tanggal->format('d') }}
                                    </span>
                                    <span class="text-[9px] uppercase leading-none text-slate-400">
                                        {{ $a->tanggal->translatedFormat('M') }}
                                    </span>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">
                                        {{ $a->tanggal->translatedFormat('D, d M') }}
                                    </p>
                                    <p class="tabular truncate text-[11px] text-slate-500">
                                        {{ $a->jam_masuk?->format('H:i') ?? '-' }} → {{ $a->jam_pulang?->format('H:i') ?? '-' }}
                                        @if ($a->durasiKerja())
                                            · {{ number_format($a->durasiKerja(), 1, ',', '.') }} jam
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if ($a->status_masuk)
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $a->status_masuk->badgeClass() }}">
                                    {{ $a->status_masuk->label() }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>

{{-- Tombol absen hanya untuk akun yang boleh mencatat. Akun yang hanya
     boleh melihat riwayat tetap punya halaman beranda, tanpa tombol. --}}
@if (auth()->user()->can('absen.catat'))
    @include('absen.tombol')
@endif
@endsection

@push('kaki')
<script>
(() => {
    const el = document.getElementById('jam-hidup');
    if (!el) return;

    const bagian = el.dataset.server.split(':');
    if (bagian.length !== 3) return;

    const server = new Date();
    server.setHours(+bagian[0], +bagian[1], +bagian[2], 0);
    const selisih = server.getTime() - Date.now();
    const dua = (n) => String(n).padStart(2, '0');

    const catat = () => {
        const t = new Date(Date.now() + selisih);
        el.textContent = dua(t.getHours()) + ':' + dua(t.getMinutes()) + ':' + dua(t.getSeconds());
    };

    catat();
    setInterval(catat, 1000);
})();
</script>
@endpush