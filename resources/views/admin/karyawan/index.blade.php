@extends('layouts.admin')

@section('judul', 'Karyawan')

@section('konten')
    <x-page-header class="mb-4" judul="Karyawan">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0v.15H4.5V20.1Z"/>
            </svg>
        </x-slot:ikon>
        <x-slot:aksi>
            <a href="{{ route('admin.karyawan.create') }}"
               class="flex shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:brightness-105 active:scale-[.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Tambah karyawan
            </a>
        </x-slot:aksi>
    </x-page-header>

    <form method="GET" class="mb-3 grid gap-2 sm:grid-cols-[1fr_auto_auto_auto]">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, NIP, atau telepon"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <select name="shop" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua toko</option>
            @foreach ($toko as $t)
                <option value="{{ $t->id }}" @selected(request('shop') == $t->id)>{{ $t->nama }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
        <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
            Filter
        </button>
    </form>

    @if ($karyawan->isEmpty())
        <p class="card px-4 py-8 text-center text-sm text-slate-500">
            Belum ada karyawan yang cocok.
        </p>
    @else
        {{-- Kartu pengenal untuk layar kecil --}}
        <div class="grid gap-3 md:hidden">
            @foreach ($karyawan as $k)
                @php $shift = $k->shiftBerlaku()?->template; @endphp
                <div class="card overflow-hidden">
                    <div @class([
                        'border-t-4 p-4',
                        'border-brand-600' => $k->aktif,
                        'border-slate-300' => ! $k->aktif,
                    ])>
                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">
                            {{ $k->shop->nama }}
                        </p>

                        <div class="mt-3 flex items-center gap-3">
                            <span class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                @if ($k->fotoUrl())
                                    <img src="{{ $k->fotoUrl() }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-300">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/>
                                        </svg>
                                    </span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-display text-base text-slate-900">{{ $k->nama }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $k->position->nama ?? 'Tanpa jabatan' }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                        'bg-brand-50 text-brand-700' => $k->aktif,
                                        'bg-slate-100 text-slate-500' => ! $k->aktif,
                                    ])>
                                        {{ $k->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    @php $shift = $k->shiftBerlaku()?->template; @endphp
                                    @if ($shift)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                            {{ $shift->nama }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2 border-t border-dashed border-slate-200 pt-2.5 text-[11px] text-slate-400">
                            <span class="truncate">NIP {{ $k->nip ?? '-' }}{{ $k->telepon ? ' · '.$k->telepon : '' }}</span>
                            <span class="shrink-0">{{ $k->user ? 'Punya akun' : 'Belum ada akun' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-100 p-2.5">
                        <a href="{{ route('admin.karyawan.qr', $k) }}"
                           class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-medium text-slate-600 hover:bg-slate-50">
                            Kartu
                        </a>
                        <a href="{{ route('admin.karyawan.edit', $k) }}"
                           class="flex-1 rounded-lg bg-brand-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-brand-700">
                            Ubah
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tabel untuk layar lebar --}}
        <div class="card hidden overflow-x-auto md:block">
            <table class="w-full min-w-[46rem] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-medium text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Karyawan</th>
                        <th class="px-4 py-2.5 font-medium">Toko</th>
                        <th class="px-4 py-2.5 font-medium">Jabatan</th>
                        <th class="px-4 py-2.5 font-medium">Shift</th>
                        <th class="px-4 py-2.5 font-medium">Akun</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($karyawan as $k)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                                        @if ($k->fotoUrl())
                                            <img src="{{ $k->fotoUrl() }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-slate-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-800">{{ $k->nama }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $k->nip ?? '-' }}{{ $k->telepon ? ' · '.$k->telepon : '' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $k->shop->nama }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $k->position->nama ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @php $shift = $k->shiftBerlaku()?->template; @endphp
                                {{ $shift->nama ?? '-' }}
                                @if ($shift && $k->shiftBerlaku()?->mulai_berlaku)
                                    <span class="block text-[11px] text-slate-400">
                                        sejak {{ $k->shiftBerlaku()->mulai_berlaku->format('d/m/Y') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($k->user)
                                    <span class="text-slate-600">{{ $k->user->email }}</span>
                                @else
                                    <span class="text-merah-600">Belum ada akun</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($k->aktif)
                                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-medium text-brand-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.karyawan.qr', $k) }}" class="text-xs font-medium text-slate-500 hover:text-slate-700">Kartu</a>
                                    <a href="{{ route('admin.karyawan.edit', $k) }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">Ubah</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($karyawan->hasPages())
        <div class="mt-3">{{ $karyawan->links() }}</div>
    @endif
@endsection
