<?php

namespace App\Enums;

enum AbsenMasukStatus: string
{
    case TepatWaktu = 'tepat_waktu';
    case Terlambat = 'terlambat';

    public function label(): string
    {
        return match ($this) {
            self::TepatWaktu => 'Tepat Waktu',
            self::Terlambat => 'Terlambat',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TepatWaktu => 'bg-emerald-100 text-emerald-700',
            self::Terlambat => 'bg-merah-100 text-merah-700',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
