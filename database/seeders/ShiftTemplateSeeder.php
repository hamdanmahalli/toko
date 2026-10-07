<?php

namespace Database\Seeders;

use App\Enums\ShiftScope;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = ShiftTemplate::updateOrCreate(
            ['nama' => 'Shift Standar'],
            [
                'scope' => ShiftScope::Global,
                'keterangan' => 'Senin–Sabtu, 08:00–17:00, toleransi telat 15 menit. Bisa diubah.',
                'aktif' => true,
            ],
        );

        // 1 = Senin ... 6 = Sabtu, 0 = Minggu (libur)
        $slots = [
            1 => ['08:00', '08:15', '17:00'],
            2 => ['08:00', '08:15', '17:00'],
            3 => ['08:00', '08:15', '17:00'],
            4 => ['08:00', '08:15', '17:00'],
            5 => ['08:00', '08:15', '17:00'],
            6 => ['08:00', '08:15', '17:00'],
        ];

        foreach ($slots as $hari => [$jamMasuk, $batasTelat, $jamPulang]) {
            ShiftSlot::updateOrCreate(
                ['shift_template_id' => $template->id, 'hari' => $hari],
                [
                    'jam_masuk' => $jamMasuk,
                    'batas_telat' => $batasTelat,
                    'jam_pulang' => $jamPulang,
                    'aktif' => true,
                ],
            );
        }
    }
}
