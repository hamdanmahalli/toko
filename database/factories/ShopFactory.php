<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'nama' => 'Toko '.fake()->unique()->city(),
            'kode' => strtoupper(fake()->unique()->bothify('TK-####')),
            'alamat' => fake()->address(),
            // Monas, Jakarta
            'latitude' => -6.1753924,
            'longitude' => 106.8271528,
            'radius_meter' => 150,
            'zona_waktu' => 'Asia/Jakarta',
            'aktif' => true,
        ];
    }

    /** Toko tanpa koordinat -> geofence tidak bisa dipakai. */
    public function tanpaKoordinat(): static
    {
        return $this->state(fn () => ['latitude' => null, 'longitude' => null]);
    }

    public function radius(int $meter): static
    {
        return $this->state(fn () => ['radius_meter' => $meter]);
    }

    public function diLokasi(float $lat, float $lng): static
    {
        return $this->state(fn () => ['latitude' => $lat, 'longitude' => $lng]);
    }
}
