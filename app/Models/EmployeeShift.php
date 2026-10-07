<?php

namespace App\Models;

use Database\Factories\EmployeeShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'shift_template_id', 'mulai_berlaku', 'selesai_berlaku', 'aktif'])]
class EmployeeShift extends Model
{
    /** @use HasFactory<EmployeeShiftFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'mulai_berlaku' => 'date',
            'selesai_berlaku' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    /** Penugasan yang berlaku pada tanggal tertentu. */
    public function scopeBerlakuPada(Builder $query, string $tanggal): Builder
    {
        return $query->where('aktif', true)
            ->where(fn ($q) => $q->whereNull('mulai_berlaku')->orWhereDate('mulai_berlaku', '<=', $tanggal))
            ->where(fn ($q) => $q->whereNull('selesai_berlaku')->orWhereDate('selesai_berlaku', '>=', $tanggal));
    }

    public function berlakuPadaTanggal(string $tanggal): bool
    {
        return $this->aktif
            && ($this->mulai_berlaku === null || $this->mulai_berlaku->lte($tanggal))
            && ($this->selesai_berlaku === null || $this->selesai_berlaku->gte($tanggal));
    }
}
