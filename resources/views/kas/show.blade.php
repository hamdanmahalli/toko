@extends('layouts.app')

@php
    use App\Enums\JenisKas;
@endphp

@section('judul', $buku->nama)

@section('konten')
    <x-page-header class="mb-4" :judul="$buku->nama" :sub="$buku->keterangan"
                   :kembali="route('kas.index')">
        <x-slot:aksi>
            @can('kas.buat')
                <div class="flex shrink-0 items-center gap-1">
                    <a href="{{ route('kas.laporan', ['buku' => $buku->id]) }}" aria-label="Laporan buku ini"
                       class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 19v-6m4 6V9m4 10v-3"/>
                        </svg>
                    </a>
                    <a href="{{ route('kas.edit', $buku) }}" aria-label="Ubah buku"
                       class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                        </svg>
                    </a>
                </div>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-4 rounded-[24px] bg-gradient-to-br from-brand-600 to-brand-700 px-5 py-4 text-white shadow-md shadow-brand-900/20">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">Saldo saat ini</p>
        <p class="tabular mt-1 text-3xl font-bold">{{ \App\Support\Rupiah::format($ringkasan['saldo']) }}</p>
        <div class="mt-3 flex gap-4 text-xs">
            <span class="text-white/80">Masuk <span class="tabular font-semibold text-white">{{ \App\Support\Rupiah::format($ringkasan['masuk']) }}</span></span>
            <span class="text-white/80">Keluar <span class="tabular font-semibold text-white">{{ \App\Support\Rupiah::format($ringkasan['keluar']) }}</span></span>
        </div>
    </div>

    <section>
        <h2 class="mb-2 font-display text-[15px] text-slate-900">Transaksi</h2>

        @if ($transaksi->isEmpty())
            <p class="card px-4 py-8 text-center text-sm text-slate-500">
                Belum ada transaksi pada buku ini.
            </p>
        @else
            @foreach ($transaksi->groupBy(fn ($t) => $t->tanggal->toDateString()) as $tanggal => $items)
                @php
                    $tgl = \Illuminate\Support\Carbon::parse($tanggal);
                    $labelTanggal = $tgl->isToday()
                        ? 'Hari ini'
                        : ($tgl->isYesterday() ? 'Kemarin' : $tgl->translatedFormat('l, d F Y'));
                @endphp

                <div class="mb-3">
                    <p class="mb-1.5 px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $labelTanggal }}</p>

                    <div class="space-y-2">
                        @foreach ($items as $t)
                            @php
                                $masuk = $t->jenis === JenisKas::Masuk;
                                $label = $t->labelKategori($petaKategori);
                            @endphp

                            @can('kas.buat')
                                <a href="{{ route('kas.transaksi.edit', [$buku, $t]) }}"
                                   class="card flex items-center justify-between gap-3 p-3.5 transition hover:bg-brand-50/40">
                            @else
                                <div class="card flex items-center justify-between gap-3 p-3.5">
                            @endcan
                                    <div class="flex min-w-0 items-center gap-3">
                                        @if ($t->gambar)
                                            <img src="{{ $t->gambarUrl() }}" alt="Bukti transaksi"
                                                 class="h-11 w-11 shrink-0 rounded-lg object-cover ring-1 ring-slate-100">
                                        @endif
                                        <div class="min-w-0">
                                            @if ($t->keterangan)
                                                <p class="truncate text-sm font-medium text-slate-800">{{ $t->keterangan }}</p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $label }}</p>
                                            @else
                                                <p class="truncate text-sm font-medium text-slate-800">{{ $label }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="tabular shrink-0 text-sm font-semibold {{ $masuk ? 'text-brand-700' : 'text-merah-600' }}">
                                        {{ $masuk ? '+' : '−' }} {{ \App\Support\Rupiah::format($t->jumlah) }}
                                    </p>
                            @can('kas.buat')
                                </a>
                            @else
                                </div>
                            @endcan
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </section>

    @can('kas.buat')
        <div class="h-16 sm:hidden"></div>

        <div class="fixed inset-x-0 z-10 px-4 sm:static sm:mb-4 sm:px-0"
             style="bottom: calc(env(safe-area-inset-bottom) + 4.5rem)">
            <div class="mx-auto max-w-5xl">
                <a href="{{ route('kas.transaksi.create', $buku) }}"
                   class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/20 transition hover:brightness-105 active:scale-[.99]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    Catat transaksi
                </a>
            </div>
        </div>
    @endcan
@endsection
