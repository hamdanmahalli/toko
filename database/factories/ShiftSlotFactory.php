<?php

namespace Database\Factories;

use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftSlot>
 */
class ShiftSlotFactory extends Factory
{
    protected $model = ShiftSlot::class;

    public function definition(): array
    {
        return [
            'shift_template_id' => ShiftTemplate::factory(),
            'hari' => 1,
            'jam_masuk' => '08:00',
            'batas_telat' => '08:15',
            'jam_pulang' => '17:00',
            'aktif' => true,
        ];
    }
}
