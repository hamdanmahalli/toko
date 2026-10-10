@php
    $karyawan = auth()->user()->employee ?? null;

    // Setiap menu punya syarat izin sendiri; "profil" selalu tampil karena
    // menu itu tidak butuh data karyawan (atasan pun boleh mengatur akunnya).
    $boleh = fn (string $rute): bool => match ($rute) {
        'beranda' => auth()->user()->can('dashboard.lihat'),
        'absen.qr', 'absen.riwayat' => auth()->user()->can('absen.lihat') && $karyawan !== null,
        'pengajuan.index' => auth()->user()->can('pengajuan.lihat') && $karyawan !== null,
        'profil.index' => true,
        default => false,
    };

    // QR ditaruh persis di tengah supaya tombol absen utama paling mudah
    // dijangkau jempol.
    $menu = array_values(array_filter([
        ['route' => 'beranda', 'label' => 'Beranda', 'tengah' => false, 'icon' => 'M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5'],
        ['route' => 'absen.riwayat', 'label' => 'Riwayat', 'tengah' => false, 'icon' => 'M4 6h16M4 12h16M4 18h10'],
        ['route' => 'absen.qr', 'label' => 'Kartu', 'tengah' => true, 'icon' => 'M6 5h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm4 4.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-2.5 7a3 3 0 0 1 5 0M15 8h2M15 11h2M15 14h2'],
        ['route' => 'pengajuan.index', 'label' => 'Pengajuan', 'tengah' => false, 'icon' => 'M8 3v3m8-3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z'],
        ['route' => 'profil.index', 'label' => 'Profil', 'tengah' => false, 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 0c-3.3 0-6 1.8-6 4v1h12v-1c0-2.2-2.7-4-6-4Z'],
    ], fn ($m) => Route::has($m['route']) && $boleh($m['route'])));
@endphp

<nav class="safe-bottom fixed inset-x-0 bottom-0 z-20 border-t border-brand-100/80 bg-white/90 backdrop-blur-xl">
    <div class="mx-auto grid max-w-md grid-cols-5 items-end gap-1 px-1 pt-2 pb-1.5">
        @foreach ($menu as $m)
            @php $aktif = request()->routeIs($m['route']); @endphp

            @if ($m['tengah'])
                <a href="{{ route($m['route']) }}"
                   @if ($aktif) aria-current="page" @endif
                   class="-mt-12 flex flex-col items-center text-[10px] font-semibold transition
                          {{ $aktif ? 'text-brand-700' : 'text-slate-500 hover:text-slate-700' }}">
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/30 ring-4 ring-white transition active:scale-95">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                        </svg>
                    </span>
                </a>
            @else
                <a href="{{ route($m['route']) }}"
                   @if ($aktif) aria-current="page" @endif
                   class="flex flex-col items-center gap-1 pb-1 pt-2 text-[10px] font-medium tracking-wide transition
                          {{ $aktif ? 'text-brand-700' : 'text-slate-400 hover:text-slate-600' }}">
                    <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" stroke-width="{{ $aktif ? 2 : 1.7 }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                    </svg>
                    {{ $m['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</nav>
