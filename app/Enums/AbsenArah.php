<?php

namespace App\Enums;

/**
 * Arah absensi: masuk atau pulang.
 *
 * Perangkat presensi memakainya untuk memberi tahu pengguna apa yang akan
 * dicatat saat kartu dipindai, supaya tidak perlu tombol tambahan di
 * halaman perangkat presensi.
 */
enum AbsenArah: string
{
    case Masuk = 'masuk';
    case Pulang = 'pulang';

    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Absen Masuk',
            self::Pulang => 'Absen Pulang',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
