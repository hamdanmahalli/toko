<?php

namespace App\Models;

use App\Enums\JenisKas;
use Database\Factories\CashBookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[
    Fillable(['employee_id', 'nama', 'keterangan', 'saldo_awal', 'aktif']),
]
class CashBook extends Model
{
    /** @use HasFactory<CashBookFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'saldo_awal' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashBookTransaction::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Saldo berjalan buku ini. Memakai hasil withSum bila sudah dimuat supaya
     * daftar buku tidak menembak satu query per baris.
     */
    public function saldoSaatIni(): float
    {
        $masuk = $this->total_masuk
            ?? $this->transactions()->where('jenis', JenisKas::Masuk->value)->sum('jumlah');

        $keluar = $this->total_keluar
            ?? $this->transactions()->where('jenis', JenisKas::Keluar->value)->sum('jumlah');

        return (float) $this->saldo_awal + (float) $masuk - (float) $keluar;
    }
}
