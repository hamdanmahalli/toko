<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

/**
 * Isi tabel `user_shop` dari toko data karyawan untuk akun yang sudah ada
 * sebelum sinkronisasi otomatis dipasang. Idempoten dan additive: aman
 * dijalankan berkali-kali, dan tidak menghapus penugasan toko lain.
 */
class SelarasTokoKaryawan extends Command
{
    protected $signature = 'pengguna:selaras-toko';

    protected $description = 'Tambahkan toko data karyawan ke daftar toko yang diawasi akunnya';

    public function handle(): int
    {
        $jumlah = 0;

        Employee::query()
            ->whereNotNull('user_id')
            ->whereNotNull('shop_id')
            ->with('user')
            ->chunkById(200, function ($karyawanList) use (&$jumlah): void {
                foreach ($karyawanList as $karyawan) {
                    $karyawan->user?->sertakanToko($karyawan->shop_id);
                    $jumlah++;
                }
            });

        $this->info("Selesai. {$jumlah} akun karyawan diselaraskan ke toko data karyawannya.");

        return self::SUCCESS;
    }
}
