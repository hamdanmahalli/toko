@php
    $karyawan = auth()->user()->employee ?? null;
    $menu = array_values(array_filter([
        ['route' => 'beranda', 'label' => 'Beranda', 'icon' => 'M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5'],
        ['route' => 'absen.qr', 'label' => 'QR', 'icon' => 'M4 4h5v5H4V4Zm11 0h5v5h-5V4ZM4 15h5v5H4v-5Zm11 4h2m3 0h1m-6-9h1m3 0h1M4 11h5m5 0h6'],
        ['route' => 'absen.riwayat', 'label' => 'Riwayat', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
        ['route' => 'pengajuan.index', 'label' => 'Pengajuan', 'icon' => 'M8 3v3m8-3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z'],
    ], fn ($m) => Route::has($m['route'])
        && auth()->user()->can(match ($m['route']) {
            'absen.qr', 'absen.riwayat' => 'absen.lihat',
            'pengajuan.index' => 'pengajuan.lihat',
            default => 'dashboard.lihat',
        })
        && ($m['route'] === 'beranda' || $karyawan !== null)));
@endphp

<nav class="safe-bottom fixed inset-x-0 bottom-0 z-20 border-t border-brand-100/80 bg-white/90 backdrop-blur-xl">
    <div class="mx-auto flex max-w-md items-stretch">
        @foreach ($menu as $m)
            @php $aktif = request()->routeIs($m['route']); @endphp
            <a href="{{ route($m['route']) }}"
               @if ($aktif) aria-current="page" @endif
               class="relative flex flex-1 flex-col items-center gap-1 pt-2.5 pb-1.5 text-[10px] font-medium tracking-wide transition
                      {{ $aktif ? 'text-brand-700' : 'text-slate-400 hover:text-slate-600' }}">
                @if ($aktif)
                    <span class="absolute inset-x-5 top-0 h-0.5 rounded-full bg-brand-600"></span>
                @endif
                <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" stroke-width="{{ $aktif ? 2 : 1.7 }}" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                </svg>
                {{ $m['label'] }}
            </a>
        @endforeach
    </div>
</nav>