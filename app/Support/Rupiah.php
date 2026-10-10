<?php

namespace App\Support;

/**
 * Format angka jadi rupiah supaya tampilan nominal konsisten di seluruh kas.
 *
 * Halaman kas, laporan, dan PDF memakai titik sebagai pemisah ribuan dan tanpa
 * desimal karena transaksi toko jarang butuh sen.
 */
class Rupiah
{
    public static function format(float|int|string|null $nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 0, ',', '.');
    }
}
