@extends('layouts.admin')

@section('judul', 'Beranda')

@section('konten')
    <div class="space-y-5">
        <section>
            <h1 class="font-display text-xl text-slate-900">{{ now()->translatedFormat('l, d F Y') }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">
                {{ auth()->user()->shops()->exists() ? 'Toko yang Anda awasi' : 'Seluruh toko' }}
            </p>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="card p-4">
                <p class="text-[11px] font-medium text-slate-400">Karyawan</p>
                <p class="tabular mt-1 text-2xl font-semibold text-slate-900">{{ $totalKaryawan }}</p>
            </div>
            <div class="card p-4">
                <p class="text-[11px] font-medium text-slate-400">Hadir</p>
                <p class="tabular mt-1 text-2xl font-semibold text-brand-600">{{ $hadir }}</p>
            </div>
            <div class="card p-4">
                <p class="text-[11px] font-medium text-slate-400">Terlambat</p>
                <p class="tabular mt-1 text-2xl font-semibold text-amber-600">{{ $terlambat }}</p>
            </div>
            <div class="card p-4">
                <p class="text-[11px] font-medium text-slate-400">Belum pulang</p>
                <p class="tabular mt-1 text-2xl font-semibold text-slate-900">{{ $belumPulang }}</p>
            </div>
        </section>

        @if ($perToko->isEmpty())
            <section class="card border border-dashed p-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada toko</p>
                <p class="mx-auto mt-1 max-w-sm text-xs text-slate-500">
                    Geofence butuh koordinat toko.
                </p>
                <a href="{{ route('admin.toko.create') }}"
                   class="mt-4 inline-block rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    Tambah toko pertama
                </a>
            </section>
        @else
            <section class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-4 py-3 border-b border-slate-100 px-4 py-3 font-display text-[15px] text-slate-900">Per toko</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70 text-[11px] font-medium text-slate-400">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Toko</th>
                            <th class="px-4 py-2.5 font-medium">Geofence</th>
                            <th class="px-4 py-2.5 font-medium">Karyawan</th>
                            <th class="px-4 py-2.5 font-medium">Hadir</th>
                            <th class="px-4 py-2.5 font-medium">Terlambat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($perToko as $t)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $t['nama'] }}</td>
                                <td class="px-4 py-3 text-xs">
                                    @if ($t['geofence'])
                                        <span class="text-brand-600">Aktif</span>
                                    @else
                                        <span class="text-amber-600">Belum ada koordinat</span>
                                    @endif
                                </td>
                                <td class="tabular px-4 py-3 text-slate-600">{{ $t['karyawan'] }}</td>
                                <td class="tabular px-4 py-3 text-slate-600">{{ $t['hadir'] }}</td>
                                <td class="tabular px-4 py-3">
                                    @if ($t['terlambat'] > 0)
                                        <span class="font-medium text-amber-600">{{ $t['terlambat'] }}</span>
                                    @else
                                        <span class="text-slate-400">0</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        <section class="card overflow-hidden">
            <h2 class="border-b border-slate-200 px-4 py-3 font-display text-[15px] text-slate-900">
                Per window
            </h2>

            @if ($perWindow->isEmpty())
                <p class="px-4 py-6 text-center text-sm text-slate-500">
                    Belum ada yang absen hari ini.
                </p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($perWindow as $label => $jumlah)
                        <li class="flex items-center justify-between px-4 py-3 text-sm">
                            <span class="font-medium text-slate-800">{{ $label }}</span>
                            <span class="tabular text-slate-600">{{ $jumlah }} orang</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">
                Hanya kasir dan pramuniaga yang dihitung di sini. Manajer dan kepala toko mengikuti template shift.
            </p>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="card p-5">
                <h2 class="font-display text-[15px] text-slate-900">Perlu persetujuan</h2>
                @if ($pendingIzin + $pendingLembur > 0)
                    <p class="text-xs text-slate-500">
                        <span class="tabular font-medium text-amber-600">{{ $pendingIzin + $pendingLembur }}</span>
                        pengajuan menunggu keputusan.
                    </p>
                @endif
                <div class="mt-3 space-y-2">
                    <a href="{{ route('admin.pengajuan.index', ['hanya_pending' => 1]) }}"
                       class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm hover:bg-slate-50 focus:border-brand-400">
                        <span class="text-slate-700">Pengajuan izin / cuti</span>
                        <span class="tabular font-semibold {{ $pendingIzin > 0 ? 'text-brand-600' : 'text-slate-400' }}">
                            {{ $pendingIzin }}
                        </span>
                    </a>
                    <a href="{{ route('admin.pengajuan.index', ['hanya_pending' => 1]) }}"
                       class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm hover:bg-slate-50 focus:border-brand-400">
                        <span class="text-slate-700">Pengajuan lembur</span>
                        <span class="tabular font-semibold {{ $pendingLembur > 0 ? 'text-brand-600' : 'text-slate-400' }}">
                            {{ $pendingLembur }}
                        </span>
                    </a>
                </div>
            </section>

            <section class="card p-5">
                <h2 class="font-display text-[15px] text-slate-900">Belum pulang</h2>
                @if ($daftarBelumPulang->isEmpty())
                    <p class="mt-2 text-sm text-slate-500">Semua yang hadir sudah pulang.</p>
                @else
                    <ul class="mt-2 divide-y divide-slate-100">
                        @foreach ($daftarBelumPulang as $a)
                            <li class="flex items-center justify-between py-2 text-sm">
                                <span class="text-slate-700">{{ $a->employee->nama }}</span>
                                <span class="tabular text-xs text-slate-500">masuk {{ $a->jam_masuk?->format('H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
