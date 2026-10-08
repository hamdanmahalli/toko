@php
    $namaApp = \App\Models\Setting::ambil('umum.nama_app', 'Toko MM');
@endphp

<header class="sticky top-0 z-20 border-b border-brand-100/80 bg-white/85 backdrop-blur-xl">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-2.5">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
            <img src="{{ asset('logo-toko-mm.png') }}" alt="{{ $namaApp }}" class="h-7 w-auto">
        </a>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('panduan') }}" target="_blank" rel="noopener"
               class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                Panduan
            </a>
            <a href="{{ route('profil.index') }}"
               class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                Profil
            </a>
            <span class="hidden max-w-[10rem] truncate text-xs font-medium text-slate-500 sm:inline">
                {{ auth()->user()->name }}
            </span>
            <form method="POST" action="{{ route('keluar') }}">
                @csrf
                <button aria-label="Keluar"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-merah-50 hover:text-merah-600">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 17l5-5-5-5M20 12H9M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</header>