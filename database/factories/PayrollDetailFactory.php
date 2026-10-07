<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollDetail>
 */
class PayrollDetailFactory extends Factory
{
    protected $model = PayrollDetail::class;

    public function definition(): array
    {
        return [
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'shop_id' => Shop::factory(),
            'hari_hadir' => 0,
            'hari_izin' => 0,
            'hari_sakit' => 0,
            'hari_cuti' => 0,
            'hari_alpha' => 0,
            'hari_libur' => 0,
            'menit_telat' => 0,
            'jam_lembur' => 0,
            'gaji_pokok' => 0,
            'total_lembur' => 0,
            'potongan' => 0,
            'total' => 0,
            'rincian' => null,
        ];
    }

    /**
     * Rincian satu karyawan pada run tertentu.
     *
     *Toko diambil dari karyawannya supaya angka payroll tidak pernah
     * menunjuk toko yang berbeda dari tempat karyawan itu bekerja.
     */
    public function untuk(Employee $employee, PayrollRun $run): static
    {
        return $this->state(fn () => [
            'employee_id' => $employee->id,
            'shop_id' => $employee->shop_id,
            'payroll_run_id' => $run->id,
        ]);
    }
}
