<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Admin') · Toko MM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-brand-50/60 text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 flex-col border-r border-brand-100/80 bg-white lg:flex">
            @include('layouts.admin-sidebar')
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 border-b border-brand-100/80 bg-white/85 backdrop-blur-xl">
                <div class="flex items-center justify-between gap-3 px-4 py-2.5 lg:px-6">
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('menu-admin').classList.remove('hidden')"
                                aria-label="Buka menu"
                                class="-ml-1 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 lg:hidden">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                            </svg>
                        </button>
                        <h1 class="font-display text-[15px] text-slate-900">@yield('judul', 'Admin')</h1>
                    </div>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('panduan') }}" target="_blank" rel="noopener"
                           class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                            Panduan
                        </a>
                        @if (auth()->user()->employee !== null)
                            <a href="{{ route('beranda') }}"
                               class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                                Mode karyawan
                            </a>
                        @endif
                        <span class="hidden max-w-[9rem] truncate text-xs font-medium text-slate-500 sm:inline">
                            {{ auth()->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('keluar') }}">
                            @csrf
                            <button aria-label="Keluar"
                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 17l5-5-5-5M20 12H9M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-5 pb-20 lg:px-6 lg:pb-8">
                @include('layouts.pesan')
                @yield('konten')
            </main>
        </div>
    </div>

    {{-- Menu geser untuk layar HP; sidebar tetap hanya tampil di layar lebar --}}
    <div id="menu-admin" class="fixed inset-0 z-40 hidden">
        <div class="absolute inset-0 bg-brand-950/40 backdrop-blur-sm" onclick="this.parentNode.classList.add('hidden')"></div>
        <div class="absolute inset-y-0 left-0 flex w-64 flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-end border-b border-brand-100/80 px-3 py-2">
                <button type="button" onclick="document.getElementById('menu-admin').classList.add('hidden')"
                        aria-label="Tutup menu"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>
            @include('layouts.admin-sidebar')
        </div>
    </div>
</body>
</html>