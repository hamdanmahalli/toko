@extends('layouts.admin')

@section('judul', 'Toko')

@section('konten')
    <x-page-header class="mb-4" judul="Toko">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 9.5 5.6 5A1 1 0 0 1 6.5 4h11a1 1 0 0 1 .9.6L20 9.5M4 9.5h16M4 9.5a2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0M5 12v7a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-7M10 20v-5h4v5"/>
            </svg>
        </x-slot:ikon>
        <x-slot:aksi>
            <a href="{{ route('admin.toko.create') }}"
               class="flex shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:brightness-105 active:scale-[.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Tambah toko
            </a>
        </x-slot:aksi>
    </x-page-header>

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
