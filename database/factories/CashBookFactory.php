<?php

namespace Database\Factories;

use App\Models\CashBook;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBook>
 */
class CashBookFactory extends Factory
{
    protected $model = CashBook::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'nama' => 'Kas '.fake()->word(),
            'keterangan' => null,
            'saldo_awal' => 0,
            'aktif' => true,
        ];
    }

    public function untuk(Employee $employee): static
    {
        return $this->state(fn () => ['employee_id' => $employee->id]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
