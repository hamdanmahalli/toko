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
                                <p class="font-medium text-slate-800">{{ $k->nama }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $k->nip ?? '-' }}{{ $k->telepon ? ' · '.$k->telepon : '' }}
                                </p>
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
