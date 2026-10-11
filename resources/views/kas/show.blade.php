@extends('layouts.app')

@php
    use App\Enums\JenisKas;
@endphp

@section('judul', $buku->nama)

@section('konten')
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('kas.index') }}" aria-label="Kembali"
           class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/>
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="truncate font-display text-xl text-slate-900">{{ $buku->nama }}</h1>
            @if ($buku->keterangan)
                <p class="truncate text-xs text-slate-500">{{ $buku->keterangan }}</p>
            @endif
        </div>
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
    </div>

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
            <div class="space-y-2">
                @foreach ($transaksi as $t)
                    @php $masuk = $t->jenis === JenisKas::Masuk; @endphp
                    <div class="card flex items-start justify-between gap-3 p-3.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800">{{ $t->labelKategori($petaKategori) }}</p>
                            <p class="tabular mt-0.5 text-[11px] text-slate-500">{{ $t->tanggal->format('d/m/Y') }}</p>
                            @if ($t->keterangan)
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $t->keterangan }}</p>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="tabular text-sm font-semibold {{ $masuk ? 'text-brand-700' : 'text-merah-600' }}">
                                {{ $masuk ? '+' : '−' }} {{ \App\Support\Rupiah::format($t->jumlah) }}
                            </p>
                            @can('kas.buat')
                                <form method="POST" action="{{ route('kas.transaksi.destroy', [$buku, $t]) }}" class="mt-1"
                                      onsubmit="return confirm('Hapus transaksi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-[11px] font-medium text-slate-400 transition hover:text-merah-600">Hapus</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @can('kas.buat')
        <a href="{{ route('kas.transaksi.create', $buku) }}"
           class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-3 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Catat transaksi
        </a>
    @endcan
@endsection
