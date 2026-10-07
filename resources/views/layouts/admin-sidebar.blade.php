@php
    $menu = [
        ['route' => 'admin.dashboard', 'label' => 'Beranda', 'icon' => 'M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5', 'permission' => 'dashboard.lihat'],
        ['route' => 'admin.karyawan.index', 'label' => 'Karyawan', 'icon' => 'M15 19a6 6 0 0 0-12 0M12 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm9 4a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM3 17a4 4 0 0 1 8 0v2H3v-2Z', 'permission' => 'karyawan.lihat'],
        ['route' => 'admin.pengajuan.index', 'label' => 'Pengajuan', 'icon' => 'M8 3v3m8-3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z', 'permission' => 'pengajuan.lihat'],
        ['route' => 'admin.laporan.index', 'label' => 'Laporan', 'icon' => 'M4 20V10m5 10V4m5 16v-7m5 7V8', 'permission' => 'laporan.lihat'],
        ['route' => 'admin.toko.index', 'label' => 'Toko', 'icon' => 'M3 9h18l-1.5-5h-15L3 9Zm1 0v11a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9M9 21v-6h6v6', 'permission' => 'toko.lihat'],
        ['route' => 'admin.shift.index', 'label' => 'Shift', 'icon' => 'M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'permission' => 'shift.lihat'],
        ['route' => 'admin.window.index', 'label' => 'Window', 'icon' => 'M4 6h16M4 12h10m4 0h2M4 18h7m4 0h5', 'permission' => 'shift.lihat'],
        ['route' => 'admin.jabatan.index', 'label' => 'Jabatan', 'icon' => 'M4 7h16v13H4V7Zm5-4h6v4H9V3Z', 'permission' => 'jabatan.lihat'],
        ['route' => 'admin.pengguna.index', 'label' => 'Pengguna', 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9a8 8 0 0 1 16 0', 'permission' => 'pengguna.lihat'],
        ['route' => 'admin.payroll.index', 'label' => 'Payroll', 'icon' => 'M12 3v18M8 7h6a2 2 0 0 1 0 4H9a2 2 0 0 0 0 4h7', 'permission' => 'payroll.lihat'],
        ['route' => 'admin.pengaturan.index', 'label' => 'Pengaturan', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7.5 7.5 0 0 0-2-1.2l-.4-2.6h-4l-.4 2.6c-.7.3-1.4.7-2 1.2l-2.4-1-2 3.4 2 1.6a7.5 7.5 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1c.6.5 1.3.9 2 1.2l.4 2.6h4l.4-2.6c.7-.3 1.4-.7 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z', 'permission' => 'pengaturan.lihat'],
    ];

    // Route yang belum dibuat disembunyikan, bukan diklik ke halaman kosong.
    $menu = array_values(array_filter(
        $menu,
        fn ($m) => auth()->user()->can($m['permission']) && Route::has($m['route'])
    ));
@endphp

<div class="border-b border-brand-100/80 p-4">
    <img src="{{ asset('logo-toko-mm.png') }}" alt="Toko MM" class="h-7 w-auto">
    <p class="mt-2 truncate text-[11px] font-medium text-slate-400">
        {{ auth()->user()->getRoleNames()->join(' · ') ?: 'Tanpa peran' }}
    </p>
</div>

<nav class="flex-1 space-y-0.5 p-2.5">
    @foreach ($menu as $m)
        @php $aktif = request()->routeIs($m['route'].'*'); @endphp
        <a href="{{ route($m['route']) }}"
           @if ($aktif) aria-current="page" @endif
           class="group flex items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-medium transition
                  {{ $aktif
                      ? 'bg-brand-600 text-white shadow-sm'
                      : 'text-slate-600 hover:bg-brand-50 hover:text-brand-800' }}">
            <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor"
                 stroke-width="{{ $aktif ? 1.9 : 1.7 }}" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
            </svg>
            {{ $m['label'] }}
        </a>
    @endforeach
</nav>