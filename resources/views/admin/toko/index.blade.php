@extends('layouts.admin')

@section('judul', 'Toko')

@section('konten')
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="font-display text-xl text-slate-900">Toko</h1>
        <a href="{{ route('admin.toko.create') }}"
           class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
            Tambah toko
        </a>
    </div>

    <form method="GET" class="mb-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode toko"
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
    </form>

    <div class="card overflow-hidden">
        @if ($toko->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-slate-500">
                Belum ada toko. Tambahkan toko pertama beserta koordinatnya agar geofence bisa dipakai.
            </p>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-medium text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Toko</th>
                        <th class="px-4 py-2.5 font-medium">Karyawan</th>
                        <th class="px-4 py-2.5 font-medium">Geofence</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($toko as $t)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $t->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $t->kode }}</p>
                            </td>
                            <td class="tabular px-4 py-3 text-slate-600">{{ $t->employees_count }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                @if ($t->hasGeofence())
                                    {{ number_format($t->radius_meter, 0, ',', '.') }} m
                                    <span class="block text-[11px] text-slate-400">
                                        {{ number_format($t->latitude, 5, ',', '.') }},
                                        {{ number_format($t->longitude, 5, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-merah-600">Belum ada koordinat</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($t->aktif)
                                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-medium text-brand-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.toko.edit', $t) }}" class="text-sm font-medium text-brand-600 hover:underline">Ubah</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
