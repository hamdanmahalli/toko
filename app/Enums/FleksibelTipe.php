<?php

namespace App\Enums;

/**
 * Tiga bentuk shift fleksibel.
 *
 * Bedanya cuma pada satu hal: jam datang dikunci atau tidak, dan jam pulang
 * diambil dari mana.
 */
enum FleksibelTipe: string
{
    /** Boleh datang kapan saja, jam pulang bebas. */
    case Bebas = 'bebas';

    /** Harus datang di antara jam masuk dan batas telat pada slot. */
    case Terbatas = 'terbatas';

    /** Boleh datang kapan saja, jam pulang = jam datang + durasi kerja. */
    case DurasiTetap = 'durasi_tetap';

    public function label(): string
    {
        return match ($this) {
            self::Bebas => 'Bebas',
            self::Terbatas => 'Terbatas',
            self::DurasiTetap => 'Durasi tetap',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::Bebas => 'Absen masuk kapan saja, jam pulang tidak menjadi acuan.',
            self::Terbatas => 'Absen masuk harus di antara jam masuk dan batas telat.',
            self::DurasiTetap => 'Jam pulang dihitung dari jam datang ditambah durasi kerja.',
        };
    }

    /** True bila jam datang dikunci pada jam tertentu. */
    public function jamMasukDikunci(): bool
    {
        return $this === self::Terbatas;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
