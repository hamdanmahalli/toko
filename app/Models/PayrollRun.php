<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Database\Factories\PayrollRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'periode', 'status', 'jumlah_karyawan',
    'total_pokok', 'total_lembur', 'total_potong', 'total_bersih',
    'catatan', 'generated_by', 'generated_at',
])]
class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'status' => PayrollStatus::class,
            'jumlah_karyawan' => 'integer',
            'total_pokok' => 'decimal:2',
            'total_lembur' => 'decimal:2',
            'total_potong' => 'decimal:2',
            'total_bersih' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function periodeLabel(): string
    {
        return $this->periode->translatedFormat('F Y');
    }

    public function isLocked(): bool
    {
        return $this->status !== PayrollStatus::Draft;
    }
}
