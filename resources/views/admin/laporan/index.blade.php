@extends('layouts.admin')

@section('judul', 'Laporan')

@section('konten')
    <x-page-header class="mb-4" judul="Laporan durasi kerja"
                   sub="Dihitung dari absensi yang tercatat, jadi angka bulan lalu tidak ikut berubah saat aturan shift diubah.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 19v-6m4 6V9m4 10v-3"/>
            </svg>
        </x-slot:ikon>

        {{-- Hanya tampil di APK: cetak ringkasan ke printer thermal Bluetooth. --}}
        <x-slot:aksi>
            <button type="button" id="cetak-thermal" hidden
                    class="inline-flex items-center gap-2 rounded-lg border border-brand-200 bg-white px-3 py-2 text-sm font-medium text-brand-700 transition hover:bg-brand-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/>
                </svg>
                Cetak struk
            </button>
        </x-slot:aksi>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-3 rounded-lg bg-merah-50 px-3 py-2 text-sm text-merah-700 ring-1 ring-merah-200">
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
                                    <span class="rounded-full bg-merah-100 px-2 py-0.5 text-[11px] font-medium text-merah-700">{{ $b['terlambat'] }}x</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="tabular px-4 py-3 text-right">
                                @if ($b['belum_pulang'] > 0)
                                    <span class="rounded-full bg-merah-100 px-2 py-0.5 text-[11px] font-medium text-merah-700">{{ $b['belum_pulang'] }}x</span>
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

@php
    $ringkasanCetak = [
        'Total jam kerja' => \App\Support\Durasi::label($ringkasan['menit']) ?? '0j 0m',
        'Karyawan hadir' => $ringkasan['karyawan'].' orang',
        'Total hari kerja' => $ringkasan['hari'].' hari',
        'Total sesi' => $ringkasan['sesi'].' sesi',
    ];
    $rentangCetak = $dari.' s/d '.$sampai;
@endphp

@push('kaki')
<script>
    (function () {
        const tombol = document.getElementById('cetak-thermal');
        if (!tombol || !window.TokoNative || !window.TokoNative.aktif) return;
        tombol.hidden = false;

        const ringkasan = @json($ringkasanCetak);
        const rentang = @json($rentangCetak);

        tombol.addEventListener('click', function () {
            const baris = [].slice.call(document.querySelectorAll('table tbody tr')).map(function (tr) {
                const sel = tr.querySelectorAll('td');
                const nama = (sel[0] ? sel[0].querySelector('p') : null);
                return {
                    nama: nama ? nama.textContent.trim() : (sel[0] ? sel[0].textContent.trim() : ''),
                    toko: sel[1] ? sel[1].textContent.trim() : '',
                    total: sel[4] ? sel[4].textContent.trim() : '',
                };
            });

            const lines = [];
            lines.push({ teks: 'ABSENSI TOKO MM', tebal: true, besar: true, tengah: true });
            lines.push({ teks: 'Laporan durasi kerja', tengah: true });
            lines.push({ teks: rentang, tengah: true });
            lines.push({ garis: true, panjang: 32 });

            Object.keys(ringkasan).forEach(function (k) {
                lines.push(k + ': ' + ringkasan[k]);
            });

            if (baris.length) {
                lines.push({ garis: true, panjang: 32 });
                baris.slice(0, 40).forEach(function (b) {
                    lines.push({ teks: b.nama, tebal: true });
                    lines.push('  ' + b.toko + ' - ' + b.total);
                });
            }

            lines.push({ garis: true, panjang: 32 });
            lines.push({ teks: 'Dicetak: ' + new Date().toLocaleString('id-ID'), tengah: true });

            const labelAsli = tombol.innerHTML;
            tombol.disabled = true;
            window.TokoNative.cetakPrinter(lines)
                .then(function () { tombol.textContent = 'Tercetak'; })
                .catch(function (e) {
                    window.TokoDialog.pesan({
                        judul: 'Cetak gagal',
                        pesan: e && e.message ? e.message : 'Cetak gagal.',
                    });
                })
                .finally(function () {
                    setTimeout(function () {
                        tombol.innerHTML = labelAsli;
                        tombol.disabled = false;
                    }, 1500);
                });
        });
    })();
</script>
@endpush