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
    <div class="mx-auto flex min-h-full max-w-5xl flex-col">
        <main class="flex-1 px-4 pb-28 pt-4 sm:pb-8">
            @include('layouts.pesan')
            @yield('konten')
        </main>

        @auth
            @include('layouts.nav')
        @endauth
    </div>
    @stack('kaki')
</body>
</html>