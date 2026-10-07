<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $judul ?? 'Panduan' }} · Toko MM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-brand-50/60 text-slate-800 antialiased">
<div class="flex min-h-full flex-col">

    <header class="sticky top-0 z-30 border-b border-brand-100/80 bg-white/85 backdrop-blur-xl">
        <div class="mx-auto flex w-full max-w-6xl items-center gap-3 px-4 py-2.5">
            <img src="{{ asset('logo-toko-mm.png') }}" alt="Toko MM" class="h-7 w-auto">

            <div class="min-w-0">
                <p class="font-display text-[15px] leading-tight text-slate-900">Panduan</p>
                <p class="truncate text-[11px] leading-tight text-slate-400">
                    Cara memakai aplikasi absensi Toko MM
                </p>
            </div>

            <a href="{{ auth()->check() ? route('beranda') : route('masuk') }}"
               class="ml-auto flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5
                      text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17l5-5-5-5M20 12H9M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>
                </svg>
                <span class="hidden sm:inline">{{ auth()->check() ? 'Beranda' : 'Masuk' }}</span>
            </a>
        </div>
    </header>

    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-5 lg:flex-row">

        {{-- Daftar isi dilipat di layar HP, nempel di kiri di layar lebar.
             Partial-nya dipakai dua kali supaya isi tidak terduplikasi. --}}
        <details class="card p-0 lg:hidden">
            <summary class="cursor-pointer px-4 py-3 font-display text-[15px] text-slate-900">
                Daftar isi
            </summary>
            <div class="border-t border-slate-100">
                @include('panduan.partials.daftar-isi')
            </div>
        </details>

        <aside class="hidden lg:block lg:w-56 lg:shrink-0">
            <div class="sticky top-[4.25rem]">
                @include('panduan.partials.daftar-isi')
            </div>
        </aside>

        <main class="min-w-0 flex-1 pb-16">
            @yield('konten')
        </main>
    </div>
</div>

@include('panduan.partials.pencarian')
</body>
</html>