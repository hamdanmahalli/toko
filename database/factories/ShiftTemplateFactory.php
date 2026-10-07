<?php

namespace Database\Factories;

use App\Enums\ShiftScope;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftTemplate>
 */
class ShiftTemplateFactory extends Factory
{
    protected $model = ShiftTemplate::class;

    public function definition(): array
    {
        return [
            'nama' => 'Shift '.fake()->unique()->word(),
            'scope' => ShiftScope::Global,
            'shop_id' => null,
            'aktif' => true,
        ];
    }

    /**
     * Pasang slot untuk hari-hari kerja.
     *
     * @param  array<int, int>  $hari  1=Senin ... 6=Sabtu
     */
    public function denganSlot(array $hari = [1, 2, 3, 4, 5], string $masuk = '08:00', string $batas = '08:15', string $pulang = '17:00'): static
    {
        return $this->afterCreating(function (ShiftTemplate $template) use ($hari, $masuk, $batas, $pulang) {
            foreach ($hari as $h) {
                ShiftSlot::factory()->create([
                    'shift_template_id' => $template->id,
                    'hari' => $h,
                    'jam_masuk' => $masuk,
                    'batas_telat' => $batas,
                    'jam_pulang' => $pulang,
                ]);
            }
        });
    }
}
