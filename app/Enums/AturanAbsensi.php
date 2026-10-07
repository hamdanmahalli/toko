<?php

namespace App\Enums;

/**
 * Sikap sistem terhadap scan yang jatuh di luar area absensi shift.
 *
 * Ini menjawab pertanyaan "kalau orang datang jam 3 pagi, apa yang terjadi?"
 * yang tidak bisa dijawab hanya dari jam shift saja.
 */
enum AturanAbsensi: string
{
    /** Di luar area = langsung dianggap terlambat. */
    case Ketat = 'ketat';

    /** Di luar area = tidak dinilai, tapi tetap dicatat sebagai catatan. */
    case Toleran = 'toleran';

    public function label(): string
    {
        return match ($this) {
            self::Ketat => 'Ketat',
            self::Toleran => 'Toleran',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::Ketat => 'Absen di luar rentang shift langsung dihitung terlambat.',
            self::Toleran => 'Absen di luar rentang shift tetap dicatat, tapi tidak dihitung terlambat.',
        };
    }

    /** True bila di luar area absensi harus dianggap terlambat. */
    public function diLuarAreaTerlambat(): bool
    {
        return $this === self::Ketat;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
