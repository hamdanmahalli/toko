@extends('layouts.app')

@php
    $ubah = $buku->exists;
    $angkaAwal = old('saldo_awal', $buku->exists ? (int) $buku->saldo_awal : '');
@endphp

@section('judul', $ubah ? 'Ubah buku kas' : 'Buku kas baru')

@section('konten')
    <x-page-header class="mb-4" :judul="$ubah ? 'Ubah buku kas' : 'Buku kas baru'"
                   sub="Beri nama buku supaya mudah dibedakan.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8V7a2 2 0 0 1 2-2h11M16 12h2"/>
            </svg>
        </x-slot:ikon>
    </x-page-header>

    <form method="POST" action="{{ $ubah ? route('kas.update', $buku) : route('kas.store') }}" class="space-y-4">
        @csrf
        @if ($ubah)
            @method('PUT')
        @endif

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="nama">Nama buku</label>
            <input id="nama" name="nama" type="text" required maxlength="120" value="{{ old('nama', $buku->nama) }}"
                   placeholder="Contoh: Kas Warung"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="saldo_awal">Saldo awal</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
                <input id="saldo_awal" name="saldo_awal" type="text" inputmode="numeric" autocomplete="off"
                       data-format-ribuan value="{{ $angkaAwal }}" placeholder="0"
                       class="tabular w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-12 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <button type="button" data-kalkulator-buka="saldo_awal" aria-label="Buka kalkulator"
                        class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm2.5 4h7M8.5 11h.01M12 11h.01M15.5 11h.01M8.5 14.5h.01M12 14.5h.01M15.5 14.5h.01M8.5 18h.01M12 18h.01M15.5 18h.01"/>
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Uang yang sudah ada di kas sebelum mulai mencatat.</p>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" rows="3" maxlength="500" placeholder="Opsional"
                      class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('keterangan', $buku->keterangan) }}</textarea>
        </div>

        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
            <input type="hidden" name="aktif" value="0">
            <input id="aktif" name="aktif" type="checkbox" value="1" @checked(old('aktif', $buku->aktif ?? true))
                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            <span class="text-sm text-slate-700">Buku aktif</span>
        </label>

        <div class="flex gap-2">
            <a href="{{ $ubah ? route('kas.show', $buku) : route('kas.index') }}"
               class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Batal
            </a>
            <button class="flex-1 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                {{ $ubah ? 'Simpan perubahan' : 'Buat buku' }}
            </button>
        </div>
    </form>

    @if ($ubah)
        <form method="POST" action="{{ route('kas.destroy', $buku) }}" class="mt-4"
              onsubmit="return confirm('Hapus buku ini beserta seluruh transaksinya?')">
            @csrf
            @method('DELETE')
            <button class="w-full rounded-xl border border-merah-100 bg-merah-50 px-4 py-2.5 text-sm font-medium text-merah-700 transition hover:bg-merah-100">
                Hapus buku
            </button>
        </form>
    @endif

    @include('components.kalkulator')
    @include('components.format-ribuan')
@endsection
