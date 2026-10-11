<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    {{-- Meta CSRF dipakai untuk menyegarkan token lewat fetch di perangkat presensi. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $judul ?? 'Presensi Karyawan' }} · Toko MM</title>
    @include('layouts.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-brand-50 antialiased">
    <div class="flex min-h-full flex-col">
        <header class="border-b border-brand-100 bg-white/80">
            <div class="mx-auto flex w-full max-w-xl items-center gap-3 px-4 py-3">
                <img src="{{ asset('logo-toko-mm.png') }}" alt="Toko MM" class="h-8 w-auto">

                <div class="min-w-0">
                    <p class="font-display text-[15px] leading-tight text-slate-900">
                        {{ $judul ?? 'Presensi Karyawan' }}
                    </p>
                    @isset($shop)
                        <p class="truncate text-[12px] leading-tight text-slate-500">{{ $shop->nama }}</p>
                    @endisset
                </div>

                @isset($jam)
                    <p class="ml-auto shrink-0 font-display text-lg tabular-nums text-slate-700">{{ $jam }}</p>
                @endisset
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-xl flex-1 flex-col gap-4 px-4 py-5">
            @yield('konten')
        </main>
    </div>

    @include('layouts.dialog')
    @stack('scripts')
</body>
</html>