@extends('layouts.app')

@section('judul', 'Profil Saya')

@section('konten')
    @php
        $namaPeran = $akun->getRoleNames()->first();
        $peran = $namaPeran ? \Illuminate\Support\Str::headline($namaPeran) : 'Karyawan';
        $foto = $employee?->fotoUrl();

        // Identitas tambahan hanya ada saat akun tertaut ke data karyawan.
        $barisInfo = $employee ? [
            ['label' => 'NIP / ID Karyawan', 'nilai' => $employee->nip ?: '—',
                'ikon' => 'M10 4 8 20M16 4l-2 16M4 9h16M4 15h16'],
            ['label' => 'Jabatan', 'nilai' => $employee->position?->nama ?? 'Karyawan',
                'ikon' => 'M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M4 7h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Zm0 5h16'],
            ['label' => 'Cabang & Departemen',
                'nilai' => $employee->shop->nama.' · '.($employee->position?->nama ?? 'Karyawan'),
                'ikon' => 'M4 21V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16M15 9h4a1 1 0 0 1 1 1v11M8 8h3M8 12h3M8 16h3'],
            ['label' => 'Tanggal Bergabung',
                'nilai' => $employee->tanggal_masuk?->translatedFormat('d F Y') ?? '—',
                'ikon' => 'M8 3v3m8-3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z'],
        ] : [];
    @endphp

    <div class="space-y-4">
        <x-page-header judul="Profil Saya" sub="Identitas dan keamanan akun.">
            <x-slot:ikon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0v.15H4.5V20.1Z"/>
                </svg>
            </x-slot:ikon>
        </x-page-header>

        {{-- Kartu identitas utama. --}}
        <div class="card p-5">
            <div class="flex items-center gap-4">
                @if ($employee)
                    {{-- Foto bisa diganti langsung dari avatar (khusus akun
                         karyawan). Berkas otomatis terkirim saat dipilih. --}}
                    <form method="POST" action="{{ route('profil.foto') }}" enctype="multipart/form-data"
                          class="relative shrink-0">
                        @csrf
                        <label for="foto-profil" class="group block cursor-pointer" title="Ganti foto profil">
                            <span class="block h-16 w-16 overflow-hidden rounded-full bg-slate-100 shadow-md shadow-brand-600/25 ring-2 ring-transparent transition group-hover:ring-brand-300">
                                @if ($foto)
                                    <img src="{{ $foto }}" alt="{{ $employee->nama }}" class="h-full w-full object-cover">
                                @else
                                    <span class="grid h-full w-full place-items-center bg-gradient-to-br from-brand-500 to-brand-600 text-xl font-bold text-white">
                                        {{ $employee->initials() }}
                                    </span>
                                @endif
                            </span>
                            <span class="absolute -bottom-0.5 -right-0.5 grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-white ring-2 ring-white transition group-hover:bg-brand-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A1.5 1.5 0 0 1 5.5 7h1.7l.8-1.3A1.5 1.5 0 0 1 9.3 5h5.4a1.5 1.5 0 0 1 1.3.7l.8 1.3h1.7A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5v-9Z"/>
                                    <circle cx="12" cy="13" r="3.2"/>
                                </svg>
                            </span>
                        </label>

                        <input id="foto-profil" type="file" name="foto" accept="image/png,image/jpeg,image/webp"
                               class="sr-only" onchange="this.form.submit()">
                    </form>
                @else
                    <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-slate-100 text-xl font-bold text-slate-600">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($akun->name, 0, 2)) }}
                    </span>
                @endif

                <div class="min-w-0">
                    <p class="truncate text-lg font-bold uppercase text-slate-900">
                        {{ $employee->nama ?? $akun->name }}
                    </p>
                    <p class="truncate text-sm text-slate-500">{{ $employee->email ?? $akun->email }}</p>
                    <span class="mt-2 inline-block rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-semibold text-brand-700 ring-1 ring-brand-100">
                        {{ $peran }}
                    </span>

                    @if ($foto)
                        <form method="POST" action="{{ route('profil.foto-hapus') }}" class="mt-1.5">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-[11px] font-medium text-merah-500 transition hover:text-merah-600">
                                Hapus foto
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Kartu informasi detail. --}}
        @if ($barisInfo !== [])
            <div class="card divide-y divide-slate-100 overflow-hidden">
                @foreach ($barisInfo as $b)
                    <div class="flex items-center gap-3 p-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $b['ikon'] }}"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $b['label'] }}</p>
                            <p class="truncate text-sm font-medium text-slate-800">{{ $b['nilai'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Menu: keamanan, panduan & keluar. --}}
        <div class="card divide-y divide-slate-100 overflow-hidden">
            <a href="{{ route('profil.keamanan') }}"
               class="flex items-center justify-between gap-3 p-4 transition hover:bg-slate-50">
                <span class="flex items-center gap-3 text-slate-700">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.5a4.5 4.5 0 0 0-9 0v3M6 10.5h12a1.5 1.5 0 0 1 1.5 1.5v6A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18v-6A1.5 1.5 0 0 1 6 10.5Z"/>
                        </svg>
                    </span>
                    <span class="text-sm font-medium">Keamanan akun</span>
                </span>
                <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
                </svg>
            </a>

            <a href="{{ route('panduan') }}" target="_blank" rel="noopener"
               class="flex items-center justify-between gap-3 p-4 transition hover:bg-slate-50">
                <span class="flex items-center gap-3 text-slate-700">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C12 5 10.5 4 8.5 4H4v14h4.5c2 0 3.5 1 3.5 2.5m0-14C12 5 13.5 4 15.5 4H20v14h-4.5c-2 0-3.5 1-3.5 2.5m0-14v14"/>
                        </svg>
                    </span>
                    <span class="text-sm font-medium">Panduan penggunaan</span>
                </span>
                <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
                </svg>
            </a>

            <form method="POST" action="{{ route('keluar') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center justify-between gap-3 p-4 text-left transition hover:bg-merah-50">
                    <span class="flex items-center gap-3 text-merah-600">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-merah-50 text-merah-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17l5-5-5-5M20 12H9M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>
                            </svg>
                        </span>
                        <span class="text-sm font-medium">Keluar</span>
                    </span>
                </button>
            </form>
        </div>
    </div>
@endsection
