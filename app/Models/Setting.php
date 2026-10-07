<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'tipe', 'kelompok', 'keterangan'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public function scopeKelompok(Builder $query, string $kelompok): Builder
    {
        return $query->where('kelompok', $kelompok);
    }

    /** Nilai mentah apa adanya (selalu string). */
    public static function ambil(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function ambilInt(string $key, ?int $default = null): ?int
    {
        $value = static::ambil($key);

        return $value === null || $value === '' ? $default : (int) $value;
    }

    public static function ambilFloat(string $key, ?float $default = null): ?float
    {
        $value = static::ambil($key);

        return $value === null || $value === '' ? $default : (float) $value;
    }

    public static function ambilBool(string $key, ?bool $default = null): ?bool
    {
        $value = static::ambil($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function ambilJson(string $key, ?array $default = null): ?array
    {
        $value = static::ambil($key);

        if ($value === null || $value === '') {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /** @param array<string, string|int|float|bool|array|null> $values */
    public static function simpan(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'tipe' => is_array($value) ? 'json' : (is_bool($value) ? 'bool' : 'string'),
                ],
            );
        }
    }
}
