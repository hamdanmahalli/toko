<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->word().' Nasional',
            'tanggal' => fake()->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'shop_id' => null,
            'aktif' => true,
        ];
    }
}
