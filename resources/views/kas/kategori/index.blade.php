@extends('layouts.app')

@section('judul', 'Kategori kas')

@section('konten')
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('kas.index') }}" aria-label="Kembali"
           class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/>
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="truncate font-display text-xl text-slate-900">Kategori</h1>
            <p class="truncate text-xs text-slate-500">Atur pilihan kategori saat mencatat transaksi.</p>
        </div>
        @can('kas.buat')
            <a href="{{ route('kas.kategori.create') }}"
               class="flex shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Tambah
            </a>
        @endcan
    </div>

    @foreach ([['label' => 'Pemasukan', 'items' => $masuk], ['label' => 'Pengeluaran', 'items' => $keluar]] as $grup)
        <section class="mb-4">
            <h2 class="mb-2 font-display text-[15px] text-slate-900">{{ $grup['label'] }}</h2>

            @if ($grup['items']->isEmpty())
                <p class="card px-4 py-8 text-center text-sm text-slate-500">
                    Belum ada kategori {{ strtolower($grup['label']) }}.
                </p>
            @else
                <div class="space-y-2">
                    @foreach ($grup['items'] as $k)
                        <div class="card flex items-center justify-between gap-3 p-3.5">
                            <div class="min-w-0">
                                <p class="flex items-center gap-2 text-sm font-medium text-slate-800">
                                    <span class="truncate">{{ $k->nama }}</span>
                                    @unless ($k->aktif)
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                                    @endunless
                                </p>
                                <p class="truncate text-[11px] text-slate-400">{{ $k->kode }}</p>
                            </div>
                            @can('kas.buat')
                                <div class="flex shrink-0 items-center gap-3">
                                    <a href="{{ route('kas.kategori.edit', $k) }}" class="text-sm font-medium text-brand-600 hover:underline">Ubah</a>
                                    <form method="POST" action="{{ route('kas.kategori.destroy', $k) }}"
                                          onsubmit="return confirm('Hapus kategori {{ $k->nama }}? Kategori yang masih dipakai akan dinonaktifkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm font-medium text-merah-600 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
@endsection
