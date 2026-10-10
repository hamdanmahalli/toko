<?php

namespace App\Enums;

enum StatusPerangkat: string
{
    case Pending = 'pending';
    case Disetujui = 'approved';
    case Ditolak = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Baru belum dikenal',
            self::Disetujui => 'Diizinkan',
            self::Ditolak => 'Ditolak',
        };
    }
}
