@extends('layouts.app')

@php
    $ubah = $transaksi?->exists ?? false;
    $semua = $masuk
        ->map(fn ($k) => ['kode' => $k->kode, 'nama' => $k->nama, 'jenis' => 'masuk'])
        ->concat($keluar->map(fn ($k) => ['kode' => $k->kode, 'nama' => $k->nama, 'jenis' => 'keluar']))
        ->values();
    $jenisAwal = old('jenis', $transaksi?->jenis?->value ?? 'masuk');
    $kategoriAwal = old('kategori', $transaksi?->kategori);
    $nilaiAwal = old('jumlah', $ubah ? (int) $transaksi->jumlah : '');
    $gambarAwal = $ubah ? $transaksi->gambarUrl() : null;
@endphp

@section('judul', $ubah ? 'Ubah transaksi' : 'Catat transaksi')

@section('konten')
    <x-page-header class="mb-4" :judul="$ubah ? 'Ubah transaksi' : 'Catat transaksi'" :sub="$buku->nama"
                   :kembali="route('kas.show', $buku)" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $ubah ? route('kas.transaksi.update', [$buku, $transaksi]) : route('kas.transaksi.store', $buku) }}"
          class="space-y-5">
        @csrf
        @if ($ubah)
            @method('PUT')
        @endif

        {{-- Jenis tanpa label: dua tombol besar yang berubah warna saat dipilih. --}}
        <div class="grid grid-cols-2 gap-2">
            <label>
                <input type="radio" name="jenis" value="masuk" class="peer sr-only"
                       @checked($jenisAwal === 'masuk')>
                <span class="block cursor-pointer rounded-xl border border-slate-200 px-3 py-3 text-center text-sm font-semibold text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white">
                    Uang masuk
                </span>
            </label>
            <label>
                <input type="radio" name="jenis" value="keluar" class="peer sr-only"
                       @checked($jenisAwal === 'keluar')>
                <span class="block cursor-pointer rounded-xl border border-slate-200 px-3 py-3 text-center text-sm font-semibold text-slate-600 transition peer-checked:border-merah-600 peer-checked:bg-merah-600 peer-checked:text-white">
                    Uang keluar
                </span>
            </label>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-[84px_1fr] items-center gap-3">
                <label class="text-sm font-medium text-slate-700" for="kategori">Kategori</label>
                <div class="relative">
                    <select id="kategori" name="kategori" required
                            data-atur="{{ route('kas.kategori.index') }}"
                            class="w-full appearance-none rounded-xl border border-slate-200 py-2.5 pl-3 pr-9 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
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
                        <option value="__atur__">Atur kategori</option>
                    </select>
                    <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-[84px_1fr] items-center gap-3">
                <label class="text-sm font-medium text-slate-700" for="tanggal">Tanggal</label>
                <input id="tanggal" name="tanggal" type="date" required
                       value="{{ old('tanggal', $ubah ? $transaksi->tanggal->toDateString() : now()->toDateString()) }}"
                       class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>

            <div class="grid grid-cols-[84px_1fr] items-center gap-3">
                <label class="text-sm font-medium text-slate-700" for="jumlah">Nominal</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
                    <input id="jumlah" name="jumlah" type="text" readonly autocomplete="off" required
                           value="{{ $nilaiAwal }}" placeholder="0"
                           class="tabular w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-10 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <span class="pointer-events-none absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center text-slate-400">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm2.5 4h7M8.5 11h.01M12 11h.01M15.5 11h.01M8.5 14.5h.01M12 14.5h.01M15.5 14.5h.01M8.5 18h.01M12 18h.01M15.5 18h.01"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-[84px_1fr] items-center gap-3">
                <label class="text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
                <input id="keterangan" name="keterangan" type="text" maxlength="500" value="{{ old('keterangan', $ubah ? $transaksi->keterangan : '') }}"
                       placeholder="Opsional"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
        </div>

        {{-- Unggah gambar: klik kartu untuk membuka kamera atau galeri. --}}
        <div>
            <label for="gambar"
                   class="flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white p-4 transition hover:border-brand-400 hover:bg-brand-50/40">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A1.5 1.5 0 0 1 5.5 7h1.7l.8-1.3A1.5 1.5 0 0 1 9.3 5h5.4a1.5 1.5 0 0 1 1.3.7l.8 1.3h1.7A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5v-9Z"/>
                    <circle cx="12" cy="13" r="3.2"/>
                </svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-800">Upload Gambar</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Silahkan upload foto yang berkaitan dengan transaksi ini</span>
                </span>
            </label>
            <input id="gambar" name="gambar" type="file" accept="image/*" class="sr-only">

            <div id="pratinjau-gambar" role="button" tabindex="0" aria-label="Perbesar pratinjau"
                 data-ket="Pratinjau bukti transaksi"
                 class="js-preview {{ $gambarAwal ? '' : 'hidden' }} mt-3 cursor-zoom-in">
                <img id="pratinjau-gambar-img" src="{{ $gambarAwal ?? '' }}" alt="Pratinjau gambar"
                     class="h-40 w-full rounded-2xl object-cover ring-1 ring-slate-100">
            </div>
        </div>

        <x-modal-gambar />

        <div class="flex gap-2 pt-1">
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
              data-konfirmasi="Hapus transaksi ini?"
              data-konfirmasi-judul="Hapus transaksi?"
              data-konfirmasi-tombol="Hapus transaksi"
              data-konfirmasi-bahaya>
            @csrf
            @method('DELETE')
            <button class="w-full rounded-xl border border-merah-100 bg-merah-50 px-4 py-2.5 text-sm font-medium text-merah-700 transition hover:bg-merah-100">
                Hapus transaksi
            </button>
        </form>
    @endif

    <x-keypad-nominal :jenis="$jenisAwal" :nilai="$nilaiAwal" />

    <script type="application/json" id="data-kategori">@json($semua)</script>
    @push('kaki')
    <script>
    (() => {
        const wadah = document.getElementById('data-kategori');
        const select = document.getElementById('kategori');
        if (wadah && select) {
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

                const atur = document.createElement('option');
                atur.value = '__atur__';
                atur.textContent = 'Atur kategori';
                select.appendChild(atur);
            };

            bangun(terpilih(), select.value);
            radio.forEach((r) => r.addEventListener('change', () => bangun(terpilih(), null)));

            select.addEventListener('change', () => {
                if (select.value === '__atur__' && select.dataset.atur) {
                    window.location.href = select.dataset.atur;
                }
            });
        }

        const berkas = document.getElementById('gambar');
        const wadahGambar = document.getElementById('pratinjau-gambar');
        const pratinjau = document.getElementById('pratinjau-gambar-img');

        berkas?.addEventListener('change', () => {
            if (!wadahGambar || !pratinjau || !berkas.files[0]) return;

            wadahGambar.classList.remove('hidden');
            pratinjau.src = URL.createObjectURL(berkas.files[0]);
        });
    })();
    </script>
    @endpush
@endsection
