@extends('layouts.app')

@section('tanpa-nav', true)

@php
    $ubah = $transaksi?->exists ?? false;
    $semua = $masuk
        ->map(fn ($k) => ['kode' => $k->kode, 'nama' => $k->nama, 'jenis' => 'masuk'])
        ->concat($keluar->map(fn ($k) => ['kode' => $k->kode, 'nama' => $k->nama, 'jenis' => 'keluar']));
    $jenisAwal = old('jenis', $ubah ? $transaksi->jenis->value : 'masuk');
    $kategoriAwal = old('kategori', $ubah ? $transaksi->kategori : null);
    $nilaiAwal = old('jumlah', $ubah ? (int) $transaksi->jumlah : '');
@endphp

@section('judul', $ubah ? 'Ubah transaksi' : 'Catat transaksi')

@section('konten')
    <div class="flex min-h-0 flex-1 flex-col">
        <div class="mb-3 flex shrink-0 items-center gap-2">
            <a href="{{ route('kas.show', $buku) }}" aria-label="Kembali"
               class="-ml-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/>
                </svg>
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="truncate font-display text-xl text-slate-900">{{ $ubah ? 'Ubah transaksi' : 'Catat transaksi' }}</h1>
                <p class="truncate text-xs text-slate-500">{{ $buku->nama }}</p>
            </div>
            @if ($ubah)
                <form method="POST" action="{{ route('kas.transaksi.destroy', [$buku, $transaksi]) }}"
                      onsubmit="return confirm('Hapus transaksi ini?')">
                    @csrf
                    @method('DELETE')
                    <button aria-label="Hapus transaksi"
                            class="flex h-9 w-9 items-center justify-center rounded-full text-merah-500 transition hover:bg-merah-50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-9 0 1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M10 11v6m4-6v6"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>

        <form method="POST"
              action="{{ $ubah ? route('kas.transaksi.update', [$buku, $transaksi]) : route('kas.transaksi.store', $buku) }}"
              class="flex min-h-0 flex-1 flex-col">
            @csrf
            @if ($ubah)
                @method('PUT')
            @endif

            <x-keypad-nominal :jenis="$jenisAwal" :nilai="$nilaiAwal">
                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pb-3">
                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">Jenis</span>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="jenis" value="masuk" class="peer sr-only" @checked($jenisAwal === 'masuk')>
                                <span class="block rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-300 peer-checked:bg-brand-50 peer-checked:text-brand-700">Uang masuk</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="jenis" value="keluar" class="peer sr-only" @checked($jenisAwal === 'keluar')>
                                <span class="block rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-merah-200 peer-checked:bg-merah-50 peer-checked:text-merah-700">Uang keluar</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="kategori">Kategori</label>
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
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
                        <input id="keterangan" name="keterangan" type="text" maxlength="500" value="{{ old('keterangan', $ubah ? $transaksi->keterangan : '') }}"
                               placeholder="Opsional"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    </div>
                </div>
            </x-keypad-nominal>
        </form>
    </div>

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
