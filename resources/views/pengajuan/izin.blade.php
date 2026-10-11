@extends('layouts.app')

@section('judul', 'Ajukan Izin / Cuti')

@section('konten')
    <x-page-header class="mb-4" judul="Ajukan izin, sakit, cuti, atau dinas"
                   sub="Pengajuan langsung terkirim ke atasan. Cuti dan dinas tidak memotong gaji.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h8l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v5h5M8.5 13h7M8.5 16.5h4"/>
            </svg>
        </x-slot:ikon>
    </x-page-header>

    <form method="POST" action="{{ route('pengajuan.izin.store') }}"
          class="space-y-4 card p-5">
        @csrf

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jenis">Jenis</label>
            <select id="jenis" name="jenis" required
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                @foreach ($jenis as $value => $label)
                    <option value="{{ $value }}" @selected(old('jenis', 'izin') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal_mulai">Mulai</label>
                <input id="tanggal_mulai" name="tanggal_mulai" type="date" required
                       value="{{ old('tanggal_mulai', now()->toDateString()) }}"
                       class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal_selesai">Selesai</label>
                <input id="tanggal_selesai" name="tanggal_selesai" type="date" required
                       value="{{ old('tanggal_selesai', now()->toDateString()) }}"
                       class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" rows="3" placeholder="Contoh: acara keluarga di luar kota"
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
