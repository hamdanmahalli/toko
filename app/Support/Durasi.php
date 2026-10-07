<?php

namespace App\Support;

/**
 * Format durasi dalam menit supaya tampilan jam kerja konsisten di semua layar.
 *
 * Absensi menyimpan durasi dalam menit karena itu satuan yang aman di database.
 * Menampilkan "540" di halaman rekap tidak terbaca, jadi selalu diubah ke
 * "9j 0m" lewat kelas ini, bukan format ulang di tiap view.
 */
class Durasi
{
    /** 540 menjadi "9j 0m", 570 menjadi "9j 30m", null tetap null. */
    public static function label(?int $menit): ?string
    {
        if ($menit === null) {
            return null;
        }

        return intdiv($menit, 60).'j '.($menit % 60).'m';
    }
}
