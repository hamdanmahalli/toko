@extends('layouts.app')

@section('judul', 'Kartu Saya')

@section('konten')
    <div class="space-y-4">
        <x-page-header class="print:hidden" judul="Kartu saya" sub="Tunjukkan kartu ini ke perangkat presensi toko.">
            <x-slot:ikon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16"/>
                </svg>
            </x-slot:ikon>

            {{-- Tombol ikon saja: aksi cetak & unduh sudah lazim, jadi tidak perlu teks. --}}
            <x-slot:aksi>
                <div class="flex shrink-0 gap-2">
                    <button type="button" onclick="window.print()" aria-label="Cetak kartu" title="Cetak kartu"
                            class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H5a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-1"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 14h12v6H6z"/>
                        </svg>
                    </button>

                    <button type="button" data-unduh-kartu="#kartu-pegawai"
                            data-label="{{ \Illuminate\Support\Str::slug($employee->nama) ?: 'karyawan' }}"
                            aria-label="Unduh PNG" title="Unduh PNG"
                            class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/25 transition hover:brightness-105 active:scale-95">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 4-4m-4 4-4-4M5 18h14"/>
                        </svg>
                    </button>
                </div>
            </x-slot:aksi>
        </x-page-header>

        @include('absen.partials.kartu', ['pengguna' => $employee])
    </div>
@endsection
