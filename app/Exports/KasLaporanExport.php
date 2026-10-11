<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor laporan kas ke Excel. Baris dihitung di KasLaporanService lalu
 * dikirim apa adanya ke sini supaya angka ekspor identik dengan yang tampil
 * di layar.
 */
class KasLaporanExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, array<string, mixed>>  $baris
     */
    public function __construct(private array $baris) {}

    public function title(): string
    {
        return 'Laporan Kas';
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Tanggal', 'Buku', 'Kategori', 'Jenis', 'Keterangan', 'Bukti', 'Masuk', 'Keluar', 'Saldo'];
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return collect($this->baris)
            ->map(fn (array $b) => [
                $b['tanggal'],
                $b['buku'],
                $b['kategori'],
                $b['jenis'],
                $b['keterangan'] ?? '-',
                $b['gambar'] ?? '-',
                $b['masuk'] ? (float) $b['masuk'] : null,
                $b['keluar'] ? (float) $b['keluar'] : null,
                (float) $b['saldo'],
            ])
            ->all();
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 20,
            'C' => 18,
            'D' => 13,
            'E' => 32,
            'F' => 28,
            'G' => 16,
            'H' => 16,
            'I' => 18,
        ];
    }

    /** @return array<string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $barisTerakhir = count($this->baris) + 1;

        return [
            1 => ['font' => ['bold' => true]],
            "G2:I{$barisTerakhir}" => ['numberFormat' => ['formatCode' => '#,##0']],
        ];
    }
}
