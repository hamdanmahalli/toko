<?php

namespace Database\Factories;

use App\Enums\PayrollType;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'shop_id' => Shop::factory(),
            'position_id' => null,
            'nip' => fake()->unique()->numerify('K-#####'),
            'nama' => fake()->name(),
            'telepon' => fake()->numerify('08##########'),
            'tipe_payroll' => PayrollType::Harian,
            'gaji_harian' => 150000,
            'qr_version' => 1,
            'tanggal_masuk' => now()->subMonth()->toDateString(),
            'aktif' => true,
        ];
    }

    /**
     * Kasir dan pramuniaga mengikuti window shift sesuai jam datang, jadi
     * tidak butuh template shift.
     */
    public function kasir(): static
    {
        return $this->state(fn () => [
            'position_id' => Position::firstOrCreate(
                ['kode' => 'KASIR'],
                ['nama' => 'Kasir', 'pakai_template' => false],
            )->id,
        ]);
    }

    /** Manajer dan kepala toko wajib punya template shift. */
    public function manajer(): static
    {
        return $this->state(fn () => [
            'position_id' => Position::firstOrCreate(
                ['kode' => 'MANAGER'],
                ['nama' => 'Manajer Toko', 'pakai_template' => true],
            )->id,
        ]);
    }

    public function denganAkun(?User $user = null): static
    {
        return $this->state(fn () => [
            'user_id' => ($user ?? User::factory()->create())->id,
        ]);
    }

    public function tidakAktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }

    public function gaji(float $gaji): static
    {
        return $this->state(fn () => ['gaji_harian' => $gaji]);
    }
}
