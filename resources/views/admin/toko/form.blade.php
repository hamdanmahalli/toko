@extends('layouts.admin')

@section('judul', $toko->exists ? 'Ubah Toko' : 'Tambah Toko')

@section('konten')
    <x-page-header class="mb-4" :kembali="route('admin.toko.index')"
                   judul="{{ $toko->exists ? 'Ubah toko' : 'Tambah toko' }}" />

    <form method="POST"
          action="{{ $toko->exists ? route('admin.toko.update', $toko) : route('admin.toko.store') }}"
          class="space-y-4">
        @csrf
        @if ($toko->exists)
            @method('PUT')
        @endif

        <div class="space-y-4 card p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="nama">Nama toko</label>
                    <input id="nama" name="nama" required value="{{ old('nama', $toko->nama) }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="kode">Kode</label>
                    <input id="kode" name="kode" required value="{{ old('kode', $toko->kode) }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm uppercase outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="alamat">Alamat</label>
                <input id="alamat" name="alamat" value="{{ old('alamat', $toko->alamat) }}"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90"
                           value="{{ old('latitude', $toko->latitude) }}" placeholder="-6.20000"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180"
                           value="{{ old('longitude', $toko->longitude) }}" placeholder="106.80000"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="radius_meter">Radius (meter)</label>
                    <input id="radius_meter" name="radius_meter" type="number" min="20" max="5000"
                           value="{{ old('radius_meter', $toko->radius_meter ?? 150) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
            </div>

            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                Ambil koordinat dari Google Maps: klik lokasi toko, salin angka-pair setelah
                <code class="rounded bg-white px-1">@</code>. Contoh <code class="rounded bg-white px-1">-6.1753924, 106.8271528</code>.
                Tanpa koordinat, geofence tidak bisa dipakai dan absen hanya bergantung pada GPS.
            </p>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="buka">Buka</label>
                    <input id="buka" name="buka" type="time" value="{{ old('buka', $toko->buka?->format('H:i')) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tutup">Tutup</label>
                    <input id="tutup" name="tutup" type="time" value="{{ old('tutup', $toko->tutup?->format('H:i')) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="zona_waktu">Zona waktu</label>
                    <input id="zona_waktu" name="zona_waktu" value="{{ old('zona_waktu', $toko->zona_waktu ?? 'Asia/Jakarta') }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="aktif" value="1"
                       @checked(old('aktif', $toko->exists ? $toko->aktif : true))
                       class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                Toko aktif
            </label>
        </div>

        <div class="space-y-4 card p-5">
            <div>
                <h2 class="font-display text-[15px] text-slate-900">Presensi Karyawan</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Satu user dan satu password dipakai bersama seluruh karyawan di toko ini, bukan
                    akun per orang. Perangkat menolak dibuka sampai keduanya diisi, jadi halaman ini
                    tidak akan pernah bisa diakses orang luar tanpa kredensial.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="presensi_user">User presensi</label>
                    <input id="presensi_user" name="presensi_user" type="text" autocomplete="off"
                           autocapitalize="off" autocorrect="off" spellcheck="false"
                           value="{{ old('presensi_user', $toko->presensi_user) }}"
                           placeholder="mis. presensi-101"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <p class="mt-1.5 text-xs text-slate-500">Harus unik antar toko, maksimal 60 karakter.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="presensi_password">Password presensi</label>
                    <input id="presensi_password" name="presensi_password" type="password" autocomplete="new-password"
                           placeholder="{{ $toko->presensiSiap() ? '•••• (sudah diisi)' : 'Minimal 8 karakter' }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
                        @if ($toko->presensiSiap())
                            Kosongkan untuk memakai password yang sekarang. Isi untuk menggantinya.
                        @else
                            Kosong berarti perangkat presensi belum siap dan tidak bisa dibuka.
                        @endif
                    </p>
                </div>
            </div>

            @unless ($toko->presensiSiap())
                <p class="rounded-lg bg-merah-50 px-3 py-2 text-xs leading-relaxed text-merah-800">
                    Perangkat presensi <span class="font-medium">{{ $toko->exists ? 'toko ini masih' : 'belum' }} belum punya
                    user dan password, jadi siapa pun yang membuka tautannya akan melihat halaman
                    "belum siap" dan tidak bisa memindai kartu.
                </p>
            @endunless

            @if ($toko->exists)
                <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                    <p class="text-xs text-slate-600">
                        Halaman presensi toko ini:
                        <a href="{{ route('presensi.form', $toko->kode) }}" target="_blank" rel="noopener"
                           class="font-medium text-brand-700 underline underline-offset-2">
                            {{ route('presensi.form', $toko->kode) }}
                        </a>
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Cetak tautan ini jadi QR Code dan tempel di dekat perangkat presensi, supaya mudah
                        dibuka lagi kalau alamatnya lupa.
                    </p>
                </div>

                @unless ($toko->hasGeofence())
                    <p class="rounded-lg bg-merah-50 px-3 py-2 text-xs leading-relaxed text-merah-800">
                        Perangkat presensi belum bisa dipakai karena koordinat toko masih kosong. Isi latitude dan longitude dulu.
                    </p>
                @endunless
            @endif
        </div>

        <div class="flex gap-2">
            <button class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[.99]">
                Simpan
            </button>
            <a href="{{ route('admin.toko.index') }}"
               class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                Batal
            </a>
        </div>
    </form>
@endsection
