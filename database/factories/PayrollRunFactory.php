<?php

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    public function definition(): array
    {
        return [
            // Periode selalu disimpan sebagai tanggal pertama bulan tersebut.
            'periode' => now()->startOfMonth()->toDateString(),
            'status' => PayrollStatus::Draft,
            'jumlah_karyawan' => 0,
            'total_pokok' => 0,
            'total_lembur' => 0,
            'total_potong' => 0,
            'total_bersih' => 0,
            'catatan' => null,
            'generated_by' => null,
            'generated_at' => null,
        ];
    }

    public function periode(string $bulan): static
    {
        return $this->state(fn () => ['periode' => $bulan.'-01']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PayrollStatus::Draft]);
    }

    /** Run yang sudah final tidak boleh diubah lagi. */
    public function final(?User $generator = null): static
    {
        return $this->state(fn () => [
            'status' => PayrollStatus::Final,
            'generated_by' => $generator?->id,
            'generated_at' => now(),
        ]);
    }

    public function batal(): static
    {
        return $this->state(fn () => ['status' => PayrollStatus::Batal]);
    }
}
