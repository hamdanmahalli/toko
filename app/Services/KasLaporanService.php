<?php

namespace App\Services;

use App\Enums\JenisKas;
use App\Models\CashBook;
use App\Models\CashBookTransaction;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rekap laporan kas.
 *
 * Baris dihitung dari transaksi yang benar-benar tercatat supaya mengubah
 * saldo awal atau menghapus transaksi tidak diam-diam mengubah angka periode
 * lampau. Saldo tiap baris adalah saldo berjalan per buku, jadi kolom saldo
 * tetap terbaca walau laporan menampilkan beberapa buku sekaligus.
 */
class KasLaporanService
{
    /**
     * @return array{
     *     baris: array<int, array<string, mixed>>,
     *     ringkasan: array<string, float|int>,
     *     buku: Collection<int, CashBook>
     * }
     */
    public function rekap(Employee $employee, ?int $bukuId, Carbon $dari, Carbon $sampai): array
    {
        // Seluruh buku tetap dikirim sebagai pilihan filter; yang dihitung
        // hanya buku terpilih agar daftar "Buku" di halaman laporan tetap bisa
        // diganti walau sedang menyaring satu buku.
        $semuaBuku = CashBook::query()
            ->where('employee_id', $employee->id)
            ->orderBy('nama')
            ->get();

        $buku = $bukuId !== null
            ? $semuaBuku->where('id', $bukuId)->values()
            : $semuaBuku;

        if ($buku->isEmpty()) {
            return [
                'baris' => [],
                'ringkasan' => $this->ringkasanKosong(),
                'buku' => $semuaBuku,
            ];
        }

        $namaBuku = $buku->pluck('nama', 'id');
        $namaKategori = $employee->cashCategories()->pluck('nama', 'kode')->all();
        $saldoAwalBuku = $this->saldoAwalPeriode($buku, $dari);
        $saldoBerjalan = $saldoAwalBuku;

        $transaksi = CashBookTransaction::query()
            ->whereIn('cash_book_id', $buku->pluck('id'))
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('cash_book_id')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $baris = [];
        $totalMasuk = 0.0;
        $totalKeluar = 0.0;

        foreach ($transaksi as $trx) {
            $masuk = $trx->jenis === JenisKas::Masuk ? (float) $trx->jumlah : 0.0;
            $keluar = $trx->jenis === JenisKas::Keluar ? (float) $trx->jumlah : 0.0;

            $saldoBerjalan[$trx->cash_book_id] = ($saldoBerjalan[$trx->cash_book_id] ?? 0.0) + $masuk - $keluar;
            $totalMasuk += $masuk;
            $totalKeluar += $keluar;

            $baris[] = [
                'tanggal' => $trx->tanggal->toDateString(),
                'buku' => $namaBuku[$trx->cash_book_id] ?? '-',
                'kategori' => $namaKategori[$trx->kategori] ?? Str::headline((string) $trx->kategori),
                'jenis' => $trx->jenis->label(),
                'keterangan' => $trx->keterangan,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'saldo' => $saldoBerjalan[$trx->cash_book_id],
                'gambar' => $trx->gambar,
                'gambar_url' => $trx->gambar ? $trx->gambarUrl() : null,
            ];
        }

        $saldoAwal = array_sum($saldoAwalBuku);

        return [
            'baris' => $baris,
            'ringkasan' => [
                'saldo_awal' => $saldoAwal,
                'masuk' => $totalMasuk,
                'keluar' => $totalKeluar,
                'selisih' => $totalMasuk - $totalKeluar,
                'saldo_akhir' => $saldoAwal + $totalMasuk - $totalKeluar,
                'jumlah_buku' => $buku->count(),
            ],
            'buku' => $semuaBuku,
        ];
    }

    /**
     * Saldo tiap buku tepat sebelum tanggal `dari`: saldo awal buku ditambah
     * seluruh mutasi sebelum periode.
     *
     * @param  Collection<int, CashBook>  $buku
     * @return array<int, float>
     */
    private function saldoAwalPeriode(Collection $buku, Carbon $dari): array
    {
        $mutasi = CashBookTransaction::query()
            ->whereIn('cash_book_id', $buku->pluck('id'))
            ->where('tanggal', '<', $dari->toDateString())
            ->selectRaw(
                'cash_book_id, sum(case when jenis = ? then jumlah else -jumlah end) as selisih',
                [JenisKas::Masuk->value],
            )
            ->groupBy('cash_book_id')
            ->pluck('selisih', 'cash_book_id');

        return $buku
            ->mapWithKeys(fn (CashBook $b) => [
                $b->id => (float) $b->saldo_awal + (float) ($mutasi[$b->id] ?? 0),
            ])
            ->all();
    }

    /**
     * @return array<string, float|int>
     */
    private function ringkasanKosong(): array
    {
        return [
            'saldo_awal' => 0.0,
            'masuk' => 0.0,
            'keluar' => 0.0,
            'selisih' => 0.0,
            'saldo_akhir' => 0.0,
            'jumlah_buku' => 0,
        ];
    }
}
