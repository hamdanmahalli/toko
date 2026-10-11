<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kas</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 17px; margin: 0 0 2px; }
        .sub { color: #64748b; font-size: 10px; margin: 0; }
        .ringkas { width: 100%; border-collapse: collapse; margin: 14px 0 16px; }
        .ringkas td { width: 25%; border: 1px solid #e2e8f0; padding: 7px 9px; }
        .ringkas .label { color: #64748b; font-size: 9px; text-transform: uppercase; letterspacing: .04em; }
        .ringkas .nilai { font-size: 12px; font-weight: bold; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #e2e8f0; padding: 5px 7px; text-align: left; }
        table.data th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; color: #475569; }
        table.data td.angka, table.data th.angka { text-align: right; }
        tr.total td { background: #f8fafc; font-weight: bold; }
        .masuk { color: #2b7a53; }
        .keluar { color: #b23a2e; }
        .lampiran { width: 100%; border-collapse: collapse; }
        .lampiran td { border: 1px solid #e2e8f0; padding: 8px 10px; vertical-align: top; }
        .lampiran td.thumb { width: 210px; }
        .lampiran img { width: 190px; max-height: 150px; }
        .lampiran .judul { font-weight: bold; font-size: 11px; color: #334155; }
        .lampiran .muted { color: #64748b; font-size: 9px; margin-top: 2px; }
    </style>
</head>
<body>
    <h1>Laporan Kas</h1>
    <p class="sub">
        {{ $employee->nama }} &middot;
        Periode {{ $dari->format('d/m/Y') }} &ndash; {{ $sampai->format('d/m/Y') }}
        @if ($bukuId) &middot; {{ optional($buku->first())->nama }} @endif
    </p>

    <table class="ringkas">
        <tr>
            <td>
                <div class="label">Saldo awal</div>
                <div class="nilai">{{ \App\Support\Rupiah::format($rekap['ringkasan']['saldo_awal']) }}</div>
            </td>
            <td>
                <div class="label">Pemasukan</div>
                <div class="nilai masuk">{{ \App\Support\Rupiah::format($rekap['ringkasan']['masuk']) }}</div>
            </td>
            <td>
                <div class="label">Pengeluaran</div>
                <div class="nilai keluar">{{ \App\Support\Rupiah::format($rekap['ringkasan']['keluar']) }}</div>
            </td>
            <td>
                <div class="label">Saldo akhir</div>
                <div class="nilai">{{ \App\Support\Rupiah::format($rekap['ringkasan']['saldo_akhir']) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 12%">Tanggal</th>
                <th style="width: 18%">Buku</th>
                <th style="width: 24%">Kategori</th>
                <th class="angka" style="width: 15%">Masuk</th>
                <th class="angka" style="width: 15%">Keluar</th>
                <th class="angka" style="width: 16%">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap['baris'] as $b)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($b['tanggal'])->format('d/m/Y') }}</td>
                    <td>{{ $b['buku'] }}</td>
                    <td>
                        {{ $b['kategori'] }}
                        @if ($b['keterangan'])
                            <div style="color:#94a3b8; font-size:9px;">{{ $b['keterangan'] }}</div>
                        @endif
                    </td>
                    <td class="angka masuk">{{ $b['masuk'] ? \App\Support\Rupiah::format($b['masuk']) : '-' }}</td>
                    <td class="angka keluar">{{ $b['keluar'] ? \App\Support\Rupiah::format($b['keluar']) : '-' }}</td>
                    <td class="angka">{{ \App\Support\Rupiah::format($b['saldo']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:14px;">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if (! empty($rekap['baris']))
            <tfoot>
                <tr class="total">
                    <td colspan="3">Total</td>
                    <td class="angka masuk">{{ \App\Support\Rupiah::format($rekap['ringkasan']['masuk']) }}</td>
                    <td class="angka keluar">{{ \App\Support\Rupiah::format($rekap['ringkasan']['keluar']) }}</td>
                    <td class="angka">{{ \App\Support\Rupiah::format($rekap['ringkasan']['saldo_akhir']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @php
        $lampiran = array_values(array_filter($rekap['baris'], fn ($b) => ! empty($b['gambar'])));
    @endphp
    @if (! empty($lampiran))
        <div style="page-break-before: always;">
            <h1>Lampiran Bukti</h1>
            <p class="sub">
                {{ $employee->nama }} &middot;
                Periode {{ $dari->format('d/m/Y') }} &ndash; {{ $sampai->format('d/m/Y') }}
                &middot; {{ count($lampiran) }} bukti
            </p>

            <table class="lampiran">
                @foreach ($lampiran as $b)
                    @php $uri = \App\Support\Gambar::dataUri($b['gambar']); @endphp
                    <tr>
                        <td class="thumb">
                            @if ($uri)
                                <img src="{{ $uri }}" alt="Bukti transaksi">
                            @else
                                <span class="muted">Gambar tidak tersedia</span>
                            @endif
                        </td>
                        <td>
                            <div class="judul">{{ $b['kategori'] }} &middot; {{ $b['buku'] }}</div>
                            <div class="muted">
                                {{ \Illuminate\Support\Carbon::parse($b['tanggal'])->format('d/m/Y') }}
                                @if ($b['keterangan']) &middot; {{ $b['keterangan'] }} @endif
                            </div>
                            <div class="muted" style="margin-top: 4px;">
                                @if ($b['masuk'])
                                    Masuk {{ \App\Support\Rupiah::format($b['masuk']) }}
                                @else
                                    Keluar {{ \App\Support\Rupiah::format($b['keluar']) }}
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</body>
</html>
