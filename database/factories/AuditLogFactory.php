<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'aksi' => fake()->randomElement(['absen.catat', 'karyawan.ubah', 'pengajuan.setujui']),
            'entitas' => null,
            'entitas_id' => null,
            'ip' => fake()->ipv4(),
            'user_agent' => null,
            'payload' => null,
        ];
    }

    /**
     * Catatan untuk satu entitas tertentu, mis. 'employee' dan id-nya.
     */
    public function untuk(string $entitas, string|int $id): static
    {
        return $this->state(fn () => ['entitas' => $entitas, 'entitas_id' => (string) $id]);
    }

    /** Aksi yang tidak dilakukan akun mana pun, mis. login perangkat presensi. */
    public function anonim(): static
    {
        return $this->state(fn () => ['user_id' => null]);
    }
}
