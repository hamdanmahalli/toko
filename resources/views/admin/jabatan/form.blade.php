@extends('layouts.admin')

@section('judul', $jabatan->exists ? 'Ubah Jabatan' : 'Tambah Jabatan')

@section('konten')
    <h1 class="mb-4 font-display text-xl text-slate-900">
        {{ $jabatan->exists ? 'Ubah jabatan' : 'Tambah jabatan' }}
    </h1>

    <form method="POST"
          action="{{ $jabatan->exists ? route('admin.jabatan.update', $jabatan) : route('admin.jabatan.store') }}"
          class="space-y-4">
        @csrf
        @if ($jabatan->exists)
            @method('PUT')
        @endif

        <div class="space-y-4 card p-5">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="nama">Nama jabatan</label>
                <input id="nama" name="nama" required value="{{ old('nama', $jabatan->nama) }}"
                       placeholder="Kasir"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="kode">Kode</label>
                <input id="kode" name="kode" value="{{ old('kode', $jabatan->kode) }}" placeholder="KASIR"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm uppercase outline-none focus:border-brand-500">
                <p class="mt-1 text-xs text-slate-500">Kosongkan bila tidak perlu. Kode dibuat otomatis dari nama jabatan.</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                          class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">{{ old('deskripsi', $jabatan->deskripsi) }}</textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="pakai_template">Aturan jam absen</label>
                <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="checkbox" name="pakai_template" value="1"
                           @checked(old('pakai_template', $jabatan->exists ? $jabatan->pakai_template : false))
                           class="mt-0.5 rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                    <span>
                        <span class="block font-medium">Wajib memakai template shift</span>
                        <span class="mt-0.5 block text-xs text-slate-500">
                            Centang untuk jabatan seperti manajer atau kepala toko yang jam kerjanya ditentukan per orang.
                            Bila tidak dicentang, jam datang karyawan sendiri yang menentukan dia masuk shift mana,
                            memakai Window Shift (misal kasir dan pramuniaga).
                        </span>
                    </span>
                </label>
                <p class="mt-1 text-xs text-slate-500">
                    Mengubah ini tidak menghapus template yang sudah terlanjur ditugaskan. Karyawan yang sudah punya
                    penugasan template tetap memakainya sampai penugasannya diubah atau dihapus.
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="aktif" value="1"
                       @checked(old('aktif', $jabatan->exists ? $jabatan->aktif : true))
                       class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                Jabatan aktif (bisa dipilih saat menambah karyawan)
            </label>
        </div>

        <div class="flex gap-2">
            <button class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[.99]">
                Simpan
            </button>
            <a href="{{ route('admin.jabatan.index') }}"
               class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                Batal
            </a>
        </div>
    </form>
@endsection
