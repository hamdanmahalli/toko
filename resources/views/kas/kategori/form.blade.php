@extends('layouts.app')

@php
    $ubah = $kategori->exists;
    $jenisAwal = old('jenis', $kategori->jenis?->value ?? 'masuk');
@endphp

@section('judul', $ubah ? 'Ubah kategori' : 'Kategori baru')

@section('konten')
    <x-page-header class="mb-4" :judul="$ubah ? 'Ubah kategori' : 'Kategori baru'"
                   sub="Kategori ini hanya berlaku untuk akunmu." :kembali="route('kas.kategori.index')" />

    <form method="POST" action="{{ $ubah ? route('kas.kategori.update', $kategori) : route('kas.kategori.store') }}" class="space-y-4">
        @csrf
        @if ($ubah)
            @method('PUT')
        @endif

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="nama">Nama kategori</label>
            <input id="nama" name="nama" type="text" required maxlength="120" value="{{ old('nama', $kategori->nama) }}"
                   placeholder="Contoh: Beli stok"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <span class="mb-1.5 block text-sm font-medium text-slate-700">Jenis</span>
            <div class="grid grid-cols-2 gap-2">
                <label>
                    <input type="radio" name="jenis" value="masuk" class="peer sr-only" @checked($jenisAwal === 'masuk')>
                    <span class="block cursor-pointer rounded-xl border border-slate-200 px-3 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-300 peer-checked:bg-brand-50 peer-checked:text-brand-700">
                        Uang masuk
                    </span>
                </label>
                <label>
                    <input type="radio" name="jenis" value="keluar" class="peer sr-only" @checked($jenisAwal === 'keluar')>
                    <span class="block cursor-pointer rounded-xl border border-slate-200 px-3 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-merah-200 peer-checked:bg-merah-50 peer-checked:text-merah-700">
                        Uang keluar
                    </span>
                </label>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="urutan">Urutan</label>
            <input id="urutan" name="urutan" type="number" min="0" max="999" value="{{ old('urutan', $kategori->urutan ?? 0) }}"
                   class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            <p class="mt-1 text-[11px] text-slate-400">Angka lebih kecil tampil lebih dulu.</p>
        </div>

        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
            <input type="hidden" name="aktif" value="0">
            <input id="aktif" name="aktif" type="checkbox" value="1" @checked(old('aktif', $kategori->aktif ?? true))
                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            <span class="text-sm text-slate-700">Aktif (muncul saat mencatat transaksi)</span>
        </label>

        <div class="flex gap-2">
            <a href="{{ route('kas.kategori.index') }}"
               class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Batal
            </a>
            <button class="flex-1 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                {{ $ubah ? 'Simpan perubahan' : 'Tambah kategori' }}
            </button>
        </div>
    </form>
@endsection
