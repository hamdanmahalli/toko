<?php

namespace App\Models;

use Database\Factories\PayrollDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payroll_run_id', 'employee_id', 'shop_id',
    'hari_hadir', 'hari_izin', 'hari_sakit', 'hari_cuti', 'hari_alpha', 'hari_libur',
    'menit_telat', 'jam_lembur',
    'gaji_pokok', 'total_lembur', 'potongan', 'total', 'rincian',
])]
class PayrollDetail extends Model
{
    /** @use HasFactory<PayrollDetailFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hari_hadir' => 'integer',
            'hari_izin' => 'integer',
            'hari_sakit' => 'integer',
            'hari_cuti' => 'integer',
            'hari_alpha' => 'integer',
            'hari_libur' => 'integer',
            'menit_telat' => 'integer',
            'jam_lembur' => 'float',
            'gaji_pokok' => 'decimal:2',
            'total_lembur' => 'decimal:2',
            'potongan' => 'decimal:2',
            'total' => 'decimal:2',
            'rincian' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
