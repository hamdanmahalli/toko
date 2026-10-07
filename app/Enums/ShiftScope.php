<?php

namespace App\Enums;

enum ShiftScope: string
{
    case Global = 'global';
    case Toko = 'toko';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Semua Toko',
            self::Toko => 'Per Toko',
            self::Individual => 'Per Karyawan',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
