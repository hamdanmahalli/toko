@extends('layouts.presensi', ['judul' => 'Presensi Belum Siap'])

@section('konten')
    <div class="card space-y-4 p-5">
        <div>
            <h1 class="font-display text-lg leading-tight text-slate-900">Perangkat presensi belum bisa dipakai</h1>
            <p class="mt-1 text-[13px] leading-relaxed text-slate-500">
                Perangkat presensi
                <span class="font-medium text-slate-700">{{ $shop->nama }}</span> belum punya
                user dan password, jadi belum ada yang boleh memindai kartu di sini.
            </p>
        </div>

        @include('layouts.pesan')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[13px] leading-relaxed text-amber-800">
            Hubungi pemilik toko atau admin untuk mengisi user dan password presensi di menu
            <span class="font-medium">Toko</span>. Perangkat baru bisa dipakai setelah itu diisi.
        </div>

        <p class="text-center text-[12px] text-slate-400">
            <a href="{{ route('panduan') }}"
               class="underline decoration-slate-300 underline-offset-2 hover:text-slate-600">
                Baca panduan
            </a>
        </p>
    </div>
@endsection