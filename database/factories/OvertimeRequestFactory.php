<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    protected $model = OvertimeRequest::class;

    public function definition(): array
    {
        $mulai = '18:00';
        $durasi = 2.0;

        return [
            'employee_id' => Employee::factory(),
            'tanggal' => now()->toDateString(),
            'jam_mulai' => $mulai,
            'jam_selesai' => '20:00',
            'durasi_jam' => $durasi,
            'tarif_per_jam' => 20000,
            'total_lembur' => $durasi * 20000,
            'keterangan' => fake()->sentence(),
            'status' => RequestStatus::Pending,
        ];
    }

    public function disetujui(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Rejected,
            'reviewed_at' => now(),
        ]);
    }

    public function untuk(Employee $employee): static
    {
        return $this->state(fn () => ['employee_id' => $employee->id]);
    }
}
