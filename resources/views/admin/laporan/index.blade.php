@extends('layouts.admin')

@section('judul', 'Laporan')

@section('konten')
    <div class="mb-4">
        <h1 class="font-display text-xl text-slate-900">Laporan durasi kerja</h1>
        <p class="text-sm text-slate-500">
            Dihitung dari absensi yang tercatat, jadi angka bulan lalu tidak ikut berubah
            saat aturan shift diubah.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 ring-1 ring-rose-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" class="mb-3 grid gap-2 sm:grid-cols-[1fr_9rem_9rem_auto_auto]">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau NIP"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <input type="date" name="dari" value="{{ $dari }}" aria-label="Tanggal mulai"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <input type="date" name="sampai" value="{{ $sampai }}" aria-label="Tanggal akhir"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <select name="shop" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua toko</option>
            @foreach ($toko as $t)
                <option value="{{ $t->id }}" @selected(request('shop') == $t->id)>{{ $t->nama }}</option>
            @endforeach
        </select>
        <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
            Filter
        </button>
    </form>

    <div class="mb-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'Total jam kerja' => \App\Support\Durasi::label($ringkasan['menit']) ?? '0j 0m',
            'Karyawan hadir' => $ringkasan['karyawan'].' orang',
            'Total hari kerja' => $ringkasan['hari'].' hari',
            'Total sesi' => $ringkasan['sesi'].' sesi',
        ] as $label => $nilai)
            <div class="card px-4 py-3">
                <p class="tabular text-xl font-semibold text-slate-900">{{ $nilai }}</p>
                <p class="text-[11px] leading-tight text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        @if ($baris->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-slate-500">
                Belum ada absensi pada rentang {{ $dari }} sampai {{ $sampai }}.
            </p>
        @else
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-medium text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Karyawan</th>
                        <th class="px-4 py-2.5 font-medium">Toko</th>
                        <th class="px-4 py-2.5 text-right font-medium">Hari</th>
                        <th class="px-4 py-2.5 text-right font-medium">Sesi</th>
                        <th class="px-4 py-2.5 text-right font-medium">Total durasi</th>
                        <th class="px-4 py-2.5 text-right font-medium">Rata-rata/hari</th>
                        <th class="px-4 py-2.5 text-right font-medium">Terlambat</th>
                        <th class="px-4 py-2.5 text-right font-medium">Belum pulang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($baris as $b)
                        @php $rata = $b['hari'] > 0 ? intdiv($b['menit'], $b['hari']) : 0; @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $b['employee']->nama }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $b['employee']->nip ?? '-' }}{{ $b['employee']->position ? ' · '.$b['employee']->position->nama : '' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $b['employee']->shop->nama }}</td>
                            <td class="tabular px-4 py-3 text-right text-slate-600">{{ $b['hari'] }}</td>
                            <td class="tabular px-4 py-3 text-right text-slate-600">{{ $b['sesi'] }}</td>
                            <td class="tabular px-4 py-3 text-right font-medium text-slate-800">
                                {{ \App\Support\Durasi::label($b['menit']) ?? '0j 0m' }}
                            </td>
                            <td class="tabular px-4 py-3 text-right text-slate-600">
                                {{ \App\Support\Durasi::label($rata) ?? '0j 0m' }}
                            </td>
                            <td class="tabular px-4 py-3 text-right">
                                @if ($b['terlambat'] > 0)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">{{ $b['terlambat'] }}x</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="tabular px-4 py-3 text-right">
                                @if ($b['belum_pulang'] > 0)
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700">{{ $b['belum_pulang'] }}x</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($baris->hasPages())
        <div class="mt-3">{{ $baris->links() }}</div>
    @endif
@endsection