<?php

namespace App\Models;

use App\Support\Durasi;
use Database\Factories\ShiftIntervalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu sesi jam kerja dalam template shift bertipe interval.
 *
 * Sesi tidak memakai slot harian karena sifatnya berbeda: interval bisa punya
 * beberapa sesi dalam satu hari dan tidak terikat jam masuk template.
 */
class ShiftInterval extends Model
{
    /** @use HasFactory<ShiftIntervalFactory> */
    use HasFactory;

    protected $fillable = [
        'shift_template_id',
        'nama',
        'mulai',
        'selesai',
        'durasi_min_menit',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'mulai' => 'datetime:H:i',
            'selesai' => 'datetime:H:i',
            'durasi_min_menit' => 'integer',
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /** Sesi selalu milik template yang menetapkannya. */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    /** Sesi malam boleh melewati tengah malam, misalnya 22:00-02:00. */
    public function lintasMalam(): bool
    {
        return $this->selesai < $this->mulai;
    }

    /**
     * Durasi rencana sesi dalam menit.
     *
     * Durasi minimum yang diisi admin menang karena itu angka yang mereka
     * tetapkan sendiri. Kalau kosong, durasi diturunkan dari rentang jam.
     */
    public function durasi(): ?int
    {
        if ($this->durasi_min_menit !== null) {
            return $this->durasi_min_menit;
        }

        return $this->selesai->diffInMinutes($this->mulai, absolute: true);
    }

    public function durasiLabel(): ?string
    {
        return Durasi::label($this->durasi());
    }

    /** "08:00 - 12:00", dipakai di daftar sesi pada form template. */
    public function rentang(): string
    {
        return $this->mulai->format('H:i').' - '.$this->selesai->format('H:i');
    }

    /**
     * Apakah waktu ini berada di dalam sesi.
     *
     * Sesi selalu menyertakan titik awal dan titik akhir, jadi scan tepat di
     * jam selesai masih dianggap sah.
     */
    public function mencakup(Carbon $waktu): bool
    {
        $mulai = Carbon::parse($waktu->toDateString())->setTimeFrom($this->mulai)->second(0);
        $selesai = Carbon::parse($waktu->toDateString())->setTimeFrom($this->selesai)->second(0);

        // Sesi 22:00-02:00 dihitung terhadap hari yang sama supaya waktu 01:00
        // tetap dikenali sebagai bagian dari sesi yang mulai semalam.
        if ($selesai->lessThanOrEqualTo($mulai)) {
            $selesai = $selesai->copy()->addDay();
        }

        return $waktu->greaterThanOrEqualTo($mulai) && $waktu->lessThanOrEqualTo($selesai);
    }
}
