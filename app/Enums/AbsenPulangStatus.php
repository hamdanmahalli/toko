<?php

namespace App\Enums;

enum AbsenPulangStatus: string
{
    case TepatWaktu = 'tepat_waktu';
    case PulangCepat = 'pulang_cepat';

    public function label(): string
    {
        return match ($this) {
            self::TepatWaktu => 'Tepat Waktu',
            self::PulangCepat => 'Pulang Cepat',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TepatWaktu => 'bg-emerald-100 text-emerald-700',
            self::PulangCepat => 'bg-rose-100 text-rose-700',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
