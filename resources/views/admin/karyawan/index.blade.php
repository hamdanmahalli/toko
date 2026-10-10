@extends('layouts.admin')

@section('judul', 'Karyawan')

@section('konten')
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="font-display text-xl text-slate-900">Karyawan</h1>
        <a href="{{ route('admin.karyawan.create') }}"
           class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
            Tambah karyawan
        </a>
    </div>

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

    <div class="card overflow-x-auto">
        @if ($karyawan->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-slate-500">
                Belum ada karyawan yang cocok.
            </p>
        @else
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
                                    <span class="text-amber-600">Belum ada akun</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($k->aktif)
                                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-medium text-brand-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.karyawan.qr', $k) }}" class="text-sm font-medium text-slate-500 hover:underline">QR</a>
                                <span class="px-1 text-slate-300">|</span>
                                <a href="{{ route('admin.karyawan.edit', $k) }}" class="text-sm font-medium text-brand-600 hover:underline">Ubah</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($karyawan->hasPages())
        <div class="mt-3">{{ $karyawan->links() }}</div>
    @endif
@endsection
