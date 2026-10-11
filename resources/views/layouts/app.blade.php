<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Absensi')</title>
    @include('layouts.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="h-full bg-brand-50/60 text-slate-800 antialiased">
    @php($tanpaNav = View::hasSection('tanpa-nav'))
    <div class="mx-auto flex max-w-5xl flex-col {{ $tanpaNav ? 'h-[100dvh] overflow-hidden' : 'min-h-full' }}">
        <main class="flex-1 {{ $tanpaNav ? 'flex min-h-0 flex-col px-4 pt-4' : 'px-4 pb-28 pt-4 sm:pb-8' }}">
            @include('layouts.pesan')
            @yield('konten')
        </main>

        @unless ($tanpaNav)
            @auth
                @include('layouts.nav')
            @endauth
        @endunless
    </div>
    @stack('kaki')
</body>
</html>