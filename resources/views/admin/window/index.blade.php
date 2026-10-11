@use('App\Enums\AturanAbsensi')

@extends('layouts.admin')

@section('judul', 'Window Shift')

@section('konten')
    <x-page-header class="mb-4" judul="Window Shift"
                   sub="Pita jam dalam sehari. Jam datang seorang karyawan yang menentukan dia masuk shift mana.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="8.25"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.75"/>
            </svg>
        </x-slot:ikon>
    </x-page-header>

    <div class="mb-4 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-xs leading-relaxed text-brand-900">
        <p class="font-semibold">Berlaku untuk kasir dan pramuniaga.</p>
        <p class="mt-1">
            Jabatan yang tidak wajib memakai template shift ikut aturan jam di halaman ini: status telat
            dibandingkan dengan <span class="font-medium">batas telat</span> window tersebut, dan jam pulang
            dibandingkan dengan <span class="font-medium">jam selesai</span>. Manajer dan kepala toko tetap
            memakai template shift masing-masing, jadi tidak terpengaruh.
        </p>
    </div>

    @if ($bentrok !== [])
        <div class="mb-4 rounded-2xl border border-merah-300 bg-merah-50 p-4">
            <p class="text-sm font-medium text-merah-900">Ada window yang saling tumpang tindih</p>
            <ul class="mt-1.5 space-y-0.5 text-xs text-merah-800">
                @foreach ($bentrok as $pasangan)
                    <li>• {{ $pasangan }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-merah-800">
                Yang dipakai adalah window yang jam mulainya paling akhir.
            </p>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-2">
            @forelse ($window as $w)
                <div class="card p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-slate-900">{{ $w->nama }}</h2>
                                <span class="tabular rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700">
                                    {{ $w->label() }}
                                </span>
                                @if ($w->kode)
                                    <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[11px] font-medium text-white">{{ $w->kode }}</span>
                                @endif
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $w->aturan() === AturanAbsensi::Ketat ? 'bg-merah-100 text-merah-700' : 'bg-brand-100 text-brand-800' }}">
                                    {{ $w->aturan()->label() }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ $w->shop ? $w->shop->nama : 'Semua toko' }}
                                </span>
                                @if (! $w->aktif)
                                    <span class="rounded-full bg-merah-100 px-2 py-0.5 text-[11px] font-medium text-merah-700">Nonaktif</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                Urutan {{ $w->urutan }}
                                @if ($w->batas_telat)
                                    · telat setelah {{ $w->batas_telat->format('H:i') }}
                                @else
                                    · tanpa toleransi telat
                                @endif
                                @if ($w->durasiMaks())
                                    · maksimal {{ $w->durasiMaksLabel() }}
                                @endif
                            </p>
                        </div>

                        @can('shift.kelola')
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="button" data-sembunyikan="ubah-{{ $w->id }}"
                                        aria-controls="ubah-{{ $w->id }}" aria-expanded="false"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                                    Ubah
                                </button>

                                @if ($w->aktif)
                                    <form method="POST"
                                          action="{{ route('admin.window.destroy', $w) }}"
                                          onsubmit="return confirm('Nonaktifkan window ini? Kasir dan pramuniaga yang jam datangnya jatuh di sini akan tercatat tanpa penilaian sampai window diaktifkan lagi.')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-merah-300 px-2.5 py-1 text-xs font-medium text-merah-600 hover:bg-merah-50">
                                            Nonaktifkan
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.window.aktifkan', $w) }}">
                                        @csrf
                                        <button class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                                            Aktifkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endcan
                    </div>

                    @can('shift.kelola')
                        <form id="ubah-{{ $w->id }}" method="POST" action="{{ route('admin.window.update', $w) }}"
                              class="mt-3 hidden space-y-3 border-t border-slate-100 pt-3">
                            @csrf @method('PUT')

                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-nama-{{ $w->id }}">Nama</label>
                                <input id="ubah-nama-{{ $w->id }}" name="nama" value="{{ $w->nama }}" required maxlength="60"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                @error('nama')
                                    <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-kode-{{ $w->id }}">Kode</label>
                                    <input id="ubah-kode-{{ $w->id }}" name="kode" value="{{ $w->kode }}" maxlength="20"
                                           placeholder="PAGI"
                                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                    @error('kode')
                                        <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-aturan-{{ $w->id }}">Aturan absensi</label>
                                    <select id="ubah-aturan-{{ $w->id }}" name="aturan_absensi"
                                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                        @foreach (AturanAbsensi::cases() as $aturan)
                                            <option value="{{ $aturan->value }}" @selected($w->aturan() === $aturan)>{{ $aturan->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('aturan_absensi')
                                        <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-durasi-{{ $w->id }}">Durasi maksimum (menit)</label>
                                <input id="ubah-durasi-{{ $w->id }}" name="durasi_maks_menit" type="number" min="1" max="1440"
                                       value="{{ $w->durasi_maks_menit }}"
                                       placeholder="480"
                                       class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                <p class="mt-1 text-xs text-slate-500">
                                    Kosongkan bila tidak ada batas. Ini catatan saja, durasi kerja tetap dihitung penuh.
                                </p>
                                @error('durasi_maks_menit')
                                    <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-mulai-{{ $w->id }}">Mulai</label>
                                    <input id="ubah-mulai-{{ $w->id }}" name="mulai" type="time" required value="{{ $w->mulai->format('H:i') }}"
                                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                    @error('mulai')
                                        <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-selesai-{{ $w->id }}">Selesai</label>
                                    <input id="ubah-selesai-{{ $w->id }}" name="selesai" type="time" required value="{{ $w->selesai->format('H:i') }}"
                                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                    @error('selesai')
                                        <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-batas-telat-{{ $w->id }}">Batas telat</label>
                                <input id="ubah-batas-telat-{{ $w->id }}" name="batas_telat" type="time"
                                       value="{{ old('batas_telat', $w->batas_telat?->format('H:i')) }}"
                                       class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                <p class="mt-1 text-xs text-slate-500">Kosongkan berarti harus datang tepat di jam mulai.</p>
                                @error('batas_telat')
                                    <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600" for="ubah-shop-{{ $w->id }}">Toko</label>
                                <select id="ubah-shop-{{ $w->id }}" name="shop_id"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                    @if ($bolehSemuaToko)
                                        <option value="" @selected($w->shop_id === null)>Semua toko</option>
                                    @endif
                                    @foreach ($toko as $t)
                                        <option value="{{ $t->id }}" @selected($w->shop_id === $t->id)>{{ $t->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 items-end gap-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="urutan-{{ $w->id }}">Urutan</label>
                                    <input id="urutan-{{ $w->id }}" name="urutan" type="number" min="0" max="999" value="{{ $w->urutan }}"
                                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                </div>
                                <label class="flex items-center gap-2 pb-2 text-xs font-medium text-slate-600">
                                    <input type="checkbox" name="aktif" value="1" @checked($w->aktif)
                                           class="size-4 rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                                    Aktif
                                </label>
                            </div>

                            <div class="flex gap-2">
                                <button class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700">
                                    Simpan perubahan
                                </button>
                            </div>
                        </form>
                    @endcan
                </div>
            @empty
                <p class="card px-4 py-8 text-center text-sm text-slate-500">
                    Belum ada window shift. Kasir dan pramuniaga tetap bisa absen, tapi jam datangnya tidak
                    dihitung dan tidak ada batas telat yang berlaku.
                </p>
            @endforelse
        </div>

        @can('shift.kelola')
            <div class="h-fit card p-4">
                <h2 class="mb-3 font-display text-[15px] text-slate-900">Tambah window</h2>

                <form method="POST" action="{{ route('admin.window.store') }}" class="space-y-3">
                    @csrf

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600" for="nama">Nama</label>
                        <input id="nama" name="nama" value="{{ old('nama') }}" required maxlength="60"
                               placeholder="Pagi"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        @error('nama')
                            <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600" for="kode">Kode</label>
                            <input id="kode" name="kode" value="{{ old('kode') }}" maxlength="20" placeholder="PAGI"
                                   class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                            <p class="mt-1 text-xs text-slate-500">Kode pendek untuk laporan, misal PAGI.</p>
                            @error('kode')
                                <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600" for="aturan_absensi">Aturan absensi</label>
                            <select id="aturan_absensi" name="aturan_absensi"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                                @foreach (AturanAbsensi::cases() as $aturan)
                                    <option value="{{ $aturan->value }}"
                                        @selected(old('aturan_absensi', AturanAbsensi::Toleran->value) === $aturan->value)>{{ $aturan->label() }}</option>
                                @endforeach
                            </select>
                            @error('aturan_absensi')
                                <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600" for="mulai">Mulai</label>
                            <input id="mulai" name="mulai" type="time" required value="{{ old('mulai', '06:00') }}"
                                   class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                            @error('mulai')
                                <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600" for="selesai">Selesai</label>
                            <input id="selesai" name="selesai" type="time" required value="{{ old('selesai', '14:00') }}"
                                   class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                            @error('selesai')
                                <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600" for="batas_telat">Batas telat</label>
                        <input id="batas_telat" name="batas_telat" type="time" value="{{ old('batas_telat') }}"
                               class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <p class="mt-1 text-xs text-slate-500">
                            Lewat dari jam ini berarti terlambat. Kosongkan bila harus datang tepat di jam mulai.
                        </p>
                        @error('batas_telat')
                            <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600" for="durasi_maks_menit">Durasi maksimum (menit)</label>
                        <input id="durasi_maks_menit" name="durasi_maks_menit" type="number" min="1" max="1440"
                               value="{{ old('durasi_maks_menit') }}" placeholder="480"
                               class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <p class="mt-1 text-xs text-slate-500">
                            Kosongkan bila tidak ada batas. Ini catatan saja, durasi kerja tetap dihitung penuh.
                        </p>
                        @error('durasi_maks_menit')
                            <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600" for="shop_id">Toko</label>
                        <select id="shop_id" name="shop_id"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                            @if ($bolehSemuaToko)
                                <option value="">Semua toko</option>
                            @endif
                            @foreach ($toko as $t)
                                <option value="{{ $t->id }}" @selected((string) old('shop_id') === (string) $t->id)>{{ $t->nama }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $bolehSemuaToko ? 'Pilih toko bila window ini hanya berlaku di sana.' : 'Window ini hanya berlaku untuk toko yang Anda awasi.' }}
                        </p>
                        @error('shop_id')
                            <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600" for="urutan">Urutan</label>
                        <input id="urutan" name="urutan" type="number" min="0" max="999" value="{{ old('urutan', 0) }}"
                               class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        @error('urutan')
                            <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                        <input type="checkbox" name="aktif" value="1" @checked(old('aktif', true))
                               class="size-4 rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                        Aktif dipakai untuk menentukan shift
                    </label>

                    <button class="w-full rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Simpan window
                    </button>
                </form>
            </div>
        @endcan
    </div>

    <script>
        document.querySelectorAll('[data-sembunyikan]').forEach((tombol) => {
            tombol.addEventListener('click', () => {
                const target = document.getElementById(tombol.dataset.sembunyikan);

                if (!target) return;

                const akanTampil = target.classList.toggle('hidden');

                tombol.setAttribute('aria-expanded', String(!akanTampil));
            });
        });
    </script>
@endsection