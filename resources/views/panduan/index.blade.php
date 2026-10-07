@extends('layouts.panduan', ['judul' => 'Panduan'])

@section('konten')
    <div class="space-y-8">

        <section class="card p-5">
            <h1 class="font-display text-xl text-slate-900">Panduan Penggunaan</h1>
            <p class="mt-1 text-[13px] leading-relaxed text-slate-500">
                Semua yang perlu diketahui untuk memakai aplikasi absensi Toko MM: cara absen,
                cara mengatur jadwal, dan arti setiap pesan yang muncul di layar.
                Baca mulai dari
                <a href="#mulai-cepat" class="font-medium text-brand-700 underline underline-offset-2">Mulai Cepat</a>
                kalau ingin langsung memakai aplikasinya.
            </p>
        </section>

        @foreach ($bagian as $m)
            <section id="{{ $m['id'] }}" class="scroll-mt-20">
                <h2 class="font-display text-lg text-slate-900">{{ $m['judul'] }}</h2>
                <p class="mb-3 mt-0.5 text-xs text-slate-400">{{ $m['ringkas'] }}</p>
                @include($m['view'])
            </section>
        @endforeach

    </div>
@endsection