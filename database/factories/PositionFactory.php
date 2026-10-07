<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->jobTitle(),
            'kode' => strtoupper(fake()->unique()->bothify('??##')),
            'deskripsi' => fake()->sentence(),
            'pakai_template' => false,
            'aktif' => true,
        ];
    }

    /** Jabatan yang wajib punya template shift, seperti supervisor dan manajer. */
    public function wajibTemplate(): static
    {
        return $this->state(fn () => ['pakai_template' => true]);
    }

    /** Jabatan yang mengikuti window shift sesuai jam datang. */
    public function ikutWindow(): static
    {
        return $this->state(fn () => ['pakai_template' => false]);
    }

    public function tidakAktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
