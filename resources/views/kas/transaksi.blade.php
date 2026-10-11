@extends('layouts.app')

@php
    $ubah = $transaksi?->exists ?? false;
    $semua = $masuk
        ->map(fn ($k) => ['kode' => $k->kode, 'nama' => $k->nama, 'jenis' => 'masuk'])
        ->concat($keluar->map(fn ($k) => ['kode' => $k->kode, 'nama' => $k->nama, 'jenis' => 'keluar']))
        ->values();
    $jenisAwal = old('jenis', $transaksi?->jenis?->value ?? 'masuk');
    $kategoriAwal = old('kategori', $transaksi?->kategori);
@endphp

@section('judul', $ubah ? 'Ubah transaksi' : 'Catat transaksi')

@section('konten')
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('kas.show', $buku) }}" aria-label="Kembali"
           class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/>
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="truncate font-display text-xl text-slate-900">{{ $ubah ? 'Ubah transaksi' : 'Catat transaksi' }}</h1>
            <p class="truncate text-xs text-slate-500">{{ $buku->nama }}</p>
        </div>
    </div>

    <form method="POST" action="{{ $ubah ? route('kas.transaksi.update', [$buku, $transaksi]) : route('kas.transaksi.store', $buku) }}" class="space-y-4">
        @csrf
        @if ($ubah)
            @method('PUT')
        @endif

        <div>
            <span class="mb-1.5 block text-sm font-medium text-slate-700">Jenis</span>
            <div class="grid grid-cols-2 gap-2">
                <label>
                    <input type="radio" name="jenis" value="masuk" class="peer sr-only"
                           @checked($jenisAwal === 'masuk')>
                    <span class="block cursor-pointer rounded-xl border border-slate-200 px-3 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-300 peer-checked:bg-brand-50 peer-checked:text-brand-700">
                        Uang masuk
                    </span>
                </label>
                <label>
                    <input type="radio" name="jenis" value="keluar" class="peer sr-only"
                           @checked($jenisAwal === 'keluar')>
                    <span class="block cursor-pointer rounded-xl border border-slate-200 px-3 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-merah-200 peer-checked:bg-merah-50 peer-checked:text-merah-700">
                        Uang keluar
                    </span>
                </label>
            </div>
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between gap-3">
                <label class="block text-sm font-medium text-slate-700" for="kategori">Kategori</label>
                <a href="{{ route('kas.kategori.index') }}" class="text-xs font-medium text-brand-600 hover:underline">
                    Atur kategori
                </a>
            </div>
            <select id="kategori" name="kategori" required
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <optgroup label="Pemasukan">
                    @foreach ($masuk as $k)
                        <option value="{{ $k->kode }}" data-jenis="masuk" @selected($kategoriAwal === $k->kode)>{{ $k->nama }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Pengeluaran">
                    @foreach ($keluar as $k)
                        <option value="{{ $k->kode }}" data-jenis="keluar" @selected($kategoriAwal === $k->kode)>{{ $k->nama }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal">Tanggal</label>
            <input id="tanggal" name="tanggal" type="date" required
                   value="{{ old('tanggal', $ubah ? $transaksi->tanggal->toDateString() : now()->toDateString()) }}"
                   class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jumlah">Nominal</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
                <input id="jumlah" name="jumlah" type="text" inputmode="numeric" autocomplete="off" required
                       data-format-ribuan value="{{ old('jumlah', $ubah ? (int) $transaksi->jumlah : '') }}" placeholder="0"
                       class="tabular w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-12 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <button type="button" data-kalkulator-buka="jumlah" aria-label="Buka kalkulator"
                        class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm2.5 4h7M8.5 11h.01M12 11h.01M15.5 11h.01M8.5 14.5h.01M12 14.5h.01M15.5 14.5h.01M8.5 18h.01M12 18h.01M15.5 18h.01"/>
                    </svg>
                </button>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
            <input id="keterangan" name="keterangan" type="text" maxlength="500" value="{{ old('keterangan', $ubah ? $transaksi->keterangan : '') }}"
                   placeholder="Opsional"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div class="flex gap-2">
            <a href="{{ route('kas.show', $buku) }}"
               class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Batal
            </a>
            <button class="flex-1 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                {{ $ubah ? 'Simpan perubahan' : 'Simpan' }}
            </button>
        </div>
    </form>

    @if ($ubah)
        <form method="POST" action="{{ route('kas.transaksi.destroy', [$buku, $transaksi]) }}" class="mt-4"
              onsubmit="return confirm('Hapus transaksi ini?')">
            @csrf
            @method('DELETE')
            <button class="w-full rounded-xl border border-merah-100 bg-merah-50 px-4 py-2.5 text-sm font-medium text-merah-700 transition hover:bg-merah-100">
                Hapus transaksi
            </button>
        </form>
    @endif

    @include('components.kalkulator')
    @include('components.format-ribuan')

    <script type="application/json" id="data-kategori">@json($semua)</script>
    @push('kaki')
    <script>
    (() => {
        const wadah = document.getElementById('data-kategori');
        const select = document.getElementById('kategori');
        if (!wadah || !select) return;

        const data = JSON.parse(wadah.textContent);
        const radio = document.querySelectorAll('input[name="jenis"]');
        const terpilih = () => document.querySelector('input[name="jenis"]:checked')?.value || 'masuk';

        const bangun = (jenis, pilih) => {
            select.innerHTML = '';
            data.filter((k) => k.jenis === jenis).forEach((k) => {
                const opsi = document.createElement('option');
                opsi.value = k.kode;
                opsi.textContent = k.nama;
                if (k.kode === pilih) opsi.selected = true;
                select.appendChild(opsi);
            });
        };

        bangun(terpilih(), select.value);
        radio.forEach((r) => r.addEventListener('change', () => bangun(terpilih(), null)));
    })();
    </script>
    @endpush
@endsection
