<?php

namespace App\Enums;

/**
 * Cara jam shift pada sebuah template dibaca.
 *
 * Ini yang menentukan apakah semua orang harus datang jam yang sama, boleh
 * datang kapan saja dalam rentang tertentu, atau satu hari dipecah jadi
 * beberapa sesi scan.
 */
enum ShiftTipe: string
{
    /** Jam masuk dan jam pulang tetap untuk semua orang. */
    case Tetap = 'tetap';

    /** Boleh datang lebih pagi/lambat sesuai aturan fleksibel yang dipilih. */
    case Fleksibel = 'fleksibel';

    /** Satu hari dipecah jadi beberapa sesi, misalnya shift sales punya dua sesi. */
    case Interval = 'interval';

    public function label(): string
    {
        return match ($this) {
            self::Tetap => 'Tetap',
            self::Fleksibel => 'Fleksibel',
            self::Interval => 'Interval',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::Tetap => 'Jam masuk dan jam pulang sama untuk semua orang.',
            self::Fleksibel => 'Boleh datang di rentang jam tertentu atau bebas, sesuai jenis fleksibel.',
            self::Interval => 'Satu hari dibagi beberapa sesi scan masuk dan pulang.',
        };
    }

    /**
     * True bila shift tipe ini boleh melewati tengah malam.
     *
     * Shift tetap sengaja ditolak: aturan lama forbid shift yang melewati
     * tengah malam dan mengubahnya diam-diam akan merusak data absensi lama.
     */
    public function bolehLintasMalam(): bool
    {
        return $this !== self::Tetap;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
