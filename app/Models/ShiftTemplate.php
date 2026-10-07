<?php

namespace App\Models;

use App\Enums\FleksibelTipe;
use App\Enums\ShiftScope;
use App\Enums\ShiftTipe;
use App\Support\Durasi;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\ShiftTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'nama', 'kode', 'tipe', 'fleksibel_tipe', 'durasi_kerja_menit', 'jam_cut_off',
    'durasi_maks_menit', 'scope', 'shop_id', 'keterangan', 'aktif',
])]
class ShiftTemplate extends Model
{
    /** @use HasFactory<ShiftTemplateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipe' => ShiftTipe::class,
            'fleksibel_tipe' => FleksibelTipe::class,
            'durasi_kerja_menit' => 'integer',
            'durasi_maks_menit' => 'integer',
            'jam_cut_off' => 'datetime:H:i',
            'scope' => ShiftScope::class,
            'aktif' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ShiftSlot::class);
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * Sesi jam kerja, hanya dipakai template bertipe interval.
     *
     * @return HasMany<ShiftInterval, $this>
     */
    public function intervals(): HasMany
    {
        return $this->hasMany(ShiftInterval::class)->orderBy('urutan');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /** Sesi aktif template, sudah terurut sesuai urutan yang ditampilkan admin. */
    public function intervalsAktif(): Collection
    {
        return $this->intervals->where('aktif', true)->values();
    }

    /**
     * Sesi interval yang mencakup waktu ini.
     *
     * Bila sesi saling tumpang tindih, sesi yang urutannya lebih dulu menang
     * supaya hasilnya tidak bergantung pada urutan query database.
     */
    public function intervalPada(CarbonInterface $waktu): ?ShiftInterval
    {
        return $this->intervalsAktif()->first(fn (ShiftInterval $interval) => $interval->mencakup(Carbon::parse($waktu)));
    }

    /** Slot untuk hari tertentu (0=Minggu ... 6=Sabtu), null bila libur. */
    public function slotFor(int $dayOfWeek): ?ShiftSlot
    {
        return $this->slots->firstWhere('hari', $dayOfWeek);
    }

    /** Template lama tanpa tipe dianggap tetap, jadi tidak ada perubahan diam-diam. */
    public function tipe(): ShiftTipe
    {
        return $this->tipe ?? ShiftTipe::Tetap;
    }

    /** Jenis fleksibel yang dipilih, null bila template ini bukan fleksibel. */
    public function fleksibelTipe(): ?FleksibelTipe
    {
        return $this->tipe() === ShiftTipe::Fleksibel ? $this->fleksibel_tipe : null;
    }

    /** True bila shift tipe ini boleh melewati tengah malam. */
    public function bolehLintasMalam(): bool
    {
        return $this->tipe()->bolehLintasMalam();
    }

    /** Durasi kerja pasti dalam menit, hanya untuk fleksibel durasi tetap. */
    public function durasiKerja(): ?int
    {
        return $this->durasi_kerja_menit ?: null;
    }

    public function durasiKerjaLabel(): ?string
    {
        return Durasi::label($this->durasiKerja());
    }

    /** Batas durasi kerja bawaan template, belum termasuk penimpaan per hari. */
    public function durasiMaks(): ?int
    {
        return $this->durasi_maks_menit ?: null;
    }

    public function durasiMaksLabel(): ?string
    {
        return Durasi::label($this->durasiMaks());
    }

    /**
     * Jam paling lambat scan masuk, atau null bila tidak ada batas.
     *
     * Berlaku untuk semua tipe shift karena ini aturan scanning, bukan aturan
     * jam kerja: orang yang telat sejam tidak boleh masuk lewat tengah malam
     * lalu memilihkan shift-nya sendiri.
     */
    public function jamCutOff(): ?CarbonInterface
    {
        return $this->jam_cut_off;
    }
}
