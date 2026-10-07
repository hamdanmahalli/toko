@extends('layouts.app')

@section('judul', 'Ajukan Lembur')

@section('konten')
    <h1 class="mb-1 font-display text-xl text-slate-900">Ajukan lembur</h1>
    <p class="mb-4 text-xs text-slate-500">
        Isi jam kerja tambahan di luar jadwal. Total dihitung dari tarif jam karyawan.
    </p>

    <form method="POST" action="{{ route('pengajuan.lembur.store') }}"
          class="space-y-4 card p-5">
        @csrf

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal">Tanggal</label>
            <input id="tanggal" name="tanggal" type="date" required
                   value="{{ old('tanggal', now()->toDateString()) }}"
                   class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jam_mulai">Mulai</label>
                <input id="jam_mulai" name="jam_mulai" type="time" required value="{{ old('jam_mulai', '18:00') }}"
                       class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jam_selesai">Selesai</label>
                <input id="jam_selesai" name="jam_selesai" type="time" required value="{{ old('jam_selesai', '20:00') }}"
                       class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>
        </div>

        <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
            @if ($tarifDefault)
                Tarif lembur karyawan: <span class="tabular">Rp {{ number_format((float) $tarifDefault, 0, ',', '.') }}</span> per jam.
            @else
                Tarif lembur belum diatur pada data karyawan, jadi total akan dihitung manual oleh atasan.
            @endif
        </p>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" rows="3" placeholder="Contoh: rekap stok akhir bulan"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">{{ old('keterangan') }}</textarea>
        </div>

        <button class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[.99]">
            Kirim pengajuan
        </button>
        <a href="{{ route('pengajuan.index') }}"
           class="block w-full rounded-lg border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
            Batal
        </a>
    </form>
@endsection
