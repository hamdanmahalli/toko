<?php

namespace App\Models;

use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'kode', 'deskripsi', 'aktif', 'pakai_template'])]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'pakai_template' => 'boolean',
        ];
    }

    /**
     * True bila karyawan jabatan ini wajib punya template shift.
     *
     * Kalau false, shift-nya ikut window yang cocok dengan jam datang, bukan
     * dari template. Ini yang membuat kasir dan pramuniaga tidak perlu input
     * template satu per satu.
     */
    public function wajibTemplate(): bool
    {
        return $this->pakai_template === true;
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
