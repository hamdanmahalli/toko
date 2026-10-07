<?php

namespace App\Enums;

enum LeaveType: string
{
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Cuti = 'cuti';
    case Dinas = 'dinas';

    public function label(): string
    {
        return match ($this) {
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::Cuti => 'Cuti',
            self::Dinas => 'Dinas Luar',
        };
    }

    /** hari yang tetap dibayar penuh */
    public function isPaid(): bool
    {
        return $this === self::Cuti || $this === self::Dinas;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
