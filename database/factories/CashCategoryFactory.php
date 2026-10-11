<?php

namespace Database\Factories;

use App\Enums\JenisKas;
use App\Models\CashCategory;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashCategory>
 */
class CashCategoryFactory extends Factory
{
    protected $model = CashCategory::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'kode' => 'kat_'.fake()->unique()->numerify('####'),
            'nama' => 'Kategori '.fake()->word(),
            'jenis' => JenisKas::Masuk,
            'urutan' => 0,
            'aktif' => true,
        ];
    }

    public function untuk(Employee $employee): static
    {
        return $this->state(fn () => ['employee_id' => $employee->id]);
    }

    public function masuk(): static
    {
        return $this->state(fn () => ['jenis' => JenisKas::Masuk]);
    }

    public function keluar(): static
    {
        return $this->state(fn () => ['jenis' => JenisKas::Keluar]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
