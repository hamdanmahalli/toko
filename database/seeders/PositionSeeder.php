<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        // pakai_template menentukan dari mana aturan jam seorang karyawan:
        //   true  -> wajib punya template shift (supervisor, manajer)
        //   false -> mengikuti window shift sesuai jam datang (kasir, pramuniaga)
        $positions = [
            [
                'nama' => 'Kasir',
                'kode' => 'KASIR',
                'deskripsi' => 'Melayani pelanggan di kasir.',
                'pakai_template' => false,
            ],
            [
                'nama' => 'Pramuniaga',
                'kode' => 'PRAMUNIAGA',
                'deskripsi' => 'Melayani pelanggan di rak penjualan.',
                'pakai_template' => false,
            ],
            [
                'nama' => 'Supervisor',
                'kode' => 'SUPERVISOR',
                'deskripsi' => 'Mengawasi operasional toko.',
                'pakai_template' => true,
            ],
            [
                'nama' => 'Manajer Toko',
                'kode' => 'MANAGER',
                'deskripsi' => 'Menangani toko secara keseluruhan.',
                'pakai_template' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(['kode' => $position['kode']], $position);
        }
    }
}
