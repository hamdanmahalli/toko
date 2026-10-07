<?php

namespace App\Models;

use App\Enums\AturanAbsensi;
use App\Support\Durasi;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pita waktu shift dalam sehari, misalnya "Pagi" 06:00-14:00 atau "Siang"
 * 13:00-21:00. Menentukan shift siapa pun yang jam datangnya berada di pita
 * ini, sehingga kasir dan pramuniaga tidak perlu template shift per orang.
 *
 * Berbeda dengan ShiftTemplate: template berisi jadwal mingguan per hari,
 * sedangkan window ini cuma pita jam. Keduanya saling melengkapi, bukan saingan.
 *
 * Karyawan dengan jabatan `pakai_template` tetap memakai template dan
 * mengabaikan window.
 */
#[Fillable(['nama', 'kode', 'mulai', 'batas_telat', 'selesai', 'aturan_absensi', 'durasi_maks_menit', 'shop_id', 'urutan', 'aktif'])]
class ShiftWindow extends Model
{
    protected function casts(): array
    {
        return [
            'mulai' => 'datetime:H:i',
            'batas_telat' => 'datetime:H:i',
            'selesai' => 'datetime:H:i',
            'aturan_absensi' => AturanAbsensi::class,
            'durasi_maks_menit' => 'integer',
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Window aktif milik toko tersebut atau window global (shop_id null). */
    public function scopeUntukToko(Builder $query, int|Shop|null $shop): Builder
    {
        $id = $shop instanceof Shop ? $shop->id : $shop;

        return $query->where('aktif', true)
            ->where(fn ($q) => $q->whereNull('shop_id')->orWhere('shop_id', $id));
    }

    public function label(): string
    {
        return $this->mulai->format('H:i').'-'.$this->selesai->format('H:i');
    }

    /** Jam paling lambat masuk shift ini tanpa kena status terlambat. */
    public function jamBatasTelat(): CarbonInterface
    {
        return $this->batas_telat ?? $this->mulai;
    }

    /** Jam terakhir kerja shift ini, dipakai menilai pulang cepat. */
    public function jamPulang(): CarbonInterface
    {
        return $this->selesai;
    }

    /** Apakah waktu ini berada di dalam pita window. */
    public function mencakup(CarbonInterface $waktu): bool
    {
        $menit = $waktu->hour * 60 + $waktu->minute;

        $dari = $this->mulai->hour * 60 + $this->mulai->minute;
        $sampai = $this->selesai->hour * 60 + $this->selesai->minute;

        return $menit >= $dari && $menit <= $sampai;
    }

    /**
     * Batas telat pada tanggal tertentu, aman untuk perbandingan jam.
     */
    public function waktuBatasTelat(CarbonInterface $tanggal): Carbon
    {
        return Carbon::parse($tanggal->toDateString().' '.$this->jamBatasTelat()->format('H:i:s'));
    }

    /** Waktu pulang yang diharapkan, dihitung pada tanggal yang sama. */
    public function waktuPulang(CarbonInterface $tanggal): Carbon
    {
        return Carbon::parse($tanggal->toDateString().' '.$this->jamPulang()->format('H:i:s'));
    }

    /**
     * Sikap terhadap scan di luar pita ini.
     *
     * Window yang sudah dinonaktifkan tidak punya sifat apa pun karena tidak
     * lagi dipakai penilaian, jadi default-nya selalu toleran.
     */
    public function aturan(): AturanAbsensi
    {
        return $this->aturan_absensi ?? AturanAbsensi::Toleran;
    }

    /** Batas durasi kerja dalam menit, atau null bila tidak dibatasi. */
    public function durasiMaks(): ?int
    {
        return $this->durasi_maks_menit ?: null;
    }

    /** Batas durasi kerja dalam format tampil, mis. "8j 0m". */
    public function durasiMaksLabel(): ?string
    {
        return Durasi::label($this->durasiMaks());
    }
}
