<?php

namespace App\Models;

use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nama', 'tanggal', 'shop_id', 'aktif'])]
class Holiday extends Model
{
    /** @use HasFactory<HolidayFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Libur yang berlaku untuk toko tertentu (null = semua toko). */
    public function scopeUntukToko(Builder $query, int|Shop|null $shop): Builder
    {
        $id = $shop instanceof Shop ? $shop->id : $shop;

        return $query->where('aktif', true)
            ->where(fn ($q) => $q->whereNull('shop_id')->orWhere('shop_id', $id));
    }
}
