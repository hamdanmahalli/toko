<?php

namespace App\Enums;

enum AbsenMethod: string
{
    case Qr = 'qr';
    case Selfie = 'selfie';
    case Presensi = 'presensi';

    public function label(): string
    {
        return match ($this) {
            self::Qr => 'Scan QR',
            self::Selfie => 'Selfie',
            self::Presensi => 'Presensi Karyawan',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
