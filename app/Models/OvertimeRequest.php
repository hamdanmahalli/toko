<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Database\Factories\OvertimeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'durasi_jam',
    'tarif_per_jam', 'total_lembur', 'keterangan',
    'status', 'reviewed_by', 'reviewed_at', 'catatan_reviewer',
])]
class OvertimeRequest extends Model
{
    /** @use HasFactory<OvertimeRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_mulai' => 'datetime:H:i',
            'jam_selesai' => 'datetime:H:i',
            'durasi_jam' => 'float',
            'tarif_per_jam' => 'decimal:2',
            'total_lembur' => 'decimal:2',
            'status' => RequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RequestStatus::Pending->value);
    }

    public function scopeDisetujui(Builder $query): Builder
    {
        return $query->where('status', RequestStatus::Approved->value);
    }
}
