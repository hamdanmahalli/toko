@extends('layouts.app')

@section('judul', 'Kartu Saya')

@section('konten')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3 print:hidden">
            <h1 class="font-display text-xl text-slate-900">Kartu saya</h1>

            <button type="button" onclick="window.print()"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Cetak kartu
            </button>
        </div>

        <p class="text-[13px] leading-relaxed text-slate-500 print:hidden">
            Kartu untuk absen di perangkat presensi toko.
        </p>

        @include('absen.partials.kartu', ['pengguna' => $employee])

        <a href="{{ route('beranda') }}"
           class="block rounded-xl border border-slate-200 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400 print:hidden">
            Kembali ke beranda
        </a>
    </div>
@endsection