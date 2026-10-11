@extends('layouts.app')

@section('judul', 'Kas')

@section('konten')
    <x-page-header class="mb-4" judul="Buku Kas" sub="Catatan uang masuk dan keluar usahamu.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8V7a2 2 0 0 1 2-2h11M16 12h2"/>
            </svg>
        </x-slot:ikon>
        <x-slot:aksi>
            @can('kas.buat')
                <a href="{{ route('kas.create') }}" aria-label="Buku baru" title="Buku baru"
                   class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm shadow-brand-600/20 transition hover:brightness-105 active:scale-[.99]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                </a>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-3 flex items-center justify-between gap-3 rounded-[24px] bg-gradient-to-br from-brand-600 to-brand-700 px-5 py-4 text-white shadow-md shadow-brand-900/20">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">Total saldo</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ \App\Support\Rupiah::format($totalSaldo) }}</p>
        </div>
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8V7a2 2 0 0 1 2-2h11M16 12h2"/>
            </svg>
        </span>
    </div>

    <div class="mb-4 flex gap-2">
        @can('kas.buat')
            <a href="{{ route('kas.kategori.index') }}"
               class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/>
                </svg>
                Kategori
            </a>
        @endcan
        <a href="{{ route('kas.laporan') }}"
           class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 19v-6m4 6V9m4 10v-3"/>
            </svg>
            Laporan
        </a>
    </div>

    @if ($buku->isEmpty())
        <p class="card px-4 py-10 text-center text-sm text-slate-500">
            Belum ada buku kas. Buat buku pertama untuk mulai mencatat.
        </p>
    @else
        <div class="space-y-2">
            @foreach ($buku as $b)
                <a href="{{ route('kas.show', $b) }}" class="card block p-4 transition hover:bg-brand-50/40">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <span class="truncate">{{ $b->nama }}</span>
                                @unless ($b->aktif)
                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                        Nonaktif
                                    </span>
                                @endunless
                            </p>
                            @if ($b->keterangan)
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $b->keterangan }}</p>
                            @endif
                            <p class="tabular mt-1 text-[11px] text-slate-400">
                                Masuk {{ \App\Support\Rupiah::format($b->total_masuk ?? 0) }}
                                &middot; Keluar {{ \App\Support\Rupiah::format($b->total_keluar ?? 0) }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[11px] text-slate-400">Saldo</p>
                            <p class="tabular text-base font-semibold text-slate-900">
                                {{ \App\Support\Rupiah::format($b->saldoSaatIni()) }}
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
