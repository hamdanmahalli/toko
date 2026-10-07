<?php

namespace App\Enums;

enum PayrollType: string
{
    case Harian = 'harian';
    case Jam = 'jam';

    public function label(): string
    {
        return match ($this) {
            self::Harian => 'Gaji Harian',
            self::Jam => 'Gaji Per Jam',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
