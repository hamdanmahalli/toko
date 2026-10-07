<?php

namespace App\Models;

use App\Enums\LeaveType;
use App\Enums\RequestStatus;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_hari',
    'keterangan', 'bukti', 'status', 'reviewed_by', 'reviewed_at', 'catatan_reviewer',
])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'jenis' => LeaveType::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'jumlah_hari' => 'float',
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

    /** Daftar tanggal yang dicakup pengajuan ini, untuk dihitung saat rekap. */
    public function tanggalRentang(): array
    {
        $dates = [];
        $cursor = $this->tanggal_mulai->copy();
        $selesai = $this->tanggal_selesai->copy();

        while ($cursor->lessThanOrEqualTo($selesai) && count($dates) < 400) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }
}
