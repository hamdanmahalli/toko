<?php

namespace Database\Factories;

use App\Enums\LeaveType;
use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $mulai = fake()->dateTimeBetween('-5 days', '+5 days');

        return [
            'employee_id' => Employee::factory(),
            'jenis' => LeaveType::Izin,
            'tanggal_mulai' => $mulai->format('Y-m-d'),
            'tanggal_selesai' => $mulai->modify('+0 days')->format('Y-m-d'),
            'jumlah_hari' => 1,
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
