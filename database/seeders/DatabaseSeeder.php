<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PositionSeeder::class,
            SettingSeeder::class,
            ShiftTemplateSeeder::class,
        ]);

        // Akun pengguna SENGAJA tidak di-seed: password tidak boleh pernah
        // punya nilai default yang tersebar di repository.
        // Buat lewat: php artisan toko:buat-akun
    }
}
