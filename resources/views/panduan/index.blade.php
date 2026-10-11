@extends('layouts.panduan', ['judul' => 'Panduan'])

@section('konten')
    <div class="space-y-8">

        <x-page-header judul="Panduan Penggunaan">
            <x-slot:ikon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 5.5A1.5 1.5 0 0 1 6 4h5v16H6a1.5 1.5 0 0 1-1.5-1.5v-13ZM18 20H13V4h5a1.5 1.5 0 0 1 1.5 1.5v13A1.5 1.5 0 0 1 18 20Z"/>
                </svg>
            </x-slot:ikon>
            <x-slot:sub>
                Semua yang perlu diketahui untuk memakai aplikasi absensi Toko MM: cara absen,
                cara mengatur jadwal, dan arti setiap pesan yang muncul di layar.
                Baca mulai dari
                <a href="#mulai-cepat" class="font-medium text-brand-700 underline underline-offset-2">Mulai Cepat</a>
                kalau ingin langsung memakai aplikasinya.
            </x-slot:sub>
        </x-page-header>

        @foreach ($bagian as $m)
            <section id="{{ $m['id'] }}" class="scroll-mt-20">
                <h2 class="font-display text-lg text-slate-900">{{ $m['judul'] }}</h2>
                <p class="mb-3 mt-0.5 text-xs text-slate-400">{{ $m['ringkas'] }}</p>
                @include($m['view'])
            </section>
        @endforeach

    </div>
@endsection