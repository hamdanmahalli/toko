@extends('layouts.app')

@section('judul', 'Ajukan Lembur')

@section('konten')
    <x-page-header class="mb-4" judul="Ajukan lembur"
                   sub="Isi jam kerja tambahan di luar jadwal. Total dihitung dari tarif jam karyawan.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="8.25"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.75M19.5 3.5v4M17.5 5.5h4"/>
            </svg>
        </x-slot:ikon>
    </x-page-header>

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
