<?php

namespace App\Models;

use Database\Factories\ShiftSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shift_template_id', 'hari', 'jam_masuk', 'batas_telat',
    'jam_pulang', 'durasi_maks_menit', 'mulai_istirahat', 'selesai_istirahat', 'aktif',
])]
class ShiftSlot extends Model
{
    /** @use HasFactory<ShiftSlotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'jam_masuk' => 'datetime:H:i',
            'batas_telat' => 'datetime:H:i',
            'jam_pulang' => 'datetime:H:i',
            'durasi_maks_menit' => 'integer',
            'mulai_istirahat' => 'datetime:H:i',
            'selesai_istirahat' => 'datetime:H:i',
            'aktif' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function hariLabel(): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$this->hari] ?? '-';
    }

    /**
     * Batas durasi kerja milik slot ini, atau null berarti tidak dibatasi.
     * Nilai di slot menang atas nilai default di template.
     */
    public function durasiMaks(): ?int
    {
        return $this->durasi_maks_menit ?: $this->template?->durasi_maks_menit ?: null;
    }

    /**
     * True bila jam pulang lebih awal daripada jam masuk, artinya shift
     * dilanjutkan ke hari berikutnya.
     *
     * Hanya tipe shift fleksibel dan interval yang boleh sampai ke sini;
     * shift tetap ditolak di lapisan validasi.
     */
    public function lintasMalam(): bool
    {
        if (! $this->jam_masuk || ! $this->jam_pulang) {
            return false;
        }

        $masuk = $this->jam_masuk->hour * 60 + $this->jam_masuk->minute;
        $pulang = $this->jam_pulang->hour * 60 + $this->jam_pulang->minute;

        return $pulang < $masuk;
    }

    /**
     * Durasi istirahat dalam menit, 0 bila tidak ada istirahat.
     *
     * Dihitung dari jam dan menit, bukan lewat diffInMinutes, karena jam
     * disimpan sebagai waktu bebas dan nilai diff-nya mengikuti konvensi
     * penulisan argumen Carbon yang mudah terbalik.
     */
    public function durasiIstirahat(): int
    {
        if (! $this->mulai_istirahat || ! $this->selesai_istirahat) {
            return 0;
        }

        $dari = $this->mulai_istirahat->hour * 60 + $this->mulai_istirahat->minute;
        $sampai = $this->selesai_istirahat->hour * 60 + $this->selesai_istirahat->minute;

        return max(0, $sampai - $dari);
    }
}
