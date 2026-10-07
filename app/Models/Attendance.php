<?php

namespace App\Models;

use App\Enums\AbsenMasukStatus;
use App\Enums\AbsenMethod;
use App\Enums\AbsenPulangStatus;
use App\Support\Durasi;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'shop_id', 'tanggal', 'sesi', 'jam_masuk', 'jam_pulang',
    'durasi_menit', 'durasi_maks_menit',
    'shift_label_masuk', 'shift_window_id', 'shift_label_pulang',
    'status_masuk', 'status_pulang',
    'latitude_masuk', 'longitude_masuk', 'jarak_masuk_meter', 'accuracy_masuk_meter',
    'latitude_pulang', 'longitude_pulang', 'jarak_pulang_meter', 'accuracy_pulang_meter',
    'foto_masuk', 'foto_pulang', 'metode', 'device_id', 'catatan', 'created_by',
])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'sesi' => 'integer',
            'jam_masuk' => 'datetime:H:i',
            'jam_pulang' => 'datetime:H:i',
            'durasi_menit' => 'integer',
            'durasi_maks_menit' => 'integer',
            'status_masuk' => AbsenMasukStatus::class,
            'status_pulang' => AbsenPulangStatus::class,
            'latitude_masuk' => 'float',
            'longitude_masuk' => 'float',
            'jarak_masuk_meter' => 'float',
            'accuracy_masuk_meter' => 'float',
            'latitude_pulang' => 'float',
            'longitude_pulang' => 'float',
            'jarak_pulang_meter' => 'float',
            'accuracy_pulang_meter' => 'float',
            'metode' => AbsenMethod::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Window shift yang dipakai saat absen masuk.
     *
     * Disimpan supaya absen pulang tidak perlu menebak window mana yang
     * dipakai, karena jam pulang bisa jatuh di window yang berbeda.
     */
    public function shiftWindow(): BelongsTo
    {
        return $this->belongsTo(ShiftWindow::class, 'shift_window_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePadaTanggal(Builder $query, string $tanggal): Builder
    {
        return $query->whereDate('tanggal', $tanggal);
    }

    public function scopeAntara(Builder $query, string $mulai, string $selesai): Builder
    {
        return $query->whereBetween('tanggal', [$mulai, $selesai]);
    }

    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->where('status_masuk', AbsenMasukStatus::Terlambat->value);
    }

    public function sudahPulang(): bool
    {
        return $this->jam_pulang !== null;
    }

    /**
     * Durasi kerja dalam menit, atau null bila belum absen pulang.
     *
     * Mengutamakan nilai yang disimpan saat absen pulang karena jam disimpan
     * hanya sebagai jammenit. Kalau belum ada, dihitung ulang dari jam masuk
     * dan jam pulang; selisih negatif diMasbahkan 24 jam supaya shift yang
     * lewat tengah malam tidak jadi negatif.
     */
    public function durasiKerjaMenit(): ?int
    {
        if ($this->durasi_menit !== null) {
            return $this->durasi_menit;
        }

        if ($this->jam_masuk === null || $this->jam_pulang === null) {
            return null;
        }

        $menit = $this->jam_pulang->hour * 60 + $this->jam_pulang->minute
            - ($this->jam_masuk->hour * 60 + $this->jam_masuk->minute);

        if ($menit < 0) {
            $menit += 24 * 60;
        }

        return $menit;
    }

    /** Durasi kerja efektif dalam jam, sebagai desimal (mis. 7.5). */
    public function durasiKerja(): ?float
    {
        $menit = $this->durasiKerjaMenit();

        return $menit === null ? null : round($menit / 60, 2);
    }

    /** Durasi kerja dalam format ramah baca, mis. "7j 30m". */
    public function durasiKerjaLabel(): ?string
    {
        return Durasi::label($this->durasiKerjaMenit());
    }

    /**
     * True bila durasi kerja melewati batas yang ditetapkan shift.
     *
     * Ini murni informasi: durasi tidak dipotong, supaya tidak mengganggu
     * perhitungan lembur.
     */
    public function lewatDurasiMaks(): bool
    {
        return $this->durasi_maks_menit !== null
            && $this->durasiKerjaMenit() !== null
            && $this->durasiKerjaMenit() > $this->durasi_maks_menit;
    }
}
