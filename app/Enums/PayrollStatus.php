<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Final = 'final';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Final => 'Final',
            self::Batal => 'Dibatalkan',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
