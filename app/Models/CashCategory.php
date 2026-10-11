<?php

namespace App\Models;

use App\Enums\JenisKas;
use Database\Factories\CashCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable(['employee_id', 'kode', 'nama', 'jenis', 'urutan', 'aktif']),
]
class CashCategory extends Model
{
    /** @use HasFactory<CashCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'jenis' => JenisKas::class,
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function scopeJenis(Builder $query, JenisKas $jenis): Builder
    {
        return $query->where('jenis', $jenis->value);
    }
}
